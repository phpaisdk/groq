<?php

declare(strict_types=1);

use AiSdk\Generate;
use AiSdk\Groq;
use AiSdk\Groq\Tests\Fakes\FakeHttpClient;
use AiSdk\Support\Sdk;
use Nyholm\Psr7\Factory\Psr17Factory;

afterEach(function () {
    Generate::reset();
    Groq::reset();
});

function configureGroqEmbeddingsWith(FakeHttpClient $client): void
{
    $factory = new Psr17Factory();
    Generate::configure(new Sdk($client, $factory, $factory));
}

it('generates Groq embeddings using its documented endpoint', function () {
    $client = new FakeHttpClient(200, json_encode([
        'object' => 'list',
        'model' => 'nomic-embed-text-v1_5',
        'data' => [['object' => 'embedding', 'index' => 0, 'embedding' => [0.1, 0.2]]],
        'usage' => ['prompt_tokens' => 4, 'total_tokens' => 4],
    ]));
    configureGroqEmbeddingsWith($client);
    Groq::create(['apiKey' => 'gsk-test']);

    $result = Generate::embedding('A document')
        ->model(Groq::model('nomic-embed-text-v1_5'))
        ->providerOptions('groq', ['user' => 'user-123'])
        ->run();

    expect($result->output->vector)->toBe([0.1, 0.2])
        ->and($result->usage->inputTokens)->toBe(4)
        ->and($client->lastRequest?->getUri()->getPath())->toBe('/openai/v1/embeddings')
        ->and($client->sentBody())->toMatchArray([
            'model' => 'nomic-embed-text-v1_5',
            'input' => ['A document'],
            'encoding_format' => 'float',
            'user' => 'user-123',
        ]);
});

it('rejects unsupported portable dimensions before sending a Groq request', function () {
    $client = new FakeHttpClient(200, '{}');
    configureGroqEmbeddingsWith($client);
    Groq::create(['apiKey' => 'gsk-test']);

    Generate::embedding('A document')
        ->model(Groq::model('nomic-embed-text-v1_5'))
        ->dimensions(256)
        ->run();
})->throws(\AiSdk\Exceptions\InvalidArgumentException::class);
