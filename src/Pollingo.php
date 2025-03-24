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
    public static function make(string $apiKey = '', string $model = 'gpt-4o', ?Translator $translator = null): self
    {
        if ($apiKey === '' && $translator === null) {
            throw new InvalidArgumentException('Either apiKey or translator must be provided');
        }

        if ($translator !== null) {
            return new self($translator);
        }

        /** @var OpenAITranslator<T> */
        $defaultTranslator = new OpenAITranslator($apiKey ?? '', $model);

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
    public function text(string $text): self
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
