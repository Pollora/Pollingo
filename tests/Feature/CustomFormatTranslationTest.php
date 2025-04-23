<?php

declare(strict_types=1);

namespace Pollora\Pollingo\Tests\Feature;

use Pollora\Pollingo\Services\StringFormatter;
use Pollora\Pollingo\Services\TranslationResponseParser;
use PHPUnit\Framework\TestCase;

class CustomFormatTranslationTest extends TestCase
{
    private StringFormatter $stringFormatter;
    private TranslationResponseParser $responseParser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create the services
        $this->stringFormatter = new StringFormatter();
        $this->responseParser = new TranslationResponseParser();
    }

    public function test_string_formatter_builds_correct_message(): void
    {
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

        $message = $this->stringFormatter->buildMessage(
            strings: $strings,
            targetLanguage: 'French',
            sourceLanguage: 'English',
            globalContext: 'UI labels',
        );

        $this->assertStringContainsString('Translate the following strings from English to French:', $message);
        $this->assertStringContainsString('- greeting: "Hello"', $message);
        $this->assertStringContainsString('- action: "Save" (context: Button label)', $message);
        $this->assertStringContainsString('Global context: UI labels', $message);
        $this->assertStringContainsString('[KEY:key_name]translated_text[/KEY]', $message);
    }

    public function test_response_parser_handles_custom_format(): void
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

        $result = $this->responseParser->parseResponse($content, $groupName, $strings);

        $this->assertEquals($groupName, $result->getName());
        
        $translatedStrings = $result->getStrings();
        $this->assertCount(2, $translatedStrings);
        
        $this->assertEquals('Hello', $translatedStrings['greeting']->getText());
        $this->assertEquals('Bonjour', $translatedStrings['greeting']->getTranslatedText());
        
        $this->assertEquals('Save', $translatedStrings['action']->getText());
        $this->assertEquals('Sauvegarder', $translatedStrings['action']->getTranslatedText());
        $this->assertEquals('Button label', $translatedStrings['action']->getContext());
    }

    public function test_response_parser_handles_json_in_translation(): void
    {
        $content = "[KEY:json_example]{\"key\": \"value\", \"array\": [1, 2, 3]}[/KEY]";
        $groupName = 'ui';
        $strings = [
            'json_example' => [
                'text' => '{"key": "value", "array": [1, 2, 3]}',
                'context' => 'JSON example',
            ],
        ];

        $result = $this->responseParser->parseResponse($content, $groupName, $strings);

        $translatedStrings = $result->getStrings();
        
        $this->assertEquals(
            '{"key": "value", "array": [1, 2, 3]}', 
            $translatedStrings['json_example']->getTranslatedText()
        );
    }
} 