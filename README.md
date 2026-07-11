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
| Structured output | Adapted (`json_object` + instruction); exact native support can be declared at runtime |
| Speech generation | Native |
| Embeddings | Native |
| Text input | Native |
| Image input | Supported by the adapter; the selected model is validated by Groq |

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

## Embeddings

```php
use AiSdk\Generate;
use AiSdk\Groq;

$result = Generate::embedding(['Search query', 'Document text'])
    ->model(Groq::embedding('nomic-embed-text-v1_5'))
    ->providerOptions('groq', ['user' => 'user-123'])
    ->run();

$queryVector = $result->embeddings[0]->vector;
$documentVector = $result->embeddings[1]->vector;
```

Groq's embedding request schema does not expose a dimensions field, so this adapter rejects the portable `dimensions()` option before sending a request.

## Speech Generation

```php
use AiSdk\Generate;
use AiSdk\Groq;

$result = Generate::speech()
    ->model(Groq::speech('canopylabs/orpheus-v1-english'))
    ->input('Welcome to Orpheus text-to-speech. [cheerful] This is expressive Groq audio generation.')
    ->voice('austin')
    ->format('wav')
    ->run();

$result->output->save(__DIR__.'/orpheus.wav');
```

## Structured Output

Without an exact runtime capability override, the provider adapter degrades `json_schema` to `json_object` with an injected JSON instruction:

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

## Model IDs and Capabilities

Groq model IDs pass through unchanged and do not need to be registered. The package does not ship a model inventory; the Groq API remains the authority on whether a particular model accepts a requested feature.

Capabilities describe what the Groq adapter can serialize. The Groq API returns a normalized SDK exception if the selected model or requested feature is rejected.

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
- [Groq Python SDK Embeddings Resource](https://github.com/groq/groq-python/blob/main/src/groq/resources/embeddings.py)
- [Project Documentation](https://github.com/phpaisdk)
