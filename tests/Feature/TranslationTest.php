<?php

declare(strict_types=1);

namespace Pollora\Pollingo\Tests\Feature;

use Pollora\Pollingo\Pollingo;

beforeEach(function () {
    $this->pollingo = Pollingo::make('fake-api-key');
});

test('it can translate a single string', function () {
    /** @var Pollingo<string> */
    $pollingo = Pollingo::make('fake-api-key');
    $translation = $pollingo
        ->to('fr')
        ->group('messages', [
            'welcome' => 'Welcome',
        ])
        ->translate();

    /** @var string */
    $translatedText = $translation['messages']['welcome'];
    expect($translatedText)->toBeString()->toBeTranslatedTo('fr', 'Welcome');
});

test('it can translate multiple strings', function () {
    /** @var Pollingo<string> */
    $pollingo = Pollingo::make('fake-api-key');
    $translation = $pollingo
        ->to('fr')
        ->group('messages', [
            'welcome' => 'Welcome',
            'hello' => 'Hello',
        ])
        ->translate();

    /** @var string */
    $translatedWelcome = $translation['messages']['welcome'];
    /** @var string */
    $translatedHello = $translation['messages']['hello'];
    expect($translatedWelcome)->toBeString()->toBeTranslatedTo('fr', 'Welcome');
    expect($translatedHello)->toBeString()->toBeTranslatedTo('fr', 'Hello');
});

test('it can translate strings with context', function () {
    /** @var Pollingo<string> */
    $pollingo = Pollingo::make('fake-api-key');
    $translation = $pollingo
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

    /** @var string */
    $translatedWelcome = $translation['messages']['welcome'];
    /** @var string */
    $translatedHello = $translation['messages']['hello'];
    expect($translatedWelcome)->toBeString()->toBeTranslatedTo('fr', 'Welcome');
    expect($translatedHello)->toBeString()->toBeTranslatedTo('fr', 'Hello');
});

test('it can use a custom translator', function () {
    $customTranslator = new class implements \Pollora\Pollingo\Contracts\Translator
    {
        public function translate(array $groups, string $targetLanguage, ?string $sourceLanguage = null, ?string $globalContext = null): array
        {
            // Simple mock that prefixes all translations with 'translated_'
            $result = [];
            foreach ($groups as $groupName => $group) {
                $translatedStrings = [];
                foreach ($group->getStrings() as $key => $string) {
                    $translatedStrings[$key] = new \Pollora\Pollingo\DTO\TranslationString(
                        text: $string->getText(),
                        context: $string->getContext(),
                        translatedText: 'translated_'.$string->getText()
                    );
                }
                $result[$groupName] = new \Pollora\Pollingo\DTO\TranslationGroup($groupName, $translatedStrings);
            }

            return $result;
        }
    };

    /** @var Pollingo<string> */
    $pollingo = Pollingo::make()->withTranslator($customTranslator);
    $translation = $pollingo
        ->to('fr')
        ->group('messages', [
            'welcome' => 'Welcome',
        ])
        ->translate();

    expect($translation['messages']['welcome'])->toBe('translated_Welcome');
});
