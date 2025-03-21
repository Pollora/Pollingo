<?php

declare(strict_types=1);

namespace Pollora\Pollingo\DTO;

/**
 * @template TKey of string
 */
final readonly class TranslationGroup
{
    /**
     * @param  array<TKey, TranslationString>  $strings
     */
    public function __construct(
        private string $name,
        private array $strings,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array<TKey, TranslationString>
     */
    public function getStrings(): array
    {
        return $this->strings;
    }

    /**
     * @return array<TKey, string>
     */
    public function toArray(): array
    {
        $result = [];
        foreach ($this->strings as $key => $string) {
            $result[$key] = $string->getTranslatedText() ?? $string->getText();
        }

        return $result;
    }
}
