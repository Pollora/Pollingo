<?php

declare(strict_types=1);

namespace Pollora\Pollingo\Services;

use OpenAI\Client;
use OpenAI\Factory;
use Pollora\Pollingo\Contracts\Translator;
use Pollora\Pollingo\DTO\TranslationGroup;
use Pollora\Pollingo\DTO\TranslationString;
use RuntimeException;

/**
 * @template TKey of string
 *
 * @implements Translator<TKey>
 */
final class OpenAITranslator implements Translator
{
    private readonly Client $client;

    private readonly LanguageCodeService $languageCodeService;

    public function __construct(
        string $apiKey,
        private readonly string $model = 'gpt-4',
    ) {
        $this->client = (new Factory())->withApiKey($apiKey)->make();
        $this->languageCodeService = new LanguageCodeService();
    }

    /**
     * @param  array<string, TranslationGroup<TKey>>  $groups
     * @return array<string, TranslationGroup<TKey>>
     */
    public function translate(array $groups, string $targetLanguage, ?string $sourceLanguage = null, ?string $globalContext = null): array
    {
        $targetLanguageName = $this->languageCodeService->getLanguageName($targetLanguage);
        $sourceLanguageName = $sourceLanguage ? $this->languageCodeService->getLanguageName($sourceLanguage) : null;

        /** @var array<string, TranslationGroup<TKey>> */
        $result = [];

        foreach ($groups as $groupName => $group) {
            $strings = [];
            foreach ($group->getStrings() as $key => $string) {
                $strings[$key] = [
                    'text' => $string->getText(),
                    'context' => $string->getContext(),
                ];
            }

            $response = $this->client->chat()->create([
                'model' => $this->model,
                'temperature' => 0.1,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $this->getSystemPrompt(),
                    ],
                    [
                        'role' => 'user',
                        'content' => $this->buildMessage(
                            strings: $strings,
                            targetLanguage: $targetLanguageName,
                            sourceLanguage: $sourceLanguageName,
                            globalContext: $globalContext,
                        ),
                    ],
                ],
            ]);

            $content = $response->choices[0]->message->content;
            if ($content === null) {
                throw new RuntimeException('Empty response from OpenAI API');
            }

            $translations = json_decode($content, true);

            if (! is_array($translations)) {
                throw new RuntimeException('Invalid JSON in response');
            }

            // Verify that translations are different from the original text
            foreach ($translations as $key => $translation) {
                if (! isset($strings[$key])) {
                    throw new RuntimeException(sprintf(
                        'Missing translation for key: %s. Available keys: %s',
                        $key,
                        implode(', ', array_keys($strings)),
                    ));
                }

                if (! is_string($translation)) {
                    throw new RuntimeException("Invalid translation for key '{$key}': expected string, got ".gettype($translation));
                }

                if (mb_strtolower($translation) === mb_strtolower($strings[$key]['text'])) {
                    throw new RuntimeException("Translation for '{$key}' is the same as the original text");
                }
            }

            // Verify all strings are translated
            foreach ($strings as $key => $string) {
                if (! isset($translations[$key])) {
                    throw new RuntimeException(sprintf(
                        'Missing translation for key: %s. Available translations: %s',
                        $key,
                        implode(', ', array_keys($translations)),
                    ));
                }
            }

            /** @var array<TKey, TranslationString> */
            $translatedStrings = [];
            foreach ($translations as $key => $translation) {
                /** @var TKey */
                $typedKey = $key;
                $translatedStrings[$typedKey] = new TranslationString(
                    text: $strings[$key]['text'],
                    translatedText: $translation,
                    context: $strings[$key]['context'],
                );
            }

            $result[$groupName] = new TranslationGroup($groupName, $translatedStrings);
        }

        return $result;
    }

    /**
     * @param  array<string, array{text: string, context: string|null}>  $strings
     */
    private function buildMessage(array $strings, string $targetLanguage, ?string $sourceLanguage, ?string $globalContext): string
    {
        $message = 'Translate the following strings';

        if ($sourceLanguage) {
            $message .= " from {$sourceLanguage}";
        }

        $message .= " to {$targetLanguage}:";

        if ($globalContext) {
            $message .= "\nGlobal context: {$globalContext}";
        }

        foreach ($strings as $key => $string) {
            $message .= sprintf(
                "\n- %s: \"%s\"%s",
                $key,
                $string['text'],
                $string['context'] ? sprintf(' (context: %s)', $string['context']) : '',
            );
        }

        $message .= "\n\nReturn ONLY a JSON object with the translations, no other text.";

        return $message;
    }

    private function getSystemPrompt(): string
    {
        return <<<'PROMPT'
You are a professional translator with expertise in multiple languages.
Your task is to translate text while preserving meaning and context.

Important rules to follow:
1. Always translate the text to the target language, never return it unchanged
2. Preserve the meaning and context of each string
3. Use appropriate translations based on context
4. Return ONLY a valid JSON object with translations, nothing else
5. Each key in the JSON must be exactly as provided in the input
6. Never return the original text unchanged
7. Your response must be a valid JSON object, starting with { and ending with }

Example request:
```
Translate to French:
- greeting: "Hello"
- action: "Save"
```

Example response:
{
    "greeting": "Bonjour",
    "action": "Sauvegarder"
}

Common translations from English to French:
- "Hello" → "Bonjour" or "Salut"
- "Save" → "Sauvegarder" or "Enregistrer"
- "Welcome" → "Bienvenue"
- "Error occurred" → "Une erreur est survenue"
- "Cancel" → "Annuler"
- "Success" → "Succès"
- "Operation completed" → "Opération terminée"

IMPORTANT: Return ONLY the JSON object, no other text or explanations.
PROMPT;
    }
}
