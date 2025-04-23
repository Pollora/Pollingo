<?php

declare(strict_types=1);

namespace Pollora\Pollingo\Services;

use Pollora\Pollingo\DTO\TranslationGroup;
use Pollora\Pollingo\DTO\TranslationString;
use RuntimeException;

/**
 * Service responsible for parsing translation responses from OpenAI.
 * 
 * @template TKey of string
 */
final class TranslationResponseParser
{
    /**
     * Parse the OpenAI response and create a TranslationGroup.
     *
     * @param  string  $content
     * @param  string  $groupName
     * @param  array<string, array{text: string, context: string|null}>  $strings
     * @return TranslationGroup<TKey>
     * @throws RuntimeException
     */
    public function parseResponse(string $content, string $groupName, array $strings): TranslationGroup
    {
        // Extract translations using regex pattern
        $pattern = '/\[KEY:([^\]]+)\](.*?)\[\/KEY\]/s';
        $matches = [];
        
        if (!preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
            throw new RuntimeException('Invalid response format. Expected [KEY:key_name]translation[/KEY] format.');
        }
        
        $translations = [];
        foreach ($matches as $match) {
            $key = $match[1];
            $translation = $match[2];
            $translations[$key] = $translation;
        }
        
        // Verify that all required keys are present
        foreach ($strings as $key => $string) {
            if (!isset($translations[$key])) {
                throw new RuntimeException(sprintf(
                    'Missing translation for key: %s. Available translations: %s',
                    $key,
                    implode(', ', array_keys($translations)),
                ));
            }
        }
        
        // Verify that no extra keys are present
        foreach ($translations as $key => $translation) {
            if (!isset($strings[$key])) {
                throw new RuntimeException(sprintf(
                    'Unexpected translation key: %s. Available keys: %s',
                    $key,
                    implode(', ', array_keys($strings)),
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
        
        return new TranslationGroup($groupName, $translatedStrings);
    }
} 