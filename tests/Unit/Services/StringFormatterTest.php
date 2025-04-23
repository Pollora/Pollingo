<?php

declare(strict_types=1);

namespace Pollora\Pollingo\Tests\Unit\Services;

use Pollora\Pollingo\Services\StringFormatter;
use PHPUnit\Framework\TestCase;

class StringFormatterTest extends TestCase
{
    private StringFormatter $formatter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formatter = new StringFormatter();
    }

    public function test_format_strings_for_prompt(): void
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

        $expected = "\n- greeting: \"Hello\"\n- action: \"Save\" (context: Button label)";
        
        $result = $this->formatter->formatStringsForPrompt($strings);
        
        $this->assertEquals($expected, $result);
    }

    public function test_build_message_with_source_language(): void
    {
        $strings = [
            'greeting' => [
                'text' => 'Hello',
                'context' => null,
            ],
        ];

        $result = $this->formatter->buildMessage(
            strings: $strings,
            targetLanguage: 'French',
            sourceLanguage: 'English',
            globalContext: null,
        );

        $this->assertStringContainsString('Translate the following strings from English to French:', $result);
        $this->assertStringContainsString('- greeting: "Hello"', $result);
        $this->assertStringContainsString('[KEY:key_name]translated_text[/KEY]', $result);
        $this->assertStringContainsString('[KEY:greeting]Bonjour[/KEY]', $result);
    }

    public function test_build_message_with_global_context(): void
    {
        $strings = [
            'greeting' => [
                'text' => 'Hello',
                'context' => null,
            ],
        ];

        $result = $this->formatter->buildMessage(
            strings: $strings,
            targetLanguage: 'French',
            sourceLanguage: null,
            globalContext: 'UI labels for a web application',
        );

        $this->assertStringContainsString('Translate the following strings to French:', $result);
        $this->assertStringContainsString('Global context: UI labels for a web application', $result);
        $this->assertStringContainsString('- greeting: "Hello"', $result);
        $this->assertStringContainsString('[KEY:key_name]translated_text[/KEY]', $result);
    }
} 