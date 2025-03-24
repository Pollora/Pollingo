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

## 🧪 Testing

```bash
composer test
```

## 🤝 Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## 📄 License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.