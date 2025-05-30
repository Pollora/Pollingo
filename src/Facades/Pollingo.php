<?php

declare(strict_types=1);

namespace Pollora\Pollingo\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @template TKey of string
 *
 * @method static \Pollora\Pollingo\Pollingo<TKey> make(?string $apiKey = null, string $model = 'gpt-4', ?\Pollora\Pollingo\Contracts\Translator<TKey> $translator = null)
 * @method static \Pollora\Pollingo\Pollingo<TKey> withTranslator(\Pollora\Pollingo\Contracts\Translator<TKey> $translator)
 * @method static \Pollora\Pollingo\Pollingo<TKey> from(string $language)
 * @method static \Pollora\Pollingo\Pollingo<TKey> to(string $language)
 * @method static \Pollora\Pollingo\Pollingo<TKey> withGlobalContext(string $context)
 * @method static \Pollora\Pollingo\Pollingo<TKey> model(string $model)
 * @method static \Pollora\Pollingo\Pollingo<TKey> timeout(int $timeout)
 * @method static \Pollora\Pollingo\Pollingo<TKey> maxRetries(int $maxRetries)
 * @method static \Pollora\Pollingo\Pollingo<TKey> retryDelay(int $retryDelay)
 * @method static \Pollora\Pollingo\Pollingo<TKey> group(string $name, array<TKey, string|array{text: string, context?: string}> $strings)
 * @method static array<string, array<TKey, string>> translate()
 *
 * @see \Pollora\Pollingo\Pollingo
 */
final class Pollingo extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Pollora\Pollingo\Pollingo::class;
    }


    /**
     * Resolve a new instance for the facade
     *
     * @return mixed
     */
    public static function refresh()
    {
        static::clearResolvedInstance(static::getFacadeAccessor());

        return static::getFacadeRoot();
    }
}
