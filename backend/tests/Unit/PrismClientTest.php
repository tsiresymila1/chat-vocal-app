<?php
namespace Tests\Unit;

use App\Services\PrismClient;
use Prism\Prism\Prism;
use Prism\Prism\Testing\TextResponseFake;
use Prism\Prism\ValueObjects\Messages\UserMessage;
use Prism\Prism\ValueObjects\Usage;
use Tests\TestCase;

class PrismClientTest extends TestCase
{
    public function test_get_ai_response()
    {

        $fakeResponse = TextResponseFake::make()
            ->withText('AI response')
            ->withUsage(new Usage(10, 20));

        Prism::fake([$fakeResponse]);

        $prismClient = new PrismClient();
        $response = $prismClient->getAiResponse([new UserMessage("hello")]);
        dump($response);

        $this->assertEquals('AI response', $response);
    }

    public function test_get_stream_response()
    {
    
        $fakeResponse = TextResponseFake::make()
            ->withStream(['Stream response']);

        Prism::fake([$fakeResponse]);

        $prismClient = new PrismClient();
        $response = $prismClient->getStreamResponse([]);

        dump($response);
        
        $streamedContent = '';
        foreach ($response as $chunk) {
            dump($chunk);
            $streamedContent .= $chunk;
        }

        $this->assertEquals('Stream response', $streamedContent);
    }
}