<?php

declare(strict_types=1);

namespace Pollora\Pollingo\Tests\Traits;

trait HasOpenAIConfig
{
    protected function getOpenAIKey(): ?string
    {
        return $_ENV['OPENAI_API_KEY'] ?? null;
    }

    protected function getOpenAIModel(): string
    {
        return $_ENV['OPENAI_MODEL'] ?? 'gpt-4';
    }

    protected function hasOpenAIKey(): bool
    {
        $key = $this->getOpenAIKey();

        return $key !== null && $key !== '';
    }

    protected function skipIfNoOpenAIKey(): void
    {
        if (! $this->hasOpenAIKey()) {
            $this->markTestSkipped('OpenAI API key not available');
        }
    }
} 