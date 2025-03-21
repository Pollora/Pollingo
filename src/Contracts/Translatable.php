<?php

declare(strict_types=1);

namespace Pollora\Pollingo\Contracts;

interface Translatable
{
    /**
     * Get the text to be translated.
     */
    public function getText(): string;

    /**
     * Get the context for translation, if any.
     */
    public function getContext(): ?string;

    /**
     * Set the translated text.
     */
    public function setTranslatedText(string $text): self;
}
