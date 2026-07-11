<?php

declare(strict_types=1);

namespace AiSdk\Groq\Models;

use AiSdk\Capability;
use AiSdk\Contracts\BaseModel;
use AiSdk\Contracts\TextModelInterface;
use AiSdk\Groq\GroqOptions;
use AiSdk\OpenAICompatible\ChatRequestBuilder;
use AiSdk\OpenAICompatible\ChatResponseParser;
use AiSdk\OpenAICompatible\ChatStreamParser;
use AiSdk\Requests\TextModelRequest;
use AiSdk\Responses\TextModelResponse;
use AiSdk\Utils\Support\Url;
use Generator;

final class GroqTextModel extends BaseModel implements TextModelInterface
{
    private const array ADAPTER_CAPABILITIES = [
        Capability::TextGeneration,
        Capability::Streaming,
        Capability::ToolCalling,
        Capability::Reasoning,
        Capability::TextInput,
        Capability::ImageInput,
    ];

    private const array ADAPTED_CAPABILITIES = [
        'structured_output' => 'json_schema downgraded to json_object',
    ];

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

    public function generate(TextModelRequest $request): TextModelResponse
    {
        $this->ensureTextRequestSupported($request, self::ADAPTER_CAPABILITIES, self::ADAPTED_CAPABILITIES);

        $body = $this->buildBody($request, stream: false);
        $url = Url::joinPath($this->options->baseUrl, '/chat/completions');

        $payload = $this->runner($this->options->sdk)
            ->postJson($url, $body, $this->options->authHeaders(), $this->provider());

        return ChatResponseParser::parse($payload, $this->provider());
    }

    public function stream(TextModelRequest $request): Generator
    {
        $this->ensureTextRequestSupported($request, self::ADAPTER_CAPABILITIES, self::ADAPTED_CAPABILITIES, streaming: true);

        $body = $this->buildBody($request, stream: true);
        $url = Url::joinPath($this->options->baseUrl, '/chat/completions');

        $events = $this->runner($this->options->sdk)
            ->postStream($url, $body, $this->options->authHeaders(), $this->provider());

        yield from ChatStreamParser::parse($events, $this->provider());
    }

    /**
     * @return array<string, mixed>
     */
    private function buildBody(TextModelRequest $request, bool $stream): array
    {
        $body = ChatRequestBuilder::build($this->modelId, $this->provider(), $request, $stream);

        if (($body['response_format']['type'] ?? null) === 'json_schema'
            && $this->usesAdaptedCapability(Capability::StructuredOutput, self::ADAPTED_CAPABILITIES)) {
            $body['response_format'] = ['type' => 'json_object'];
        }

        if (($body['response_format']['type'] ?? null) === 'json_object') {
            $body['messages'] = $this->withJsonObjectInstruction($body['messages'] ?? []);
        }

        return $body;
    }

    /**
     * @param  mixed  $messages
     * @return array<int, array<string, mixed>>
     */
    private function withJsonObjectInstruction(mixed $messages): array
    {
        $messages = is_array($messages) ? array_values($messages) : [];
        $instruction = 'Respond only with a valid JSON object that matches the requested schema.';

        if (($messages[0]['role'] ?? null) === 'system') {
            $messages[0]['content'] = trim((string) ($messages[0]['content'] ?? '') . "\n\n" . $instruction);

            return $messages;
        }

        array_unshift($messages, [
            'role' => 'system',
            'content' => $instruction,
        ]);

        return $messages;
    }

}
