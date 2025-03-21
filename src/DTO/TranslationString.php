<?php

declare(strict_types=1);

namespace Pollora\Pollingo\DTO;

use Pollora\Pollingo\Contracts\Translatable;

final readonly class TranslationString implements Translatable
{
    public function __construct(
        private string $text,
        private ?string $context = null,
        private ?string $translatedText = null,
    ) {}

    public function getText(): string
    {
        return $this->text;
    }

    public function getContext(): ?string
    {
        return $this->context;
    }

    public function setTranslatedText(string $text): self
    {
        return new self(
            text: $this->text,
            context: $this->context,
            translatedText: $text,
        );
    }

    public function getTranslatedText(): ?string
    {
        return $this->translatedText;
    }
}
