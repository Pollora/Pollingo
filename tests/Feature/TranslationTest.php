<?php

declare(strict_types=1);

use Pollora\Pollingo\Contracts\Translator;
use Pollora\Pollingo\DTO\TranslationGroup;
use Pollora\Pollingo\DTO\TranslationString;
use Pollora\Pollingo\Pollingo;

uses()->group('feature');

beforeEach(function () {
    $this->mockTranslator = new class implements Translator {
        public function translate(array $groups, string $targetLanguage, ?string $sourceLanguage = null, ?string $globalContext = null): array
        {
            $result = [];
            foreach ($groups as $groupName => $group) {
                $translatedStrings = [];
                foreach ($group->getStrings() as $key => $string) {
                    $translatedStrings[$key] = new TranslationString(
                        text: $string->getText(),
                        translatedText: 'translated_'.$string->getText(),
                        context: $string->getContext(),
                    );
                }
                $result[$groupName] = new TranslationGroup($groupName, $translatedStrings);
            }

            return $result;
        }
    };

    $this->pollingo = Pollingo::make(translator: $this->mockTranslator);
});

test('it can translate a single string', function () {
    $translation = $this->pollingo
        ->to('fr')
        ->group('messages', [
            'welcome' => 'Welcome',
        ])
        ->translate();

    expect($translation['messages']['welcome'])->toBe('translated_Welcome');
});

test('it can translate multiple strings', function () {
    $translation = $this->pollingo
        ->to('fr')
        ->group('messages', [
            'welcome' => 'Welcome',
            'hello' => 'Hello',
        ])
        ->translate();

    expect($translation['messages']['welcome'])->toBe('translated_Welcome');
    expect($translation['messages']['hello'])->toBe('translated_Hello');
});

test('it can translate strings with context', function () {
    $translation = $this->pollingo
        ->to('fr')
        ->group('messages', [
            'welcome' => [
                'text' => 'Welcome',
                'context' => 'Greeting message shown on the homepage',
            ],
            'hello' => [
                'text' => 'Hello',
                'context' => 'Informal greeting',
            ],
        ])
        ->translate();

    expect($translation['messages']['welcome'])->toBe('translated_Welcome');
    expect($translation['messages']['hello'])->toBe('translated_Hello');
});

test('it can use a custom translator', function () {
    $customTranslator = new class implements Translator {
        public function translate(array $groups, string $targetLanguage, ?string $sourceLanguage = null, ?string $globalContext = null): array
        {
            $result = [];
            foreach ($groups as $groupName => $group) {
                $translatedStrings = [];
                foreach ($group->getStrings() as $key => $string) {
                    $translatedStrings[$key] = new TranslationString(
                        text: $string->getText(),
                        translatedText: 'custom_'.$string->getText(),
                        context: $string->getContext(),
                    );
                }
                $result[$groupName] = new TranslationGroup($groupName, $translatedStrings);
            }

            return $result;
        }
    };

    $pollingo = Pollingo::make(translator: $customTranslator);
    $translation = $pollingo
        ->to('fr')
        ->group('messages', [
            'welcome' => 'Welcome',
        ])
        ->translate();

    expect($translation['messages']['welcome'])->toBe('custom_Welcome');
});

test('it can translate a single text', function () {
    $translation = $this->pollingo
        ->to('fr')
        ->text('Welcome')
        ->translate();

    expect($translation)->toBe('translated_Welcome');
});

test('it can translate a single text with context', function () {
    $translation = $this->pollingo
        ->to('fr')
        ->text('Welcome')
        ->context('Greeting message shown on the homepage')
        ->translate();

    expect($translation)->toBe('translated_Welcome');
});
