<?php

namespace App\Services;

use App\Models\Consultation;
use App\Models\Message;
use Illuminate\Support\Facades\Log;
use Exception;

class ConsultationAnalysisService
{
    protected $llmService;

    public function __construct(LLMService $llmService)
    {
        $this->llmService = $llmService;
    }

    /**
     * Analyze a consultation and extract key information
     *
     * @param Consultation $consultation
     * @param string $language
     * @return array
     */
    public function analyzeConsultation(Consultation $consultation, string $language = 'en'): array
    {
        try {
            // Build context from consultation data
            $contextText = $this->buildConsultationContext($consultation);

            // Analyze using LLM
            $analysis = $this->llmService->analyzeText($contextText, $language);

            // Add consultation metadata
            $analysis['consultation_id'] = $consultation->id;
            $analysis['analyzed_at'] = now()->toISOString();

            return $analysis;
        } catch (Exception $e) {
            Log::error('Failed to analyze consultation', [
                'consultation_id' => $consultation->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Generate a summary of a consultation
     *
     * @param Consultation $consultation
     * @param string $language
     * @return string
     */
    public function summarizeConsultation(Consultation $consultation, string $language = 'en'): string
    {
        try {
            // Build conversation from chat messages
            $messages = $this->buildConversationMessages($consultation);

            if (empty($messages)) {
                return $this->getSummaryPrompt($language, 'no_messages');
            }

            // Generate summary using LLM
            $summary = $this->llmService->summarizeConversation($messages, $language);

            return $summary;
        } catch (Exception $e) {
            Log::error('Failed to summarize consultation', [
                'consultation_id' => $consultation->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Build consultation context text
     *
     * @param Consultation $consultation
     * @return string
     */
    protected function buildConsultationContext(Consultation $consultation): string
    {
        $parts = [];

        // Title
        if ($consultation->title) {
            $parts[] = "Title: {$consultation->title}";
        }

        // Description
        if ($consultation->description) {
            $parts[] = "Description: {$consultation->description}";
        }

        // Topic type
        if ($consultation->topic_type) {
            $parts[] = "Topic: {$consultation->topic_type}";
        }

        // Current status
        if ($consultation->status) {
            $parts[] = "Status: {$consultation->status}";
        }

        // Priority
        if ($consultation->priority) {
            $parts[] = "Priority: {$consultation->priority}";
        }

        // Include chat messages if available
        if ($consultation->chat_id) {
            $chatMessages = Message::where('chat_id', $consultation->chat_id)
                ->where('type', 'text')
                ->whereNotNull('content')
                ->orderBy('created_at', 'asc')
                ->limit(20)
                ->get();

            if ($chatMessages->isNotEmpty()) {
                $parts[] = "\nRecent conversation:";
                foreach ($chatMessages as $message) {
                    $sender = $message->sender ? $message->sender->getFullName() : 'System';
                    $parts[] = "{$sender}: {$message->content}";
                }
            }
        }

        return implode("\n", $parts);
    }

    /**
     * Build conversation messages for LLM
     *
     * @param Consultation $consultation
     * @return array
     */
    protected function buildConversationMessages(Consultation $consultation): array
    {
        if (!$consultation->chat_id) {
            return [];
        }

        $messages = Message::where('chat_id', $consultation->chat_id)
            ->where('type', 'text')
            ->whereNotNull('content')
            ->orderBy('created_at', 'asc')
            ->limit(50)
            ->get();

        $conversationMessages = [];

        foreach ($messages as $message) {
            $role = 'user';
            $sender = $message->sender ? $message->sender->getFullName() : 'System';
            
            // System messages are treated as assistant
            if ($message->type === 'system' || !$message->sender_id) {
                $role = 'assistant';
            }

            $conversationMessages[] = [
                'role' => $role,
                'content' => "{$sender}: {$message->content}",
            ];
        }

        return $conversationMessages;
    }

    /**
     * Get summary prompt based on language and context
     *
     * @param string $language
     * @param string $context
     * @return string
     */
    protected function getSummaryPrompt(string $language, string $context): string
    {
        $prompts = [
            'en' => [
                'no_messages' => 'No messages available to summarize.',
            ],
            'zh' => [
                'no_messages' => '没有可供总结的消息。',
            ],
            'ru' => [
                'no_messages' => 'Нет сообщений для суммирования.',
            ],
            'kk' => [
                'no_messages' => 'Қорытындылау үшін хабарламалар жоқ.',
            ],
        ];

        return $prompts[$language][$context] ?? $prompts['en'][$context];
    }

    /**
     * Suggest priority for a consultation based on its content
     *
     * @param Consultation $consultation
     * @param string $language
     * @return string
     */
    public function suggestPriority(Consultation $consultation, string $language = 'en'): string
    {
        try {
            $contextText = $this->buildConsultationContext($consultation);

            $prompts = [
                'zh' => "基于以下咨询内容，建议优先级（low/medium/high/urgent）。只返回优先级值：\n\n{$contextText}",
                'ru' => "На основе следующей консультации предложите приоритет (low/medium/high/urgent). Верните только значение приоритета:\n\n{$contextText}",
                'kk' => "Келесі кеңес негізінде басымдықты ұсыныңыз (low/medium/high/urgent). Тек басымдық мәнін қайтарыңыз:\n\n{$contextText}",
            ];

            $prompt = $prompts[$language] ?? "Based on the following consultation, suggest a priority level (low/medium/high/urgent). Return only the priority value:\n\n{$contextText}";

            $response = $this->llmService->askQuestion($prompt);

            // Extract priority from response
            $priority = strtolower(trim($response));
            $validPriorities = ['low', 'medium', 'high', 'urgent'];

            foreach ($validPriorities as $validPriority) {
                if (str_contains($priority, $validPriority)) {
                    return $validPriority;
                }
            }

            // Default to medium if unable to determine
            return 'medium';
        } catch (Exception $e) {
            Log::error('Failed to suggest priority', [
                'consultation_id' => $consultation->id,
                'error' => $e->getMessage(),
            ]);

            return 'medium';
        }
    }
}

