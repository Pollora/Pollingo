<?php

declare(strict_types=1);

namespace Pollora\Pollingo\Tests\Traits;

use OpenAI\Client;
use OpenAI\Resources\Chat;
use PHPUnit\Framework\MockObject\MockObject;

trait MockOpenAIResponse
{
    /**
     * Create a mock OpenAI client that returns a formatted response.
     *
     * @param  string  $apiKey
     * @return Client&MockObject
     */
    protected function createMockOpenAIClient(string $apiKey): Client
    {
        $mockClient = $this->createMock(Client::class);
        $mockChat = $this->createMock(Chat::class);
        
        $mockClient->method('chat')->willReturn($mockChat);
        
        $mockChat->method('create')->willReturnCallback(function (array $params) {
            $messages = $params['messages'];
            $userMessage = $messages[1]['content'];
            
            // Extract the strings to translate from the user message
            $strings = [];
            if (preg_match_all('/- ([^:]+): "([^"]+)"(?: \(context: ([^)]+)\))?/', $userMessage, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $key = $match[1];
                    $text = $match[2];
                    $context = $match[3] ?? null;
                    
                    $strings[$key] = [
                        'text' => $text,
                        'context' => $context,
                    ];
                }
            }
            
            // Generate a mock response in our custom format
            $responseContent = '';
            foreach ($strings as $key => $string) {
                // For testing, we'll just add "Translated" to the original text
                $translatedText = "Translated: {$string['text']}";
                $responseContent .= "[KEY:{$key}]{$translatedText}[/KEY]\n";
            }
            
            // Create a simple mock response object
            $response = new \stdClass();
            $response->choices = [
                (object)[
                    'message' => (object)[
                        'content' => $responseContent,
                    ],
                ],
            ];
            
            return $response;
        });
        
        return $mockClient;
    }
} 