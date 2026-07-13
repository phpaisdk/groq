<?php

declare(strict_types=1);

namespace AiSdk\Groq\Models;

use AiSdk\Contracts\BaseModel;
use AiSdk\Contracts\TranscriptionModelInterface;
use AiSdk\Generate;
use AiSdk\Groq\GroqOptions;
use AiSdk\OpenAICompatible\TranscriptionRequestBuilder;
use AiSdk\OpenAICompatible\TranscriptionResponseParser;
use AiSdk\Requests\TranscriptionRequest;
use AiSdk\Responses\TranscriptionResponse;
use AiSdk\Utils\Support\Url;

final class GroqTranscriptionModel extends BaseModel implements TranscriptionModelInterface
{
    public function __construct(
        private readonly string $modelId,
        private readonly GroqOptions $options,
    ) {}

    public function provider(): string
    {
        return GroqOptions::PROVIDER_NAME;
    }

    public function modelId(): string
    {
        return $this->modelId;
    }

    public function transcribe(TranscriptionRequest $request): TranscriptionResponse
    {
        $sdk = $this->options->sdk ?? Generate::sdk();
        $multipart = TranscriptionRequestBuilder::build(
            $this->modelId,
            $this->provider(),
            $request,
            supportsUrl: true,
        );
        $url = Url::joinPath($this->options->baseUrl, '/audio/transcriptions');
        $httpRequest = $sdk->requestFactory->createRequest('POST', $url)
            ->withBody($sdk->streamFactory->createStream($multipart['body']))
            ->withHeader('Content-Type', 'multipart/form-data; boundary=' . $multipart['boundary'])
            ->withHeader('Accept', 'application/json');

        foreach ($this->options->authHeaders() as $name => $value) {
            $httpRequest = $httpRequest->withHeader($name, $value);
        }

        $response = $this->runner($sdk)->sendRequest($httpRequest, $this->provider());

        return TranscriptionResponseParser::parse($response, $this->provider(), ['model' => $this->modelId]);
    }
}
