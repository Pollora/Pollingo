<?php

declare(strict_types=1);

namespace Pollora\Pollingo\Tests\Unit;

use InvalidArgumentException;
use Mockery;
use Mockery\LegacyMockInterface;
use Mockery\MockInterface;
use Pollora\Pollingo\Contracts\Translator;
use Pollora\Pollingo\DTO\TranslationGroup;
use Pollora\Pollingo\DTO\TranslationString;
use Pollora\Pollingo\Exceptions\MissingTargetLanguageException;
use Pollora\Pollingo\Pollingo;

beforeEach(function () {
    /** @var MockInterface&LegacyMockInterface&Translator<string> */
    $this->translator = Mockery::mock(Translator::class);
});

test('it can be instantiated with an API key', function () {
    /** @var Pollingo<string> */
    $pollingo = Pollingo::make('fake-api-key');
    expect($pollingo)->toBeInstanceOf(Pollingo::class);
});

test('it can be instantiated with a custom translator', function () {
    /** @var Pollingo<string> */
    $pollingo = Pollingo::make(translator: $this->translator);
    expect($pollingo)->toBeInstanceOf(Pollingo::class);
});

test('it throws an exception when neither API key nor translator is provided', function () {
    expect(fn () => Pollingo::make(''))->toThrow(InvalidArgumentException::class);
});

test('it requires target language to be set', function () {
    /** @var Pollingo<string> */
    $pollingo = Pollingo::make('fake-api-key')
        ->group('test', ['key' => 'value']);

    expect(fn () => $pollingo->translate())->toThrow(MissingTargetLanguageException::class);
});

test('it validates language codes', function () {
    /** @var Pollingo<string> */
    $pollingo = Pollingo::make('fake-api-key');

    expect(fn () => $pollingo->from('invalid'))->toThrow(InvalidArgumentException::class);
    expect(fn () => $pollingo->to('invalid'))->toThrow(InvalidArgumentException::class);
});

test('it groups strings correctly', function () {
    /** @var MockInterface&LegacyMockInterface&Translator<string> */
    $mockTranslator = Mockery::mock(Translator::class);
    $mockTranslator->expects('translate')
        ->withArgs(function (array $groups, string $targetLanguage, ?string $sourceLanguage, ?string $globalContext) {
            return $targetLanguage === 'fr'
                && $sourceLanguage === 'en'
                && $globalContext === null;
        })
        ->andReturn([
            'test' => new TranslationGroup('test', [
                'hello' => new TranslationString('Hello', translatedText: 'Bonjour'),
                'world' => new TranslationString('World', translatedText: 'Monde'),
            ]),
        ]);

    /** @var Pollingo<string> */
    $pollingo = Pollingo::make(translator: $mockTranslator);

    $translations = $pollingo
        ->from('en')
        ->to('fr')
        ->group('test', [
            'hello' => 'Hello',
            'world' => 'World',
        ])
        ->translate();

    expect($translations)
        ->toBeArray()
        ->toHaveKey('test')
        ->and($translations['test'])
        ->toHaveKeys(['hello', 'world']);
});

test('it applies global context', function () {
    /** @var MockInterface&LegacyMockInterface&Translator<string> */
    $mockTranslator = Mockery::mock(Translator::class);
    $mockTranslator->expects('translate')
        ->withArgs(function (array $groups, string $targetLanguage, ?string $sourceLanguage, ?string $globalContext) {
            return $targetLanguage === 'fr'
                && $sourceLanguage === 'en'
                && $globalContext === 'This is a test context';
        })
        ->andReturn([
            'test' => new TranslationGroup('test', [
                'hello' => new TranslationString('Hello', translatedText: 'Bonjour'),
            ]),
        ]);

    /** @var Pollingo<string> */
    $pollingo = Pollingo::make(translator: $mockTranslator);

    $translations = $pollingo
        ->from('en')
        ->to('fr')
        ->withGlobalContext('This is a test context')
        ->group('test', [
            'hello' => 'Hello',
        ])
        ->translate();

    expect($translations)
        ->toBeArray()
        ->toHaveKey('test')
        ->and($translations['test'])
        ->toHaveKey('hello');
});

test('it can translate a single text', function () {
    /** @var MockInterface&LegacyMockInterface&Translator<string> */
    $mockTranslator = Mockery::mock(Translator::class);
    $mockTranslator->expects('translate')
        ->withArgs(function (array $groups, string $targetLanguage, ?string $sourceLanguage, ?string $globalContext) {
            return $targetLanguage === 'fr'
                && $sourceLanguage === 'en'
                && $globalContext === null
                && isset($groups['single'])
                && $groups['single']->getStrings()['text']->getText() === 'Hello';
        })
        ->andReturn([
            'single' => new TranslationGroup('single', [
                'text' => new TranslationString('Hello', translatedText: 'Bonjour'),
            ]),
        ]);

    /** @var Pollingo<string> */
    $pollingo = Pollingo::make(translator: $mockTranslator);

    $translation = $pollingo
        ->from('en')
        ->to('fr')
        ->text('Hello')
        ->translate();

    expect($translation)->toBe('Bonjour');
});

test('it can translate a single text with context', function () {
    /** @var MockInterface&LegacyMockInterface&Translator<string> */
    $mockTranslator = Mockery::mock(Translator::class);
    $mockTranslator->expects('translate')
        ->withArgs(function (array $groups, string $targetLanguage, ?string $sourceLanguage, ?string $globalContext) {
            return $targetLanguage === 'fr'
                && $sourceLanguage === 'en'
                && $globalContext === 'Greeting message'
                && isset($groups['single'])
                && $groups['single']->getStrings()['text']->getText() === 'Hello';
        })
        ->andReturn([
            'single' => new TranslationGroup('single', [
                'text' => new TranslationString('Hello', translatedText: 'Bonjour'),
            ]),
        ]);

    /** @var Pollingo<string> */
    $pollingo = Pollingo::make(translator: $mockTranslator);

    $translation = $pollingo
        ->from('en')
        ->to('fr')
        ->text('Hello')
        ->context('Greeting message')
        ->translate();

    expect($translation)->toBe('Bonjour');
});
