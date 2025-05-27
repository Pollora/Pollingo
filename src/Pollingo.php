<?php

declare(strict_types=1);

namespace Pollora\Pollingo;

use InvalidArgumentException;
use Pollora\Pollingo\Contracts\Translator;
use Pollora\Pollingo\DTO\TranslationGroup;
use Pollora\Pollingo\DTO\TranslationString;
use Pollora\Pollingo\Exceptions\MissingTargetLanguageException;
use Pollora\Pollingo\Services\LanguageCodeService;
use Pollora\Pollingo\Services\OpenAITranslator;

/**
 * @template TKey of string
 */
final class Pollingo
{
    /** @var array<string, TranslationGroup<TKey>> */
    private array $groups = [];

    private ?string $sourceLanguage = null;

    private ?string $targetLanguage = null;

    private ?string $globalContext = null;

    private ?string $singleText = null;

    private readonly LanguageCodeService $languageCodeService;

    /**
     * @param  Translator<TKey>  $translator
     */
    private function __construct(
        private readonly Translator $translator,
    ) {
        $this->languageCodeService = new LanguageCodeService();
    }

    /**
     * @template T of string
     *
     * @param  Translator<T>|null  $translator
     * @return self<T>
     */
    public static function make(?string $apiKey = null, string $model = 'gpt-4_1-2025-04-14', ?Translator $translator = null): self
    {
        // Ensure apiKey is a valid string, even if empty
        $apiKey = $apiKey ?? '';

        // Only throw exception if both apiKey is empty AND translator is null
        // This allows for blank string apiKeys if they're intentionally set that way in config
        if (($apiKey === '' || $apiKey === null) && $translator === null) {
            throw new InvalidArgumentException('Either apiKey or translator must be provided');
        }

        if ($translator !== null) {
            return new self($translator);
        }

        /** @var OpenAITranslator<T> */
        $defaultTranslator = new OpenAITranslator($apiKey, $model);

        /** @var self<T> */
        return new self($defaultTranslator);
    }

    /**
     * @template T of string
     *
     * @param  Translator<T>  $translator
     * @return self<T>
     */
    public function withTranslator(Translator $translator): self
    {
        return new self($translator);
    }

    /**
     * Set the AI model to use for translation.
     *
     * @param string $model The model identifier (e.g. 'gpt-4', 'gpt-4.1-nano')
     * @return self<TKey>
     */
    public function model(string $model): self
    {
        if ($this->translator instanceof OpenAITranslator) {
            $this->translator->setModel($model);
        }

        return $this;
    }

    /**
     * Set the timeout for API requests.
     *
     * @param int $timeout Timeout in seconds
     * @return self<TKey>
     */
    public function timeout(int $timeout): self
    {
        if ($timeout <= 0) {
            throw new \InvalidArgumentException('Timeout must be greater than 0');
        }

        if ($this->translator instanceof OpenAITranslator) {
            $this->translator->setTimeout($timeout);

            // Try to update the HTTP client's timeout if possible
            try {
                $reflection = new \ReflectionClass($this->translator);
                $clientProperty = $reflection->getProperty('client');
                $clientProperty->setAccessible(true);
                $client = $clientProperty->getValue($this->translator);

                $clientReflection = new \ReflectionClass($client);
                $httpClientProperty = $clientReflection->getProperty('httpClient');

                if ($httpClientProperty) {
                    $httpClientProperty->setAccessible(true);
                    $httpClient = $httpClientProperty->getValue($client);

                    $httpClientReflection = new \ReflectionClass($httpClient);
                    $configProperty = $httpClientReflection->getProperty('config');

                    if ($configProperty) {
                        $configProperty->setAccessible(true);
                        $config = $configProperty->getValue($httpClient);

                        if (is_array($config) && isset($config['timeout'])) {
                            $config['timeout'] = $timeout;
                            $configProperty->setValue($httpClient, $config);
                        }
                    }
                }
            } catch (\Exception $e) {
                // Silently continue if we can't update the HTTP client timeout
            }
        }

        return $this;
    }

    /**
     * Set the maximum number of retries for failed API requests.
     *
     * @param int $maxRetries Maximum number of retries
     * @return self<TKey>
     */
    public function maxRetries(int $maxRetries): self
    {
        if ($maxRetries < 0) {
            throw new \InvalidArgumentException('Maximum retries must be 0 or greater');
        }

        if ($this->translator instanceof OpenAITranslator) {
            $this->translator->setMaxRetries($maxRetries);
        }

        return $this;
    }

    /**
     * Set the delay between retries for failed API requests.
     *
     * @param int $retryDelay Delay in milliseconds
     * @return self<TKey>
     */
    public function retryDelay(int $retryDelay): self
    {
        if ($retryDelay < 0) {
            throw new \InvalidArgumentException('Retry delay must be 0 or greater');
        }

        if ($this->translator instanceof OpenAITranslator) {
            $this->translator->setRetryDelay($retryDelay);
        }

        return $this;
    }

    /**
     * @return self<TKey>
     */
    public function from(string $language): self
    {
        if (! $this->languageCodeService->isValid($language)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid source language code: %s. Available languages: %s',
                $language,
                implode(', ', array_keys($this->languageCodeService->getAllLanguages()))
            ));
        }

        $this->sourceLanguage = mb_strtolower($language);

        return $this;
    }

    /**
     * @return self<TKey>
     */
    public function to(string $language): self
    {
        if (! $this->languageCodeService->isValid($language)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid target language code: %s. Available languages: %s',
                $language,
                implode(', ', array_keys($this->languageCodeService->getAllLanguages()))
            ));
        }

        $this->targetLanguage = mb_strtolower($language);

        return $this;
    }

    /**
     * @return self<TKey>
     */
    public function withGlobalContext(string $context): self
    {
        $this->globalContext = $context;

        return $this;
    }

    /**
     * @return self<TKey>
     */
    public function text(?string $text): self
    {
        $this->singleText = $text;
        return $this;
    }

    /**
     * @return self<TKey>
     */
    public function context(string $context): self
    {
        return $this->withGlobalContext($context);
    }

    /**
     * @param  array<TKey, string|array{text: string, context?: string}>  $strings
     * @return self<TKey>
     */
    public function group(string $name, array $strings): self
    {
        $translationStrings = [];
        
        foreach ($strings as $key => $value) {
            if (is_array($value)) {
                $translationStrings[$key] = new TranslationString(
                    text: $value['text'],
                    context: $value['context'] ?? null,
                );

                continue;
            }

            $translationStrings[$key] = new TranslationString(text: $value);
        }

        $this->groups[$name] = new TranslationGroup($name, $translationStrings);

        return $this;
    }

    /**
     * @return array<string, array<TKey, string>>|string
     *
     * @throws MissingTargetLanguageException
     */
    public function translate(): array|string
    {
        if (! $this->targetLanguage) {
            throw new MissingTargetLanguageException('Target language must be set before translating');
        }

        // Handle single text translation
        if ($this->singleText !== null) {
            $this->group('single', ['text' => $this->singleText]);
            $result = $this->translator->translate(
                groups: $this->groups,
                targetLanguage: $this->targetLanguage,
                sourceLanguage: $this->sourceLanguage,
                globalContext: $this->globalContext,
            );

            return $result['single']->getStrings()['text']->getTranslatedText();
        }

        $translatedGroups = $this->translator->translate(
            groups: $this->groups,
            targetLanguage: $this->targetLanguage,
            sourceLanguage: $this->sourceLanguage,
            globalContext: $this->globalContext,
        );

        $result = [];
        foreach ($translatedGroups as $groupName => $group) {
            $result[$groupName] = $group->toArray();
        }

        return $result;
    }
}
