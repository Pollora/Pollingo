<?php

declare(strict_types=1);

namespace Pollora\Pollingo\Services;

/**
 * Service for building messages to send to AI APIs
 */
final class MessageBuilder
{
    /**
     * Build a message array for AI API requests
     *
     * @param string $systemPrompt The system prompt
     * @param string $userMessage The user message
     * @return array<string, mixed> The message array
     */
    public function buildMessages(string $systemPrompt, string $userMessage): array
    {
        return [
            [
                'role' => 'system',
                'content' => $systemPrompt,
            ],
            [
                'role' => 'user',
                'content' => $userMessage,
            ],
        ];
    }
} 