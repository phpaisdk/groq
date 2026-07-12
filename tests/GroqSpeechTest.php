<?php

declare(strict_types=1);

use AiSdk\Contracts\EmbeddingProviderInterface;
use AiSdk\Contracts\SpeechProviderInterface;
use AiSdk\Contracts\TextProviderInterface;
use AiSdk\Exceptions\InvalidResponseException;
use AiSdk\Exceptions\RateLimitException;
use AiSdk\Generate;
use AiSdk\Groq;
use AiSdk\Groq\GroqProvider;
use AiSdk\Groq\Tests\Fakes\FakeHttpClient;
use AiSdk\Support\Sdk;

afterEach(function () {
    Generate::reset();
    Groq::reset();
});

function configureGroqSpeechWith(FakeHttpClient $client): void
{
    $factory = new \Nyholm\Psr7\Factory\Psr17Factory();
    Generate::configure(new Sdk(
        httpClient: $client,
        requestFactory: $factory,
        streamFactory: $factory,
    ));
}

it('declares the text, speech, and embedding provider capabilities', function () {
    $provider = Groq::create(['apiKey' => 'gsk-test']);

    expect($provider)
        ->toBeInstanceOf(GroqProvider::class)
        ->toBeInstanceOf(TextProviderInterface::class)
        ->toBeInstanceOf(SpeechProviderInterface::class)
        ->toBeInstanceOf(EmbeddingProviderInterface::class);
});

it('uses Groq TTS defaults and parses binary wav audio', function () {
    $client = new FakeHttpClient(200, 'RIFF-wav-bytes', 'audio/wav');
    configureGroqSpeechWith($client);
    Groq::create(['apiKey' => 'gsk-test']);

    $result = Generate::speech()
        ->model(Groq::speech('canopylabs/orpheus-v1-english'))
        ->input('Welcome to Groq text to speech.')
        ->voice('austin')
        ->run();

    expect($client->sentBody())->toBe([
        'model' => 'canopylabs/orpheus-v1-english',
        'input' => 'Welcome to Groq text to speech.',
        'voice' => 'austin',
        'response_format' => 'wav',
    ]);

    expect($client->lastRequest->getUri()->getPath())->toBe('/openai/v1/audio/speech')
        ->and($client->lastRequest->getHeaderLine('Accept'))->toBe('audio/wav')
        ->and($result->output->data)->toBe('RIFF-wav-bytes')
        ->and($result->output->mimeType)->toBe('audio/wav')
        ->and($result->providerMetadata['groq'])->toMatchArray([
            'model' => 'canopylabs/orpheus-v1-english',
            'format' => 'wav',
        ]);
});

it('normalizes Groq TTS rate-limit errors', function () {
    $client = new FakeHttpClient(429, json_encode(['error' => ['message' => 'slow down']]));
    configureGroqSpeechWith($client);
    Groq::create(['apiKey' => 'gsk-test']);

    Generate::speech()
        ->model(Groq::speech('canopylabs/orpheus-v1-english'))
        ->input('Welcome to Groq text to speech.')
        ->voice('austin')
        ->run();
})->throws(RateLimitException::class);

it('rejects an empty Groq TTS response', function () {
    $client = new FakeHttpClient(200, '', 'audio/wav');
    configureGroqSpeechWith($client);
    Groq::create(['apiKey' => 'gsk-test']);

    Generate::speech()
        ->model(Groq::speech('canopylabs/orpheus-v1-english'))
        ->input('Welcome to Groq text to speech.')
        ->voice('austin')
        ->run();
})->throws(InvalidResponseException::class, 'empty speech response');
