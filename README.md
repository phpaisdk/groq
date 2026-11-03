# PHP AI SDK — Groq

Text generation, streaming, tools, speech generation, transcription, and embeddings through Groq. Part of the framework-agnostic [PHP AI SDK](https://phpaisdk.com).

## Installation

```bash
composer require aisdk/groq
```

Set `GROQ_API_KEY` in your environment.

## Usage

```php
use AiSdk\Generate;
use AiSdk\Groq;

$result = Generate::text('Explain closures in PHP in one sentence.')
    ->model(Groq::model('openai/gpt-oss-120b'))
    ->run();

echo $result->text;
```

## Documentation

[Read the documentation](https://phpaisdk.com/docs/0.x/providers/groq) for configuration, supported features, and more examples.

[Contributing](https://github.com/phpaisdk/.github/blob/main/CONTRIBUTING.md) · [Support](https://github.com/phpaisdk/.github/blob/main/SUPPORT.md)

## License

The MIT License (MIT). See [LICENSE](LICENSE) for details.
