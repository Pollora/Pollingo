<?php

declare(strict_types=1);

namespace Pollora\Pollingo\Services;

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

    public function __construct(
        protected readonly string $model,
        protected readonly int $timeout = 120 // default timeout in seconds
    ) {
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