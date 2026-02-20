package services

import (
	"bytes"
	"encoding/json"
	"errors"
	"fmt"
	"io"
	"kelisim-chat/internal/config"
	"net/http"
	"time"

	"github.com/sirupsen/logrus"
)

// LLMService handles interactions with DeepSeek LLM API
type LLMService struct {
	apiKey      string
	apiBase     string
	model       string
	maxTokens   int
	temperature float64
	timeout     time.Duration
	httpClient  *http.Client
}

// ChatMessage represents a message in the conversation
type ChatMessage struct {
	Role    string `json:"role"`    // "system", "user", or "assistant"
	Content string `json:"content"` // The message content
}

// ChatCompletionRequest represents the request payload for chat completion
type ChatCompletionRequest struct {
	Model       string        `json:"model"`
	Messages    []ChatMessage `json:"messages"`
	MaxTokens   int           `json:"max_tokens,omitempty"`
	Temperature float64       `json:"temperature,omitempty"`
	Stream      bool          `json:"stream"`
}

// ChatCompletionResponse represents the response from chat completion
type ChatCompletionResponse struct {
	ID      string `json:"id"`
	Object  string `json:"object"`
	Created int64  `json:"created"`
	Model   string `json:"model"`
	Choices []struct {
		Index   int `json:"index"`
		Message struct {
			Role    string `json:"role"`
			Content string `json:"content"`
		} `json:"message"`
		FinishReason string `json:"finish_reason"`
	} `json:"choices"`
	Usage struct {
		PromptTokens     int `json:"prompt_tokens"`
		CompletionTokens int `json:"completion_tokens"`
		TotalTokens      int `json:"total_tokens"`
	} `json:"usage"`
}

// ErrorResponse represents an error response from the API
type ErrorResponse struct {
	Error struct {
		Message string `json:"message"`
		Type    string `json:"type"`
		Code    string `json:"code"`
	} `json:"error"`
}

// NewLLMService creates a new LLM service instance
func NewLLMService() *LLMService {
	cfg := config.AppConfig.LLM
	
	return &LLMService{
		apiKey:      cfg.APIKey,
		apiBase:     cfg.APIBase,
		model:       cfg.Model,
		maxTokens:   cfg.MaxTokens,
		temperature: cfg.Temperature,
		timeout:     time.Duration(cfg.Timeout) * time.Second,
		httpClient: &http.Client{
			Timeout: time.Duration(cfg.Timeout) * time.Second,
		},
	}
}

// ChatCompletion sends a chat completion request to DeepSeek API
func (s *LLMService) ChatCompletion(messages []ChatMessage) (*ChatCompletionResponse, error) {
	if s.apiKey == "" {
		return nil, errors.New("DEEPSEEK_API_KEY is not configured")
	}

	// Prepare request payload
	reqPayload := ChatCompletionRequest{
		Model:       s.model,
		Messages:    messages,
		MaxTokens:   s.maxTokens,
		Temperature: s.temperature,
		Stream:      false,
	}

	jsonData, err := json.Marshal(reqPayload)
	if err != nil {
		logrus.WithError(err).Error("Failed to marshal request payload")
		return nil, fmt.Errorf("failed to prepare request: %w", err)
	}

	// Create HTTP request
	url := fmt.Sprintf("%s/chat/completions", s.apiBase)
	req, err := http.NewRequest("POST", url, bytes.NewBuffer(jsonData))
	if err != nil {
		logrus.WithError(err).Error("Failed to create HTTP request")
		return nil, fmt.Errorf("failed to create request: %w", err)
	}

	// Set headers
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Authorization", fmt.Sprintf("Bearer %s", s.apiKey))

	// Log request (without sensitive data)
	logrus.WithFields(logrus.Fields{
		"model":    s.model,
		"messages": len(messages),
	}).Debug("Sending chat completion request to DeepSeek API")

	// Send request
	resp, err := s.httpClient.Do(req)
	if err != nil {
		logrus.WithError(err).Error("Failed to send request to DeepSeek API")
		return nil, fmt.Errorf("API request failed: %w", err)
	}
	defer resp.Body.Close()

	// Read response body
	body, err := io.ReadAll(resp.Body)
	if err != nil {
		logrus.WithError(err).Error("Failed to read response body")
		return nil, fmt.Errorf("failed to read response: %w", err)
	}

	// Check for error response
	if resp.StatusCode != http.StatusOK {
		var errResp ErrorResponse
		if err := json.Unmarshal(body, &errResp); err == nil && errResp.Error.Message != "" {
			logrus.WithFields(logrus.Fields{
				"status":  resp.StatusCode,
				"message": errResp.Error.Message,
				"type":    errResp.Error.Type,
			}).Error("DeepSeek API returned error")
			return nil, fmt.Errorf("API error: %s", errResp.Error.Message)
		}
		return nil, fmt.Errorf("API request failed with status %d: %s", resp.StatusCode, string(body))
	}

	// Parse response
	var completionResp ChatCompletionResponse
	if err := json.Unmarshal(body, &completionResp); err != nil {
		logrus.WithError(err).WithField("body", string(body)).Error("Failed to parse response")
		return nil, fmt.Errorf("failed to parse response: %w", err)
	}

	// Log success
	logrus.WithFields(logrus.Fields{
		"tokens_used": completionResp.Usage.TotalTokens,
		"model":       completionResp.Model,
	}).Info("DeepSeek API request successful")

	return &completionResp, nil
}

// AskQuestion is a helper method to ask a single question with optional context
func (s *LLMService) AskQuestion(question string, systemPrompt string, context []ChatMessage) (string, error) {
	messages := []ChatMessage{}

	// Add system prompt if provided
	if systemPrompt != "" {
		messages = append(messages, ChatMessage{
			Role:    "system",
			Content: systemPrompt,
		})
	}

	// Add context messages if provided
	if len(context) > 0 {
		messages = append(messages, context...)
	}

	// Add the user's question
	messages = append(messages, ChatMessage{
		Role:    "user",
		Content: question,
	})

	// Call API
	response, err := s.ChatCompletion(messages)
	if err != nil {
		return "", err
	}

	// Extract the assistant's reply
	if len(response.Choices) == 0 {
		return "", errors.New("no response from API")
	}

	return response.Choices[0].Message.Content, nil
}

// SummarizeConversation summarizes a conversation given the message history
func (s *LLMService) SummarizeConversation(messages []ChatMessage, language string) (string, error) {
	if len(messages) == 0 {
		return "", errors.New("no messages to summarize")
	}

	// Determine the prompt based on language
	var prompt string
	switch language {
	case "zh", "zh-Hans", "zh_CN":
		prompt = "请用中文总结以下对话的主要内容和关键要点："
	case "ru":
		prompt = "Пожалуйста, суммируйте основное содержание и ключевые моменты следующего разговора на русском языке:"
	case "kk":
		prompt = "Келесі әңгіменің негізгі мазмұны мен басты тұстарын қазақ тілінде қорытындылаңыз:"
	default:
		prompt = "Please summarize the main content and key points of the following conversation in English:"
	}

	// Build the summary request
	summaryMessages := []ChatMessage{
		{
			Role:    "system",
			Content: prompt,
		},
	}

	// Add the conversation history
	summaryMessages = append(summaryMessages, messages...)

	// Call API
	response, err := s.ChatCompletion(summaryMessages)
	if err != nil {
		return "", err
	}

	// Extract the summary
	if len(response.Choices) == 0 {
		return "", errors.New("no response from API")
	}

	return response.Choices[0].Message.Content, nil
}

