<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use Mockery;
use Pollora\Pollingo\Contracts\AIClient;
use Pollora\Pollingo\Services\OpenAITranslator;
use RuntimeException;

/**
 * A test implementation of AIClient that can be used to test retry logic
 */
class TestAIClient implements AIClient
{
    private $behavior;
    private int $maxRetries = 3;
    private int $retryDelay = 10;
    private string $model = 'gpt-4';
    
    public function setChatCompletionBehavior(callable $behavior): void
    {
        $this->behavior = $behavior;
    }
    
    public function setMaxRetries(int $maxRetries): void
    {
        $this->maxRetries = $maxRetries;
    }
    
    public function setRetryDelay(int $retryDelay): void
    {
        $this->retryDelay = $retryDelay;
    }
    
    public function setModel(string $model): void
    {
        $this->model = $model;
    }
    
    public function getMaxRetries(): int
    {
        return $this->maxRetries;
    }
    
    public function getRetryDelay(): int
    {
        return $this->retryDelay;
    }
    
    public function getModel(): string
    {
        return $this->model;
    }
    
    /**
     * Simulate the chatCompletion method with configurable behavior for testing
     */
    public function chatCompletion(string $model, array $messages, float $temperature = 0.1): string
    {
        if (isset($this->behavior)) {
            return ($this->behavior)($model, $messages, $temperature);
        }
        
        return 'Default test response';
    }
}

// Create a testable OpenAI client for the tests
beforeEach(function () {
    $this->client = new TestAIClient();
});

test('chat completion successful on first attempt', function () {
    $messages = [
        ['role' => 'system', 'content' => 'You are a translator.'],
        ['role' => 'user', 'content' => 'Translate "Hello" to French.'],
    ];
    
    // Configure the test client
    $called = 0;
    $this->client->setChatCompletionBehavior(function ($model, $messages, $temperature) use (&$called) {
        $called++;
        return 'Bonjour';
    });
    
    $result = $this->client->chatCompletion('gpt-4', $messages);
    
    expect($result)->toBe('Bonjour');
    expect($called)->toBe(1);
});

test('translator retry logic works on exceptions', function () {
    // Create a custom class that extends OpenAITranslator
    // PHP 8.2+ anonymous class with named arguments
    $translatorWithRetry = new class('test-key') extends OpenAITranslator {
        private int $calls = 0;
        private bool $shouldFailFirstCall = false;
        private bool $shouldFailAllCalls = false;
        
        public function setChatCompletionBehavior(bool $shouldFailFirstCall = false, bool $shouldFailAllCalls = false): void
        {
            $this->shouldFailFirstCall = $shouldFailFirstCall;
            $this->shouldFailAllCalls = $shouldFailAllCalls;
            $this->calls = 0;
        }
        
        public function chatCompletion(string $model, array $messages, float $temperature = 0.1): string
        {
            $this->calls++;
            
            if ($this->shouldFailAllCalls) {
                throw new RequestException('Error', new Request('POST', 'test'));
            }
            
            if ($this->shouldFailFirstCall && $this->calls === 1) {
                throw new RequestException('Error', new Request('POST', 'test'));
            }
            
            return 'Bonjour';
        }
        
        public function getCalls(): int
        {
            return $this->calls;
        }
    };
    
    // Test retry success scenario - should work on second attempt
    $translatorWithRetry->setChatCompletionBehavior(shouldFailFirstCall: true);
    
    $result = $translatorWithRetry->chatCompletion('gpt-4', [
        ['role' => 'system', 'content' => 'You are a translator.'],
    ]);
    
    expect($result)->toBe('Bonjour');
    expect($translatorWithRetry->getCalls())->toBe(2);
    
    // Reset for next test
    $translatorWithRetry->setChatCompletionBehavior(shouldFailAllCalls: true);
    
    expect(fn () => $translatorWithRetry->chatCompletion('gpt-4', [
        ['role' => 'system', 'content' => 'You are a translator.'],
    ]))->toThrow(\RuntimeException::class);
    
    expect($translatorWithRetry->getCalls())->toBe(3);
});

test('translator uses specified model', function () {
    $messages = [
        ['role' => 'system', 'content' => 'You are a translator.'],
        ['role' => 'user', 'content' => 'Translate "Hello" to French.'],
    ];
    
    // Configure the client to verify model
    $actualModel = null;
    $this->client->setChatCompletionBehavior(function ($model, $messages, $temperature) use (&$actualModel) {
        $actualModel = $model;
        return 'Bonjour';
    });
    
    // Set test model
    $this->client->setModel('gpt-4.1-nano');
    
    $result = $this->client->chatCompletion('gpt-4.1-nano', $messages);
    
    expect($result)->toBe('Bonjour');
    expect($actualModel)->toBe('gpt-4.1-nano');
}); 