<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | OpenAI API Configuration
    |--------------------------------------------------------------------------
    |
    | This section contains the configuration for the OpenAI API integration.
    | You can specify your API key and the model to use for translations.
    |
    */

    'openai' => [
        /*
        |--------------------------------------------------------------------------
        | API Key
        |--------------------------------------------------------------------------
        |
        | Your OpenAI API key. We recommend setting this in your environment file
        | as OPENAI_API_KEY for security.
        |
        */
        'api_key' => env('OPENAI_API_KEY'),

        /*
        |--------------------------------------------------------------------------
        | Model
        |--------------------------------------------------------------------------
        |
        | The OpenAI model to use for translations. We recommend using GPT-4
        | for the best results, but you can also use other models.
        |
        */
        'model' => env('OPENAI_MODEL', 'gpt-4'),
    ],
];
