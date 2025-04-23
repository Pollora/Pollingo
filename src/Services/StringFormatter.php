<?php

declare(strict_types=1);

namespace Pollora\Pollingo\Services;

use Pollora\Pollingo\DTO\TranslationString;

/**
 * Service responsible for formatting strings for translation prompts.
 */
final class StringFormatter
{
    /**
     * Format strings for the translation prompt.
     *
     * @param  array<string, array{text: string, context: string|null}>  $strings
     * @return string
     */
    public function formatStringsForPrompt(array $strings): string
    {
        $formattedStrings = '';
        
        foreach ($strings as $key => $string) {
            $formattedStrings .= sprintf(
                "\n- %s: \"%s\"%s",
                $key,
                $string['text'],
                $string['context'] ? sprintf(' (context: %s)', $string['context']) : '',
            );
        }
        
        return $formattedStrings;
    }
    
    /**
     * Build the complete message for the OpenAI API.
     *
     * @param  array<string, array{text: string, context: string|null}>  $strings
     * @param  string  $targetLanguage
     * @param  string|null  $sourceLanguage
     * @param  string|null  $globalContext
     * @return string
     */
    public function buildMessage(
        array $strings, 
        string $targetLanguage, 
        ?string $sourceLanguage, 
        ?string $globalContext
    ): string {
        $message = 'Translate the following strings';

        if ($sourceLanguage) {
            $message .= " from {$sourceLanguage}";
        }

        $message .= " to {$targetLanguage}:";

        if ($globalContext) {
            $message .= "\nGlobal context: {$globalContext}";
        }

        $message .= $this->formatStringsForPrompt($strings);
        $message .= "\n\nReturn the translations using the following format for each key:";
        $message .= "\n[KEY:key_name]translated_text[/KEY]";
        $message .= "\n\nExample:";
        $message .= "\n[KEY:greeting]Bonjour[/KEY]";
        $message .= "\n[KEY:action]Sauvegarder[/KEY]";
        $message .= "\n\nIMPORTANT: Return ONLY the translations in this format, no other text or explanations.";

        return $message;
    }
} 