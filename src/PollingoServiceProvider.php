<?php

declare(strict_types=1);

namespace Pollora\Pollingo;

use Illuminate\Support\ServiceProvider;
use Pollora\Pollingo\Contracts\Translator;
use Pollora\Pollingo\Services\OpenAITranslator;

/**
 * @template TKey of string
 */
final class PollingoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Merge config
        $this->mergeConfigFrom(
            __DIR__.'/../config/pollingo.php',
            'pollingo'
        );

        // Register the main Pollingo service
        $this->app->bind(Pollingo::class, function ($app) {
            $config = $app['config']['pollingo'];

            $apiKey = $config['openai_api_key'] ?? null;
            // If apiKey is null, try to get it from nested structure
            if ($apiKey === null && isset($config['openai']['api_key'])) {
                $apiKey = $config['openai']['api_key'];
            }

            // Ensure apiKey is a string, not null, to avoid type error
            $apiKey = $apiKey ?? '';

            $model = $config['openai_model'] ?? $config['openai']['model'] ?? 'gpt-4o';

            /**
             * @var Pollingo<TKey> $pollingo
             */
            $pollingo = Pollingo::make(
                apiKey: $apiKey,
                model: $model,
            );

            return $pollingo;
        });

        // Register the translator interface
        $this->app->singleton(Translator::class, function ($app) {
            $config = $app['config']['pollingo'];

            /** @var OpenAITranslator<TKey> */
            $translator = new OpenAITranslator(
                apiKey: $config['openai']['api_key'] ?? '',
                model: $config['openai']['model'] ?? 'gpt-4o',
            );

            return $translator;
        });

        // Register the facade
        $this->app->alias(Pollingo::class, 'pollingo');
    }

    public function boot(): void
    {
        // Publish config
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/pollingo.php' => config_path('pollingo.php'),
            ], 'config');
        }
    }
}
