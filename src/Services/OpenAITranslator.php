<?php

declare(strict_types=1);

namespace Pollora\Pollingo\Services;

use GuzzleHttp\Client as GuzzleClient;
use OpenAI\Client;
use OpenAI\Factory;
use Pollora\Pollingo\Contracts\AIClient;
use Pollora\Pollingo\Contracts\Translator;
use Pollora\Pollingo\DTO\TranslationGroup;
use Pollora\Pollingo\DTO\TranslationString;
use RuntimeException;

/**
 * @template TKey of string
 *
 * @implements Translator<TKey>
 * @implements AIClient
 */
final class OpenAITranslator extends BaseAITranslator implements AIClient
{
    private readonly Client $client;
    private readonly MessageBuilder $messageBuilder;

    protected readonly LanguageCodeService $languageCodeService;
    protected readonly StringFormatter $stringFormatter;
    protected readonly TranslationResponseParser $responseParser;

    public function __construct(
        string $apiKey,
        string $model = 'gpt-4_1-2025-04-14',
        int $timeout = 120 // default timeout in seconds
    ) {
        parent::__construct($model, $timeout);

        $httpClient = new GuzzleClient(['timeout' => $this->timeout]);
        $this->client = (new Factory())
            ->withApiKey($apiKey)
            ->withHttpClient($httpClient)
            ->make();

        $this->messageBuilder = new MessageBuilder();
    }

    /**
     * @param  array<string, TranslationGroup<TKey>>  $groups
     * @return array<string, TranslationGroup<TKey>>
     */
    public function translate(array $groups, string $targetLanguage, ?string $sourceLanguage = null, ?string $globalContext = null): array
    {
        $targetLanguageName = $this->languageCodeService->getLanguageName($targetLanguage);
        $sourceLanguageName = $sourceLanguage ? $this->languageCodeService->getLanguageName($sourceLanguage) : null;

        /** @var array<string, TranslationGroup<TKey>> */
        $result = [];

        foreach ($groups as $groupName => $group) {
            $strings = [];
            foreach ($group->getStrings() as $key => $string) {
                $strings[$key] = [
                    'text' => $string->getText(),
                    'context' => $string->getContext(),
                ];
            }

            $userMessage = $this->stringFormatter->buildMessage(
                strings: $strings,
                targetLanguage: $targetLanguageName,
                sourceLanguage: $sourceLanguageName,
                globalContext: $globalContext,
            );

            $messages = $this->messageBuilder->buildMessages(
                systemPrompt: $this->getSystemPrompt(),
                userMessage: $userMessage
            );

            $content = $this->chatCompletion(
                model: $this->model,
                messages: $messages
            );

            $result[$groupName] = $this->responseParser->parseResponse(
                content: $content,
                groupName: $groupName,
                strings: $strings,
            );
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function chatCompletion(string $model, array $messages, float $temperature = 0.1): string
    {
        $response = $this->client->chat()->create([
            'model' => $model,
            'temperature' => $temperature,
            'messages' => $messages,
        ]);

        $content = $response->choices[0]->message->content;

        if ($content === null) {
            throw new RuntimeException('Empty response from OpenAI API');
        }

        return $content;
    }
}
