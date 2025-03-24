<?php

declare(strict_types=1);

use Pest\Expectation;
use Pollora\Pollingo\Pollingo;
use Pollora\Pollingo\Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

uses(TestCase::class)->in('Feature', 'Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

/**
 * @template T
 *
 * @param  Expectation<T>  $expectation
 * @return Expectation<T>
 */
function toBeTranslatable(Expectation $expectation): Expectation
{
    return $expectation->toBeInstanceOf(Pollingo::class);
}

/**
 * @template T
 *
 * @param  Expectation<T>  $expectation
 * @return Expectation<T>
 */
function toBeTranslatedTo(Expectation $expectation, string $expectedLanguage, string $original): Expectation
{
    $value = $expectation->value;

    // Check that the value is a string
    expect($value)->toBeString();

    // Check that the translation is not empty
    expect($value)->not->toBeEmpty();

    // Normalize strings by trimming whitespace and converting to lowercase
    $normalizedValue = mb_strtolower(trim((string) $value));
    $normalizedOriginal = mb_strtolower(trim($original));

    // Check that the translation is different from the original
    if ($normalizedValue === $normalizedOriginal) {
        throw new Exception(
            "Expected '{$value}' to be different from '{$original}'"
        );
    }

    // For French, check against known translations
    if ($expectedLanguage === 'fr') {
        $knownTranslations = [
            'hello' => ['bonjour', 'salut'],
            'world' => ['monde'],
            'welcome' => ['bienvenue'],
            'save' => ['enregistrer', 'sauvegarder'],
            'cancel' => ['annuler'],
            'operation completed successfully' => ['opération réussie', 'opération terminée avec succès'],
            'an error occurred' => ['une erreur est survenue', 'une erreur s\'est produite'],
        ];

        $originalKey = mb_strtolower($original);
        if (isset($knownTranslations[$originalKey])) {
            $validTranslations = array_map('mb_strtolower', $knownTranslations[$originalKey]);
            if (! in_array($normalizedValue, $validTranslations, true)) {
                throw new Exception(
                    "Expected '{$value}' to be one of: ".implode(', ', $knownTranslations[$originalKey])
                );
            }
        }
    }

    return $expectation;
}

expect()->extend('toBeTranslatable', toBeTranslatable(...));
expect()->extend('toBeTranslatedTo', toBeTranslatedTo(...));

expect()->extend('toBeTranslatedTo', function (string $targetLanguage, string $originalText) {
    expect($this->value)
        ->toBeString()
        ->not->toBe($originalText)
        ->and($this->value)->not->toBeEmpty();

    return $this;
});
