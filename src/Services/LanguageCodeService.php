<?php

declare(strict_types=1);

namespace Pollora\Pollingo\Services;

use InvalidArgumentException;

final class LanguageCodeService
{
    /**
     * @var array<string, string>
     */
    private readonly array $languageCodes;

    public function __construct()
    {
        $configPath = dirname(__DIR__, 2).'/config/language-codes.php';

        if (! file_exists($configPath)) {
            throw new InvalidArgumentException('Language codes configuration file not found');
        }

        $this->languageCodes = require $configPath;
    }

    public function isValid(string $code): bool
    {
        return isset($this->languageCodes[mb_strtolower($code)]);
    }

    public function getLanguageName(string $code): string
    {
        $code = mb_strtolower($code);

        if (! $this->isValid($code)) {
            throw new InvalidArgumentException("Invalid language code: {$code}");
        }

        return $this->languageCodes[$code];
    }

    /**
     * @return array<string, string>
     */
    public function getAllLanguages(): array
    {
        return $this->languageCodes;
    }
}
