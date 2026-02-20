<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class LLMService
{
    protected $apiKey;
    protected $apiBase;
    protected $model;
    protected $maxTokens;
    protected $temperature;
    protected $timeout;

    public function __construct()
    {
        $this->apiKey = config('services.deepseek.api_key');
        $this->apiBase = config('services.deepseek.api_base', 'https://api.deepseek.com/v1');
        $this->model = config('services.deepseek.model', 'deepseek-chat');
        $this->maxTokens = config('services.deepseek.max_tokens', 2000);
        $this->temperature = config('services.deepseek.temperature', 0.7);
        $this->timeout = config('services.deepseek.timeout', 30);
    }

    /**
     * Send a chat completion request to DeepSeek API
     *
     * @param array $messages Array of message objects with 'role' and 'content'
     * @return array Response from the API
     * @throws Exception
     */
    public function chatCompletion(array $messages): array
    {
        if (empty($this->apiKey)) {
            throw new Exception('DEEPSEEK_API_KEY is not configured');
        }

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post($this->apiBase . '/chat/completions', [
                    'model' => $this->model,
                    'messages' => $messages,
                    'max_tokens' => $this->maxTokens,
                    'temperature' => $this->temperature,
                    'stream' => false,
                ]);

            if (!$response->successful()) {
                $error = $response->json('error.message', $response->body());
                Log::error('DeepSeek API error', [
                    'status' => $response->status(),
                    'error' => $error,
                ]);
                throw new Exception("DeepSeek API error: {$error}");
            }

            $data = $response->json();

            Log::info('DeepSeek API request successful', [
                'tokens_used' => $data['usage']['total_tokens'] ?? 0,
                'model' => $data['model'] ?? $this->model,
            ]);

            return $data;
        } catch (Exception $e) {
            Log::error('Failed to call DeepSeek API', [
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Ask a single question with optional context
     *
     * @param string $question The question to ask
     * @param string|null $systemPrompt System prompt for context
     * @param array $context Additional context messages
     * @return string The assistant's response
     * @throws Exception
     */
    public function askQuestion(string $question, ?string $systemPrompt = null, array $context = []): string
    {
        $messages = [];

        // Add system prompt if provided
        if ($systemPrompt) {
            $messages[] = [
                'role' => 'system',
                'content' => $systemPrompt,
            ];
        }

        // Add context messages
        if (!empty($context)) {
            $messages = array_merge($messages, $context);
        }

        // Add user's question
        $messages[] = [
            'role' => 'user',
            'content' => $question,
        ];

        $response = $this->chatCompletion($messages);

        if (empty($response['choices'])) {
            throw new Exception('No response from API');
        }

        return $response['choices'][0]['message']['content'] ?? '';
    }

    /**
     * Summarize a conversation
     *
     * @param array $messages Array of message objects
     * @param string $language Language for the summary
     * @return string The summary
     * @throws Exception
     */
    public function summarizeConversation(array $messages, string $language = 'en'): string
    {
        if (empty($messages)) {
            throw new Exception('No messages to summarize');
        }

        // Determine prompt based on language
        $prompts = [
            'zh' => '请用中文总结以下对话的主要内容和关键要点：',
            'zh-Hans' => '请用中文总结以下对话的主要内容和关键要点：',
            'zh_CN' => '请用中文总结以下对话的主要内容和关键要点：',
            'ru' => 'Пожалуйста, суммируйте основное содержание и ключевые моменты следующего разговора на русском языке:',
            'kk' => 'Келесі әңгіменің негізгі мазмұны мен басты тұстарын қазақ тілінде қорытындылаңыз:',
        ];

        $prompt = $prompts[$language] ?? 'Please summarize the main content and key points of the following conversation in English:';

        // Build summary request
        $summaryMessages = [
            [
                'role' => 'system',
                'content' => $prompt,
            ],
        ];

        // Add conversation history
        $summaryMessages = array_merge($summaryMessages, $messages);

        $response = $this->chatCompletion($summaryMessages);

        if (empty($response['choices'])) {
            throw new Exception('No response from API');
        }

        return $response['choices'][0]['message']['content'] ?? '';
    }

    /**
     * Analyze text and extract key information
     *
     * @param string $text Text to analyze
     * @param string $language Language for the analysis
     * @return array Analyzed information
     * @throws Exception
     */
    public function analyzeText(string $text, string $language = 'en'): array
    {
        $prompts = [
            'zh' => "请分析以下文本，提取关键信息，包括：\n1. 主要主题\n2. 关键问题或需求\n3. 建议的优先级（低/中/高/紧急）\n4. 建议的分类\n\n请以JSON格式返回结果。",
            'ru' => "Проанализируйте следующий текст и извлеките ключевую информацию, включая:\n1. Основную тему\n2. Ключевые вопросы или требования\n3. Рекомендуемый приоритет (низкий/средний/высокий/срочный)\n4. Рекомендуемую категорию\n\nПожалуйста, верните результат в формате JSON.",
            'kk' => "Келесі мәтінді талдап, негізгі ақпаратты алыңыз:\n1. Басты тақырып\n2. Негізгі мәселелер немесе талаптар\n3. Ұсынылған басымдық (төмен/орташа/жоғары/шұғыл)\n4. Ұсынылған санат\n\nJSON форматында қайтарыңыз.",
        ];

        $prompt = $prompts[$language] ?? "Please analyze the following text and extract key information, including:\n1. Main topic\n2. Key issues or requirements\n3. Suggested priority (low/medium/high/urgent)\n4. Suggested category\n\nPlease return the result in JSON format.";

        $messages = [
            [
                'role' => 'system',
                'content' => $prompt,
            ],
            [
                'role' => 'user',
                'content' => $text,
            ],
        ];

        $response = $this->chatCompletion($messages);

        if (empty($response['choices'])) {
            throw new Exception('No response from API');
        }

        $content = $response['choices'][0]['message']['content'] ?? '';

        // Try to parse JSON response
        try {
            // Extract JSON from markdown code blocks if present
            if (preg_match('/```json\s*(.*?)\s*```/s', $content, $matches)) {
                $content = $matches[1];
            } elseif (preg_match('/```\s*(.*?)\s*```/s', $content, $matches)) {
                $content = $matches[1];
            }

            $analyzed = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                // If JSON parsing fails, return raw content
                return [
                    'raw_content' => $content,
                    'parsed' => false,
                ];
            }

            return array_merge($analyzed, ['parsed' => true]);
        } catch (Exception $e) {
            return [
                'raw_content' => $content,
                'parsed' => false,
            ];
        }
    }
}

