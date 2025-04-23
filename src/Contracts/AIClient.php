<?php

declare(strict_types=1);

namespace Pollora\Pollingo\Contracts;

/**
 * Interface for AI API clients
 */
interface AIClient
{
    /**
     * Send a chat completion request to the AI service
     *
     * @param string $model The model to use
     * @param array<string, mixed> $messages The messages to send
     * @param float $temperature The temperature to use (0.0 to 1.0)
     * @return string The response content
     */
    public function chatCompletion(string $model, array $messages, float $temperature = 0.1): string;
} 