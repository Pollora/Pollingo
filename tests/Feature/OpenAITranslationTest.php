<?php

declare(strict_types=1);

use OpenAI\Exceptions\ErrorException;
use Pollora\Pollingo\Pollingo;
use Pollora\Pollingo\Tests\Traits\HasOpenAIConfig;

uses(HasOpenAIConfig::class)->group('openai', 'feature');

beforeEach(function () {
    $this->skipIfNoOpenAIKey();
    $this->pollingo = Pollingo::make(
        apiKey: $this->getOpenAIKey(),
        model: $this->getOpenAIModel()
    );
});

test('it throws an exception when using an invalid API key', function () {
    $pollingo = Pollingo::make('invalid-api-key', $this->getOpenAIModel());

    expect(fn () => $pollingo
        ->from('en')
        ->to('fr')
        ->text('Hello')
        ->translate()
    )->toThrow(ErrorException::class);
});

test('it can translate a single text using OpenAI', function () {
    $translation = $this->pollingo
        ->from('en')
        ->to('fr')
        ->text('Hello')
        ->translate();

    expect($translation)
        ->toBeString()
        ->toBeTranslatedTo('fr', 'Hello');
});

test('it can translate a single text with context using OpenAI', function () {
    $translation = $this->pollingo
        ->from('en')
        ->to('fr')
        ->text('Welcome')
        ->context('Used in a formal email')
        ->translate();

    expect($translation)
        ->toBeString()
        ->toBeTranslatedTo('fr', 'Welcome');
});

test('it can translate multiple strings using OpenAI', function () {
    $translations = $this->pollingo
        ->from('en')
        ->to('fr')
        ->group('messages', [
            'hello' => 'Hello',
            'welcome' => 'Welcome',
        ])
        ->translate();

    expect($translations)
        ->toBeArray()
        ->toHaveKey('messages')
        ->and($translations['messages'])
        ->toHaveKeys(['hello', 'welcome']);

    expect($translations['messages']['hello'])->toBeTranslatedTo('fr', 'Hello');
    expect($translations['messages']['welcome'])->toBeTranslatedTo('fr', 'Welcome');
});

test('it can translate strings with context using OpenAI', function () {
    $translations = $this->pollingo
        ->from('en')
        ->to('fr')
        ->withGlobalContext('Used in a mobile app')
        ->group('messages', [
            'welcome' => [
                'text' => 'Welcome',
                'context' => 'Shown on the homepage',
            ],
            'hello' => [
                'text' => 'Hello',
                'context' => 'Used in chat messages',
            ],
        ])
        ->translate();

    expect($translations)
        ->toBeArray()
        ->toHaveKey('messages')
        ->and($translations['messages'])
        ->toHaveKeys(['welcome', 'hello']);

    expect($translations['messages']['welcome'])->toBeTranslatedTo('fr', 'Welcome');
    expect($translations['messages']['hello'])->toBeTranslatedTo('fr', 'Hello');
}); 