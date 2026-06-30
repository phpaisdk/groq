# aisdk/groq

Official Groq provider for the PHP AI SDK. Uses the shared OpenAI-compatible wire adapter.

## Installation

```bash
composer require aisdk/groq
```

## Basic Usage

```php
use AiSdk\Generate;
use AiSdk\Groq;

$result = Generate::text()
    ->model(Groq::model('llama-3.3-70b-versatile'))
    ->instructions('Write short, clear answers.')
    ->prompt('Explain closures in PHP.')
    ->run();

echo $result->text;
```

Default model shorthand:

```php
Generate::model(Groq::model('llama-3.3-70b-versatile'));

$result = Generate::text('Explain closures in PHP.')->run();
```

## Configuration

### Environment Variables

| Variable | Description | Default |
|---|---|---|
| `GROQ_API_KEY` | API key for authentication | Required |
| `GROQ_BASE_URL` | Base URL for API requests | `https://api.groq.com/openai/v1` |

### Programmatic Configuration

```php
$provider = Groq::create([
    'apiKey' => 'gsk-...',
    'baseUrl' => 'https://api.groq.com/openai/v1',
    'headers' => ['X-Custom-Header' => 'value'],
]);
```

## Supported Capabilities

| Capability | Support |
|---|---|
| Text generation | Native |
| Streaming | Native |
| Tool calling | Native |
| Structured output | Adapted on most models (`json_object` + instruction); native on `gpt-oss-*` and `kimi*` |
| Text input | Native |
| Image input | Native (Llama 4 models) |

## Streaming

```php
use AiSdk\Generate;
use AiSdk\Groq;

$stream = Generate::text('Tell me a story.')
    ->model(Groq::model('llama-3.3-70b-versatile'))
    ->stream();

foreach ($stream->chunks() as $chunk) {
    echo $chunk;
}

$result = $stream->run();
```

## Structured Output

Most Groq models do not support native `json_schema`. The provider automatically degrades to `json_object` with an injected JSON instruction:

```php
use AiSdk\Generate;
use AiSdk\Groq;
use AiSdk\Schema;

$result = Generate::text()
    ->model(Groq::model('llama-3.3-70b-versatile'))
    ->prompt('Extract the city and country from: Lahore, Pakistan.')
    ->output(Schema::object(
        name: 'address',
        properties: [
            Schema::string(name: 'city')->required(),
            Schema::string(name: 'country')->required(),
        ],
    ))
    ->run();
```

Models with native `json_schema` support (`openai/gpt-oss-20b`, `openai/gpt-oss-120b`, `moonshotai/kimi*`) use the native format directly.

## Tools

```php
use AiSdk\Generate;
use AiSdk\Groq;
use AiSdk\Schema;
use AiSdk\Tool;

$weather = Tool::make('weather', 'Get current weather')
    ->input(Schema::string(name: 'city')->required())
    ->run(fn (string $city): string => "Sunny in {$city}");

$result = Generate::text()
    ->model(Groq::model('llama-3.3-70b-versatile'))
    ->prompt('What is the weather in Lahore?')
    ->tool($weather)
    ->run();
```

## Custom Model Registration

Register new Groq models without waiting for a package release:

```php
use AiSdk\Capability;
use AiSdk\Groq;

Groq::registerModel('llama-5-70b', capabilities: [
    Capability::TextGeneration,
    Capability::Streaming,
    Capability::ToolCalling,
    Capability::StructuredOutput,
    Capability::TextInput,
]);

$result = Generate::text('Hello')
    ->model(Groq::model('llama-5-70b'))
    ->run();
```

Use `ModelDefinition` only when you need metadata or adapted-capability details.

## Provider-Specific Options

Raw provider options can be passed as an escape hatch:

```php
$result = Generate::text('Hello')
    ->model(Groq::model('llama-3.3-70b-versatile'))
    ->providerOptions('groq', [
        'raw' => ['top_k' => 40],
    ])
    ->run();
```

## Testing

```bash
composer test
```

## Links

- [Core Package](https://github.com/phpaisdk/core)
- [OpenAI-Compatible Package](https://github.com/phpaisdk/openai-compatible)
- [Project Documentation](https://github.com/phpaisdk)
