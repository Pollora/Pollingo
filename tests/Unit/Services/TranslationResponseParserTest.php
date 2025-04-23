<?php

declare(strict_types=1);

namespace Pollora\Pollingo\Tests\Unit\Services;

use Pollora\Pollingo\DTO\TranslationGroup;
use Pollora\Pollingo\DTO\TranslationString;
use Pollora\Pollingo\Services\TranslationResponseParser;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class TranslationResponseParserTest extends TestCase
{
    private TranslationResponseParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new TranslationResponseParser();
    }

    public function test_parse_response_creates_translation_group(): void
    {
        $content = "[KEY:greeting]Bonjour[/KEY]\n[KEY:action]Sauvegarder[/KEY]";
        $groupName = 'ui';
        $strings = [
            'greeting' => [
                'text' => 'Hello',
                'context' => null,
            ],
            'action' => [
                'text' => 'Save',
                'context' => 'Button label',
            ],
        ];

        $result = $this->parser->parseResponse($content, $groupName, $strings);

        $this->assertInstanceOf(TranslationGroup::class, $result);
        $this->assertEquals($groupName, $result->getName());
        
        $translatedStrings = $result->getStrings();
        $this->assertCount(2, $translatedStrings);
        
        $this->assertEquals('Hello', $translatedStrings['greeting']->getText());
        $this->assertEquals('Bonjour', $translatedStrings['greeting']->getTranslatedText());
        $this->assertNull($translatedStrings['greeting']->getContext());
        
        $this->assertEquals('Save', $translatedStrings['action']->getText());
        $this->assertEquals('Sauvegarder', $translatedStrings['action']->getTranslatedText());
        $this->assertEquals('Button label', $translatedStrings['action']->getContext());
    }

    public function test_parse_response_handles_json_in_translation(): void
    {
        $content = "[KEY:json_example]{\"key\": \"value\", \"array\": [1, 2, 3]}[/KEY]";
        $groupName = 'ui';
        $strings = [
            'json_example' => [
                'text' => '{"key": "value", "array": [1, 2, 3]}',
                'context' => 'JSON example',
            ],
        ];

        $result = $this->parser->parseResponse($content, $groupName, $strings);

        $this->assertInstanceOf(TranslationGroup::class, $result);
        $translatedStrings = $result->getStrings();
        
        $this->assertEquals(
            '{"key": "value", "array": [1, 2, 3]}', 
            $translatedStrings['json_example']->getTranslatedText()
        );
    }

    public function test_parse_response_throws_exception_for_invalid_format(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid response format');
        
        $content = 'invalid format';
        $groupName = 'ui';
        $strings = [
            'greeting' => [
                'text' => 'Hello',
                'context' => null,
            ],
        ];
        
        $this->parser->parseResponse($content, $groupName, $strings);
    }

    public function test_parse_response_throws_exception_for_missing_translation(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Missing translation for key: greeting');
        
        $content = "[KEY:action]Sauvegarder[/KEY]";
        $groupName = 'ui';
        $strings = [
            'greeting' => [
                'text' => 'Hello',
                'context' => null,
            ],
            'action' => [
                'text' => 'Save',
                'context' => 'Button label',
            ],
        ];
        
        $this->parser->parseResponse($content, $groupName, $strings);
    }

    public function test_parse_response_throws_exception_for_extra_translation(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unexpected translation key: extra');
        
        $content = "[KEY:greeting]Bonjour[/KEY]\n[KEY:action]Sauvegarder[/KEY]\n[KEY:extra]Extra[/KEY]";
        $groupName = 'ui';
        $strings = [
            'greeting' => [
                'text' => 'Hello',
                'context' => null,
            ],
            'action' => [
                'text' => 'Save',
                'context' => 'Button label',
            ],
        ];
        
        $this->parser->parseResponse($content, $groupName, $strings);
    }
} 