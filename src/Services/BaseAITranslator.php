<?php

declare(strict_types=1);

namespace Pollora\Pollingo\Services;

use InvalidArgumentException;
use Pollora\Pollingo\Contracts\Translator;
use Pollora\Pollingo\DTO\TranslationGroup;

/**
 * @template TKey of string
 *
 * @implements Translator<TKey>
 */
abstract class BaseAITranslator implements Translator
{
    protected readonly LanguageCodeService $languageCodeService;
    protected readonly StringFormatter $stringFormatter;
    protected readonly TranslationResponseParser $responseParser;

    /** @var string The AI model to use */
    protected string $model;
    
    /** @var int Timeout in seconds */
    protected int $timeout;
    
    /** @var int Maximum number of retries */
    protected int $maxRetries;
    
    /** @var int Delay between retries in milliseconds */
    protected int $retryDelay;

    public function __construct(
        string $model = 'gpt-4_1-2025-04-14',
        int $timeout = 120, // default timeout in seconds
        int $maxRetries = 3, // default number of retries
        int $retryDelay = 1000 // default delay between retries in milliseconds
    ) {
        $this->model = $model;
        $this->timeout = $timeout;
        $this->maxRetries = $maxRetries;
        $this->retryDelay = $retryDelay;
        
        $this->languageCodeService = new LanguageCodeService();
        $this->stringFormatter = new StringFormatter();
        $this->responseParser = new TranslationResponseParser();
    }

    /**
     * @param  array<string, TranslationGroup<TKey>>  $groups
     * @return array<string, TranslationGroup<TKey>>
     */
    abstract public function translate(array $groups, string $targetLanguage, ?string $sourceLanguage = null, ?string $globalContext = null): array;

    /**
     * Set the AI model to use for translation.
     * 
     * @param string $model The AI model to use
     * @return self
     */
    public function setModel(string $model): self
    {
        $this->model = $model;
        return $this;
    }
    
    /**
     * Set the timeout for API requests.
     * 
     * @param int $timeout Timeout in seconds
     * @return self
     * @throws InvalidArgumentException If timeout is <= 0
     */
    public function setTimeout(int $timeout): self
    {
        if ($timeout <= 0) {
            throw new InvalidArgumentException('Timeout must be greater than 0');
        }
        
        $this->timeout = $timeout;
        return $this;
    }
    
    /**
     * Set the maximum number of retries for failed API requests.
     * 
     * @param int $maxRetries Maximum number of retries
     * @return self
     * @throws InvalidArgumentException If maxRetries is < 0
     */
    public function setMaxRetries(int $maxRetries): self
    {
        if ($maxRetries < 0) {
            throw new InvalidArgumentException('Maximum retries must be 0 or greater');
        }
        
        $this->maxRetries = $maxRetries;
        return $this;
    }
    
    /**
     * Set the delay between retries for failed API requests.
     * 
     * @param int $retryDelay Delay in milliseconds
     * @return self
     * @throws InvalidArgumentException If retryDelay is < 0
     */
    public function setRetryDelay(int $retryDelay): self
    {
        if ($retryDelay < 0) {
            throw new InvalidArgumentException('Retry delay must be 0 or greater');
        }
        
        $this->retryDelay = $retryDelay;
        return $this;
    }
    
    /**
     * Get the current model.
     * 
     * @return string
     */
    public function getModel(): string
    {
        return $this->model;
    }
    
    /**
     * Get the current timeout.
     * 
     * @return int Timeout in seconds
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }
    
    /**
     * Get the maximum number of retries.
     * 
     * @return int
     */
    public function getMaxRetries(): int
    {
        return $this->maxRetries;
    }
    
    /**
     * Get the delay between retries.
     * 
     * @return int Delay in milliseconds
     */
    public function getRetryDelay(): int
    {
        return $this->retryDelay;
    }

    /**
     * Get the system prompt for the AI translator.
     * This method can be overridden by child classes to provide a custom prompt.
     */
    protected function getSystemPrompt(): string
    {
        return <<<'PROMPT'
You are a professional translator with expertise in multiple languages.
Your task is to translate text while preserving meaning and context.

Important rules to follow:
1. Always translate the text to the target language, never return it unchanged
2. Preserve the meaning and context of each string
3. Use appropriate translations based on context
4. Return translations using the following format for each key: [KEY:key_name]translated_text[/KEY]
5. Each key in the response must be exactly as provided in the input
6. Do not include any JSON format, Markdown or code block syntax in your response

Example request:
Translate to French:
- greeting: "Hello"
- action: "Save"

Example response:
[KEY:greeting]Bonjour[/KEY]
[KEY:action]Sauvegarder[/KEY]

IMPORTANT: Return ONLY the translations in this format, no other text, explanations, or JSON syntax.
PROMPT;
    }
} 