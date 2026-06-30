<?php

declare(strict_types=1);

namespace AiSdk\Groq;

use AiSdk\Contracts\BaseProvider;
use AiSdk\Contracts\TextModelInterface;
use AiSdk\Groq\Models\GroqTextModel;

final class GroqProvider extends BaseProvider
{
    public function __construct(public readonly GroqOptions $options) {}

    public function name(): string
    {
        return GroqOptions::PROVIDER_NAME;
    }

    public function textModel(string $modelId): TextModelInterface
    {
        return new GroqTextModel($modelId, $this->options, $this->modelRegistry());
    }
}
