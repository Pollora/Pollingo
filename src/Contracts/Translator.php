<?php

declare(strict_types=1);

namespace Pollora\Pollingo\Contracts;

use Pollora\Pollingo\DTO\TranslationGroup;

/**
 * @template TKey of string
 */
interface Translator
{
    /**
     * @param  array<string, TranslationGroup<TKey>>  $groups
     * @return array<string, TranslationGroup<TKey>>
     */
    public function translate(
        array $groups,
        string $targetLanguage,
        ?string $sourceLanguage = null,
        ?string $globalContext = null,
    ): array;
}
