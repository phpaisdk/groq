<?php

declare(strict_types=1);

use AiSdk\Content;
use AiSdk\Contracts\TranscriptionProviderInterface;
use AiSdk\Generate;
use AiSdk\Groq;
use AiSdk\Groq\Tests\Fakes\FakeHttpClient;
use AiSdk\Support\Sdk;
use Nyholm\Psr7\Factory\Psr17Factory;

afterEach(function () {
    Generate::reset();
    Groq::reset();
});

it('transcribes audio and accepts Groq URL input', function () {
    $client = new FakeHttpClient(200, '{"text":"Fast transcript."}');
    $factory = new Psr17Factory();
    Generate::configure(new Sdk($client, $factory, $factory));
    Groq::create(['apiKey' => 'gsk-test']);

    expect(Groq::default())->toBeInstanceOf(TranscriptionProviderInterface::class);

    $result = Generate::transcription(Content::audio('https://example.com/clip.mp3', 'audio/mpeg'))
        ->model(Groq::model('whisper-large-v3-turbo'))
        ->run();

    $body = (string) $client->lastRequest?->getBody();
    expect($result->output->text)->toBe('Fast transcript.')
        ->and((string) $client->lastRequest?->getUri())->toBe('https://api.groq.com/openai/v1/audio/transcriptions')
        ->and($body)->toContain('name="model"', 'whisper-large-v3-turbo', 'name="url"', 'https://example.com/clip.mp3')
        ->and(str_contains($body, 'name="file"'))->toBeFalse();
});
