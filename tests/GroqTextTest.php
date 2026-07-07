<?php

declare(strict_types=1);

use AiSdk\Generate;
use AiSdk\Groq;
use AiSdk\Groq\Tests\Fakes\FakeHttpClient;
use AiSdk\Schema;
use AiSdk\Support\Sdk;

afterEach(function () {
    Generate::reset();
    Groq::reset();
});

function configureGroqWith(FakeHttpClient $client): void
{
    $factory = new \Nyholm\Psr7\Factory\Psr17Factory();
    Generate::configure(new Sdk(
        httpClient: $client,
        requestFactory: $factory,
        streamFactory: $factory,
    ));
}

it('generates text end to end through the Groq vertical', function () {
    $client = new FakeHttpClient(200, json_encode([
        'id' => 'chatcmpl_groq',
        'object' => 'chat.completion',
        'created' => 1710000000,
        'model' => 'llama-3.3-70b-versatile',
        'system_fingerprint' => 'fp_groq',
        'choices' => [['index' => 0, 'message' => ['content' => 'Hello from Groq'], 'finish_reason' => 'stop']],
        'usage' => ['prompt_tokens' => 7, 'completion_tokens' => 3],
    ]));
    configureGroqWith($client);

    Groq::create(['apiKey' => 'gsk-test']);

    $result = Generate::text('Hi')->model(Groq::model('llama-3.3-70b-versatile'))->run();

    expect($result->text)->toBe('Hello from Groq')
        ->and($result->usage->inputTokens)->toBe(7)
        ->and($result->providerMetadata['groq']['id'])->toBe('chatcmpl_groq')
        ->and($result->providerMetadata['groq']['model'])->toBe('llama-3.3-70b-versatile')
        ->and($result->providerMetadata['groq']['choice_finish_reason'])->toBe('stop');

    $body = $client->sentBody();
    expect($body['model'])->toBe('llama-3.3-70b-versatile')
        ->and($body['messages'][0]['role'])->toBe('user')
        ->and($body['stream'])->toBeFalse();

    expect($client->lastRequest->getHeaderLine('Authorization'))->toBe('Bearer gsk-test');
});

it('normalizes provider-neutral text usage fields', function () {
    $client = new FakeHttpClient(200, json_encode([
        'choices' => [['index' => 0, 'message' => ['content' => 'Hello from Groq'], 'finish_reason' => 'stop']],
        'usage' => ['input_tokens' => 13, 'output_tokens' => 6, 'total_tokens' => 19],
    ]));
    configureGroqWith($client);

    Groq::create(['apiKey' => 'gsk-test']);

    $result = Generate::text('Hi')->model(Groq::model('llama-3.3-70b-versatile'))->run();

    expect($result->usage->inputTokens)->toBe(13)
        ->and($result->usage->outputTokens)->toBe(6)
        ->and($result->usage->totalTokens)->toBe(19);
});

it('maps a 429 to a rate limit exception', function () {
    $client = new FakeHttpClient(429, json_encode(['error' => ['message' => 'slow down']]));
    configureGroqWith($client);
    Groq::create(['apiKey' => 'gsk-test']);

    Generate::text('Hi')->model(Groq::model('llama-3.3-70b-versatile'))->run();
})->throws(\AiSdk\Exceptions\RateLimitException::class);

it('falls back to json_object structured output for models without json_schema support', function () {
    $client = new FakeHttpClient(200, json_encode([
        'choices' => [['message' => ['content' => '{"city":"Lahore","country":"Pakistan"}'], 'finish_reason' => 'stop']],
        'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 7],
    ]));
    configureGroqWith($client);
    Groq::create(['apiKey' => 'gsk-test']);

    $result = Generate::text('Extract the city and country from: Lahore, Pakistan.')
        ->model(Groq::model('llama-3.1-8b-instant'))
        ->output(Schema::object(
            name: 'address',
            properties: [
                Schema::string(name: 'city')->required(),
                Schema::string(name: 'country')->required(),
            ],
        ))
        ->run();

    $body = $client->sentBody();

    expect($body['response_format'])->toBe(['type' => 'json_object'])
        ->and($body['messages'][0]['role'])->toBe('system')
        ->and($body['messages'][0]['content'])->toContain('valid JSON object')
        ->and($result->output)->toBe(['city' => 'Lahore', 'country' => 'Pakistan']);
});

it('reports json object fallback as an adapted structured output capability', function () {
    Groq::create(['apiKey' => 'gsk-test']);

    $support = Groq::model('llama-3.1-8b-instant')->capability(\AiSdk\Capability::StructuredOutput);

    expect($support->state)->toBe(\AiSdk\CapabilitySupportState::Adapted)
        ->and($support->strategy)->toContain('json_schema');
});

it('loads native structured output support from current Groq GPT OSS models', function () {
    Groq::create(['apiKey' => 'gsk-test']);

    expect(Groq::model('openai/gpt-oss-20b')->supports(\AiSdk\Capability::StructuredOutput))->toBeTrue()
        ->and(Groq::model('openai/gpt-oss-120b')->supports(\AiSdk\Capability::StructuredOutput))->toBeTrue()
        ->and(Groq::model('llama-3.3-70b-versatile')->supports(\AiSdk\Capability::StructuredOutput))->toBeFalse();
});

it('loads image input support from resources models json', function () {
    Groq::create(['apiKey' => 'gsk-test']);

    expect(Groq::model('meta-llama/llama-4-scout-17b-16e-instruct')->supports(\AiSdk\Capability::ImageInput))->toBeTrue()
        ->and(Groq::model('meta-llama/llama-4-maverick-17b-128e-instruct')->supports(\AiSdk\Capability::ImageInput))->toBeTrue()
        ->and(Groq::model('llama-3.1-8b-instant')->supports(\AiSdk\Capability::ImageInput))->toBeFalse();
});
