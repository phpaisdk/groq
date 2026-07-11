<?php

declare(strict_types=1);

namespace AiSdk\Groq;

use AiSdk\Contracts\BaseProvider;
use AiSdk\Contracts\EmbeddingModelInterface;
use AiSdk\Contracts\EmbeddingProviderInterface;
use AiSdk\Contracts\SpeechModelInterface;
use AiSdk\Contracts\TextModelInterface;
use AiSdk\Groq\Models\GroqEmbeddingModel;
use AiSdk\Groq\Models\GroqSpeechModel;
use AiSdk\Groq\Models\GroqTextModel;

final class GroqProvider extends BaseProvider implements EmbeddingProviderInterface
{
    public function __construct(public readonly GroqOptions $options) {}

    public function name(): string
    {
        return GroqOptions::PROVIDER_NAME;
    }

    public function textModel(string $modelId): TextModelInterface
    {
        return new GroqTextModel($modelId, $this->options);
    }

    public function speechModel(string $modelId): SpeechModelInterface
    {
        return new GroqSpeechModel($modelId, $this->options);
    }

    public function embeddingModel(string $modelId): EmbeddingModelInterface
    {
        return new GroqEmbeddingModel($modelId, $this->options);
    }
}
