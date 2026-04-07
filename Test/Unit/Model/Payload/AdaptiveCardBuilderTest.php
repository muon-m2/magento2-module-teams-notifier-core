<?php

declare(strict_types=1);

namespace Muon\TeamsNotifierCore\Test\Unit\Model\Payload;

use Magento\Framework\Serialize\Serializer\Json;
use Muon\TeamsNotifierCore\Api\Data\MessageInterface;
use Muon\TeamsNotifierCore\Model\AdaptiveCardMessage;
use Muon\TeamsNotifierCore\Model\Payload\AdaptiveCardBuilder;
use PHPUnit\Framework\TestCase;

class AdaptiveCardBuilderTest extends TestCase
{
    /** @var \Muon\TeamsNotifierCore\Model\Payload\AdaptiveCardBuilder */
    private AdaptiveCardBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new AdaptiveCardBuilder(new Json());
    }

    public function testBuildProducesValidEnvelopeJson(): void
    {
        $body = [['type' => 'TextBlock', 'text' => 'Hello', 'weight' => 'bolder']];

        $message = (new AdaptiveCardMessage())
            ->setTitle('Deployment Complete')
            ->setSummary('Deploy done')
            ->setCardBody($body)
            ->setCardVersion('1.4');

        $payload     = $this->builder->build($message);
        $decoded     = json_decode($payload, true);
        $attachment  = $decoded['attachments'][0];
        $card        = $attachment['content'];

        $this->assertSame('message', $decoded['type']);
        $this->assertSame('application/vnd.microsoft.card.adaptive', $attachment['contentType']);
        $this->assertSame('AdaptiveCard', $card['type']);
        $this->assertSame('1.4', $card['version']);
        $this->assertSame('http://adaptivecards.io/schemas/adaptive-card.json', $card['$schema']);
    }

    public function testBuildPrependsAutoTitleTextBlockWhenBodyDoesNotStartWithOne(): void
    {
        $message = (new AdaptiveCardMessage())
            ->setTitle('Auto Title')
            ->setCardBody([['type' => 'Image', 'url' => 'https://example.com/img.png']]);

        $decoded = json_decode($this->builder->build($message), true);
        $body    = $decoded['attachments'][0]['content']['body'];

        $this->assertSame('TextBlock', $body[0]['type']);
        $this->assertSame('Auto Title', $body[0]['text']);
        $this->assertSame('bolder', $body[0]['weight']);
    }

    public function testBuildDoesNotDoubleInsertTitleWhenBodyAlreadyStartsWithTextBlock(): void
    {
        $body = [['type' => 'TextBlock', 'text' => 'Custom', 'weight' => 'bolder']];

        $message = (new AdaptiveCardMessage())
            ->setTitle('Should Not Duplicate')
            ->setCardBody($body);

        $decoded = json_decode($this->builder->build($message), true);
        $card    = $decoded['attachments'][0]['content'];

        $this->assertCount(1, $card['body']);
        $this->assertSame('Custom', $card['body'][0]['text']);
    }

    public function testBuildIncludesActionsWhenProvided(): void
    {
        $actions = [['type' => 'Action.OpenUrl', 'title' => 'View', 'url' => 'https://example.com']];

        $message = (new AdaptiveCardMessage())
            ->setCardBody([['type' => 'TextBlock', 'text' => 'Body']])
            ->setCardActions($actions);

        $decoded = json_decode($this->builder->build($message), true);
        $card    = $decoded['attachments'][0]['content'];

        $this->assertSame($actions, $card['actions']);
    }

    public function testBuildThrowsForNonAdaptiveCardMessage(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->builder->build($this->createMock(MessageInterface::class));
    }
}
