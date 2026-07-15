<?php

declare(strict_types=1);

namespace AiSdk\Groq;

use AiSdk\Contracts\BaseProvider;
use AiSdk\Contracts\EmbeddingModelInterface;
use AiSdk\Contracts\EmbeddingProviderInterface;
use AiSdk\Contracts\SpeechModelInterface;
use AiSdk\Contracts\SpeechProviderInterface;
use AiSdk\Contracts\TextModelInterface;
use AiSdk\Contracts\TextProviderInterface;
use AiSdk\Contracts\TranscriptionModelInterface;
use AiSdk\Contracts\TranscriptionProviderInterface;
use AiSdk\Groq\Models\GroqEmbeddingModel;
use AiSdk\Groq\Models\GroqSpeechModel;
use AiSdk\Groq\Models\GroqTextModel;
use AiSdk\Groq\Models\GroqTranscriptionModel;

final class GroqProvider extends BaseProvider implements EmbeddingProviderInterface, SpeechProviderInterface, TextProviderInterface, TranscriptionProviderInterface
{
    public function __construct(public readonly GroqOptions $options) {}

    public function name(): string
    {
        return GroqOptions::PROVIDER_NAME;
    }

    protected function textModel(string $modelId): TextModelInterface
    {
        return new GroqTextModel($modelId, $this->options);
    }

    protected function speechModel(string $modelId): SpeechModelInterface
    {
        return new GroqSpeechModel($modelId, $this->options);
    }

    protected function transcriptionModel(string $modelId): TranscriptionModelInterface
    {
        return new GroqTranscriptionModel($modelId, $this->options);
    }

    protected function embeddingModel(string $modelId): EmbeddingModelInterface
    {
        return new GroqEmbeddingModel($modelId, $this->options);
    }
}
