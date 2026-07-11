<?php

declare(strict_types=1);

namespace AiSdk\Groq\Models;

use AiSdk\Contracts\BaseModel;
use AiSdk\Contracts\EmbeddingModelInterface;
use AiSdk\Exceptions\InvalidArgumentException;
use AiSdk\Groq\GroqOptions;
use AiSdk\OpenAICompatible\EmbeddingRequestBuilder;
use AiSdk\OpenAICompatible\EmbeddingResponseParser;
use AiSdk\Requests\EmbeddingRequest;
use AiSdk\Responses\EmbeddingResponse;
use AiSdk\Utils\Support\Url;

final class GroqEmbeddingModel extends BaseModel implements EmbeddingModelInterface
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

    public function generate(EmbeddingRequest $request): EmbeddingResponse
    {
        if ($request->dimensions !== null) {
            throw new InvalidArgumentException('Groq embeddings do not support the portable dimensions() option.');
        }

        $body = EmbeddingRequestBuilder::build($this->modelId, $this->provider(), $request, [
            'dimensionsParameter' => null,
        ]);
        $payload = $this->runner($this->options->sdk)->postJson(
            Url::joinPath($this->options->baseUrl, '/embeddings'),
            $body,
            $this->options->authHeaders(),
            $this->provider(),
        );

        return EmbeddingResponseParser::parse($payload, $this->provider(), count($request->inputs));
    }
}
