<?php

declare(strict_types=1);

namespace AiSdk\Groq\Models;

use AiSdk\Capability;
use AiSdk\CapabilitySupport;
use AiSdk\Contracts\BaseModel;
use AiSdk\Contracts\TextModelInterface;
use AiSdk\Groq\GroqOptions;
use AiSdk\OpenAICompatible\ChatRequestBuilder;
use AiSdk\OpenAICompatible\ChatResponseParser;
use AiSdk\OpenAICompatible\ChatStreamParser;
use AiSdk\Requests\TextModelRequest;
use AiSdk\Responses\TextModelResponse;
use AiSdk\Support\ModelCatalog;
use AiSdk\Support\ModelRegistry;
use AiSdk\Utils\Support\Url;
use Generator;

final class GroqTextModel extends BaseModel implements TextModelInterface
{
    public function __construct(
        private readonly string $modelId,
        private readonly GroqOptions $options,
        private readonly ?ModelRegistry $registry = null,
    ) {}

    public function provider(): string
    {
        return GroqOptions::PROVIDER_NAME;
    }

    public function modelId(): string
    {
        return $this->modelId;
    }

    /**
     * @return array<int, Capability>
     */
    public function capabilities(): array
    {
        $definition = $this->registry?->resolve($this->provider(), $this->modelId);
        if ($definition !== null) {
            return $this->configuredCapabilities($definition->capabilities);
        }

        return $this->configuredCapabilities($this->catalog()->capabilities($this->modelId));
    }

    public function capability(Capability $capability): CapabilitySupport
    {
        $configured = $this->configuredCapability($capability);
        if ($configured !== null) {
            return $configured;
        }

        $registered = $this->registry?->capability($this->provider(), $this->modelId, $capability);
        if ($registered !== null) {
            return $registered;
        }

        $support = $this->catalog()->capability($this->modelId, $capability);

        // Unknown model: allow text generation, defer other capabilities to provider API errors.
        if (! $support->isSupported()
            && $capability === Capability::TextGeneration
            && $this->catalog()->capabilities($this->modelId) === []) {
            return CapabilitySupport::supported($capability, 'unknown-model-fallback');
        }

        return $support;
    }

    public function generate(TextModelRequest $request): TextModelResponse
    {
        $body = $this->buildBody($request, stream: false);
        $url = Url::joinPath($this->options->baseUrl, '/chat/completions');

        $payload = $this->runner($this->options->sdk)
            ->postJson($url, $body, $this->options->authHeaders(), $this->provider());

        return ChatResponseParser::parse($payload, $this->provider());
    }

    public function stream(TextModelRequest $request): Generator
    {
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
            && $this->capability(Capability::StructuredOutput)->state === \AiSdk\CapabilitySupportState::Adapted) {
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

    private function catalog(): ModelCatalog
    {
        return ModelCatalog::fromFile(dirname(__DIR__, 2) . '/resources/models.json');
    }
}
