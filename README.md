<p align="center"><img src="public/logo.svg" width="80px" alt="Logo Laravel Pollingo"></p>

# 🌍 Pollingo

A framework-agnostic PHP package for translating groups of strings using OpenAI.

## 🚀 Features

- ✨ Fluent API for submitting groups of strings to translate
- 🔤 Simple API for translating single strings
- 🤖 OpenAI-powered smart, contextual translations
- 🔌 Support for custom translation providers
- 🌐 Global and per-string context support
- 🔤 Support for all ISO 639-1 language codes
- 📦 Framework-agnostic with Laravel integration
- 🧩 Modular architecture for easy extension with other AI providers

## 📥 Installation

### Standalone Installation

```bash
composer require pollora/pollingo
```

### Laravel Installation

The package will automatically register itself if you're using Laravel's package discovery. After installation, you can publish the configuration file:

```bash
php artisan vendor:publish --tag=pollingo-config
```

## 🔧 Configuration

### Standalone Configuration

Set your OpenAI API key in your environment:

```env
OPENAI_API_KEY=your-api-key
OPENAI_MODEL=gpt-4
```

### Laravel Configuration

In your `.env` file:

```env
OPENAI_API_KEY=your-api-key
OPENAI_MODEL=gpt-4
```

You can also customize the configuration in `config/pollingo.php` after publishing it.

## 📝 Basic Usage

### Single String Translation

```php
use Pollora\Pollingo\Pollingo;

// Simple translation
$translation = Pollingo::make('your-openai-api-key')
    ->from('en')
    ->to('fr')
    ->text('Welcome to our application')
    ->translate();

// Translation with context
$translation = Pollingo::make('your-openai-api-key')
    ->from('en')
    ->to('fr')
    ->text('Welcome to our platform!')
    ->context('Used in the subject of a welcome email.')
    ->translate();
```

### Group Translation

```php
use Pollora\Pollingo\Pollingo;

$translations = Pollingo::make('your-openai-api-key')
    ->from('en')
    ->to('fr')
    ->group('messages', [
        'welcome' => 'Welcome to our application',
        'goodbye' => 'Goodbye!',
    ])
    ->translate();
```

### Laravel Usage

Using the Facade:
```php
use Pollora\Pollingo\Facades\Pollingo;

$translations = Pollingo::from('en')
    ->to('fr')
    ->group('messages', [
        'welcome' => 'Welcome to our application',
        'goodbye' => 'Goodbye!',
    ])
    ->translate();
```

Using Dependency Injection:
```php
use Pollora\Pollingo\Pollingo;

class TranslationController
{
    public function __construct(
        private readonly Pollingo $pollingo
    ) {}

    public function translate()
    {
        return $this->pollingo
            ->from('en')
            ->to('fr')
            ->group('messages', [
                'welcome' => 'Welcome to our application',
            ])
            ->translate();
    }
}
```

## 🌐 Language Support

Pollingo supports all ISO 639-1 language codes. Here are some common examples:

- 🇺🇸 English: `en`
- 🇫🇷 French: `fr`
- 🇪🇸 Spanish: `es`
- 🇩🇪 German: `de`
- 🇮🇹 Italian: `it`
- 🇯🇵 Japanese: `ja`
- 🇨🇳 Chinese: `zh`
- 🇷🇺 Russian: `ru`

Example with language codes:
```php
$pollingo
    ->from('en')    // Source language (English)
    ->to('fr')      // Target language (French)
    ->translate();
```

## 🧩 Advanced Features

### Groups and Context

You can organize your translations into logical groups and provide context:

```php
$pollingo->group('emails', [
    'welcome' => [
        'text' => 'Welcome to our platform!',
        'context' => 'Used in the subject of a welcome email.'
    ],
    'greeting' => [
        'text' => 'Hi {name}',
        'context' => 'Personal greeting with user name placeholder'
    ]
]);
```

### Custom Translators

While OpenAI is the default translation provider, you can implement your own translator by implementing the `Translator` interface:

```php
use Pollora\Pollingo\Contracts\Translator;

class MyCustomTranslator implements Translator
{
    public function translate(
        array $groups,
        string $targetLanguage,
        ?string $sourceLanguage = null,
        ?string $globalContext = null
    ): array {
        // Your custom translation logic here
    }
}
```

You can then use your custom translator in two ways:

1. Using the fluent `withTranslator()` method:
```php
$pollingo = Pollingo::make()
    ->withTranslator(new MyCustomTranslator())
    ->from('en')
    ->to('fr')
    ->translate();
```

2. Using the `make()` method's `translator` parameter:
```php
$pollingo = Pollingo::make(translator: new MyCustomTranslator());
```

This is particularly useful when you want to:
- Use a different translation service (DeepL, Google Translate, etc.)
- Implement custom translation logic
- Mock translations for testing
- Cache translations
- Implement fallback mechanisms

### Creating Custom AI Translators

You can create custom AI translators by extending the `BaseAITranslator` class:

```php
use Pollora\Pollingo\Services\BaseAITranslator;
use Pollora\Pollingo\Contracts\AIClient;

class GoogleAITranslator extends BaseAITranslator implements AIClient
{
    private readonly GoogleClient $client;
    
    public function __construct(
        string $apiKey,
        string $model = 'gemini-pro',
        int $timeout = 120
    ) {
        parent::__construct($model, $timeout);
        $this->client = new GoogleClient($apiKey);
    }
    
    public function translate(array $groups, string $targetLanguage, ?string $sourceLanguage = null, ?string $globalContext = null): array
    {
        // Implementation for Google AI translation
    }
    
    public function chatCompletion(string $model, array $messages, float $temperature = 0.1): string
    {
        // Implementation for Google AI chat completion
    }
    
    protected function getSystemPrompt(): string
    {
        // Optionally override the system prompt for Google AI
        return parent::getSystemPrompt();
    }
}
```

### Global Context

You can provide global context that applies to all translations:

```php
$pollingo
    ->withGlobalContext('This is for a professional business application')
    ->group('auth', [
        'login' => 'Log in',
        'register' => 'Create an account',
    ])
    ->translate();
```

### Multiple Groups

You can translate multiple groups in a single request:

```php
$translations = $pollingo
    ->from('en')
    ->to('fr')
    ->group('ui', [
        'save' => 'Save',
        'cancel' => 'Cancel',
    ])
    ->group('messages', [
        'success' => 'Operation completed successfully',
        'error' => 'An error occurred',
    ])
    ->translate();
```

## 🛠️ Laravel Integration Features

### Configuration

The package provides a configuration file that can be customized:

```php
// config/pollingo.php
return [
    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4'),
    ],
];
```

### Service Container

The package registers both the main service and the translator interface in Laravel's service container:

```php
use Pollora\Pollingo\Contracts\Translator;

class TranslationService
{
    public function __construct(
        private readonly Translator $translator
    ) {}
}
```

### API Configuration and Reliability

Pollingo provides methods to customize the OpenAI API configuration and enhance reliability with retries:

```php
// Select different AI models for different needs
$translations = Pollingo::make('your-openai-api-key')
    ->model('gpt-4.1-nano')  // Smaller, faster model for simple translations
    ->from('en')
    ->to('fr')
    ->text('Hello world')
    ->translate();

// Set custom timeout for long translations
$translations = Pollingo::make('your-openai-api-key')
    ->timeout(180)  // 3 minutes timeout (default: 120 seconds)
    ->from('en')
    ->to('es')
    ->group('legal', $legalTexts)  // Large legal documents
    ->translate();

// Configure retry behavior
$translations = Pollingo::make('your-openai-api-key')
    ->maxRetries(5)           // Number of retries on failure (default: 3)
    ->retryDelay(2000)        // Milliseconds between retries (default: 1000)
    ->from('en')
    ->to('ja')
    ->group('documents', $documents)
    ->translate();

// Chain configuration methods
$translations = Pollingo::make('your-openai-api-key')
    ->model('gpt-4.1-turbo')
    ->timeout(240)
    ->maxRetries(4)
    ->retryDelay(1500)
    ->from('en')
    ->to('zh')
    ->group('marketing', $marketingContent)
    ->translate();
```

## 🧪 Testing

```bash
composer test
```

For testing with OpenAI, you need to set the following environment variables:

```bash
OPENAI_API_KEY=your-api-key OPENAI_MODEL=gpt-4 vendor/bin/pest
```

## 🤝 Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## 📄 License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.