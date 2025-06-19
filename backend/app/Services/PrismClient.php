<?php

namespace App\Services;

use Prism\Prism\Prism;
use Prism\Prism\Enums\Provider;

class PrismClient
{
    public function getAiResponse(array $context)
    {
        return Prism::text()
            ->using(Provider::OpenAI, 'gpt-3.5-turbo')
            ->withSystemPrompt("You are a helpful assistant")
            ->withMessages($context)
            ->asText()
            ->messages
            ->get(0);
    }

    public function getStreamResponse(array $context)
    {
        return Prism::text()
            ->using(Provider::OpenAI, 'gpt-3.5-turbo')
            ->withSystemPrompt("You are a helpful assistant")
            ->withMessages($context)
            ->asStream();
    }
}