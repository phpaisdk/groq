<?php

declare(strict_types=1);

namespace AiSdk;

use AiSdk\Contracts\SpeechModelInterface;
use AiSdk\Contracts\TextModelInterface;
use AiSdk\Groq\GroqOptions;
use AiSdk\Groq\GroqProvider;

/**
 * Friendly facade for the Groq provider.
 *
 *   $model = Groq::model('llama-3.3-70b-versatile');
 */
final class Groq
{
    private static ?GroqProvider $default = null;

    /**
     * @param  array<string, mixed>  $config
     */
    public static function create(array $config = []): GroqProvider
    {
        return self::$default = new GroqProvider(GroqOptions::fromArray($config));
    }

    public static function default(): GroqProvider
    {
        return self::$default ??= self::create();
    }

    public static function reset(): void
    {
        self::$default = null;
    }

    public static function model(string $modelId): TextModelInterface
    {
        return self::default()->textModel($modelId);
    }

    public static function speech(string $modelId): SpeechModelInterface
    {
        return self::default()->speechModel($modelId);
    }
}
