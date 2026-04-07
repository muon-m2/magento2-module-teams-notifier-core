<?php

declare(strict_types=1);

namespace Muon\TeamsNotifierCore\Test\Unit\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Client\Curl;
use Muon\TeamsNotifierCore\Model\AdaptiveCardMessage;
use Muon\TeamsNotifierCore\Model\Config;
use Muon\TeamsNotifierCore\Model\HttpDelivery;
use Muon\TeamsNotifierCore\Model\Payload\PayloadBuilderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class HttpDeliveryTest extends TestCase
{
    // phpcs:disable Magento2.Commenting.ClassPropertyPHPDocFormatting.Missing
    /** @var \Muon\TeamsNotifierCore\Model\Config */
    private Config&MockObject $config;
    /** @var \Magento\Framework\HTTP\Client\Curl */
    private Curl&MockObject $curl;
    /** @var \Psr\Log\LoggerInterface */
    private LoggerInterface&MockObject $logger;
    /** @var \Muon\TeamsNotifierCore\Model\Payload\PayloadBuilderInterface */
    private PayloadBuilderInterface&MockObject $builder;
    // phpcs:enable Magento2.Commenting.ClassPropertyPHPDocFormatting.Missing
    /** @var \Muon\TeamsNotifierCore\Model\HttpDelivery */
    private HttpDelivery $httpDelivery;

    protected function setUp(): void
    {
        $this->config  = $this->createMock(Config::class);
        $this->curl    = $this->createMock(Curl::class);
        $this->logger  = $this->createMock(LoggerInterface::class);
        $this->builder = $this->createMock(PayloadBuilderInterface::class);

        $this->config->method('getTimeout')->willReturn(10);

        $this->httpDelivery = new HttpDelivery(
            $this->config,
            $this->curl,
            $this->logger,
            ['adaptive_card' => $this->builder]
        );
    }

    public function testPostCallsCurlWithCorrectPayload(): void
    {
        $message    = new AdaptiveCardMessage();
        $webhookUrl = 'https://org123.aa.environment.api.powerplatform.com/powerautomate/automations/test';

        $this->builder->expects($this->once())
            ->method('build')
            ->with($message)
            ->willReturn('{"payload":"data"}');

        $this->curl->expects($this->once())->method('setTimeout')->with(10);
        $this->curl->expects($this->once())->method('post')->with($webhookUrl, '{"payload":"data"}');
        $this->curl->method('getStatus')->willReturn(202);

        $this->httpDelivery->post($message, $webhookUrl);
    }

    public function testPostSendsTriggerSecretHeaderWhenProvided(): void
    {
        $message    = new AdaptiveCardMessage();
        $webhookUrl = 'https://org123.aa.environment.api.powerplatform.com/powerautomate/automations/test';
        $secret     = 'my-trigger-secret';

        $this->builder->method('build')->willReturn('{}');
        $this->curl->method('getStatus')->willReturn(202);

        $this->curl->expects($this->exactly(2))
            ->method('addHeader')
            ->willReturnCallback(function (string $name, string $value) use ($secret): void {
                if ($name === 'TriggerSecret') {
                    $this->assertSame($secret, $value);
                }
            });

        $this->httpDelivery->post($message, $webhookUrl, $secret);
    }

    public function testPostOmitsTriggerSecretHeaderWhenEmpty(): void
    {
        $message = new AdaptiveCardMessage();

        $this->builder->method('build')->willReturn('{}');
        $this->curl->method('getStatus')->willReturn(202);

        // Only Content-Type header should be added, not TriggerSecret
        $this->curl->expects($this->once())
            ->method('addHeader')
            ->with('Content-Type', 'application/json');

        $this->httpDelivery->post($message, 'https://example.com/webhook');
    }

    public function testPostThrowsWhenStatusIsNot202(): void
    {
        $this->expectException(LocalizedException::class);

        $message = new AdaptiveCardMessage();

        $this->builder->method('build')->willReturn('{}');
        $this->curl->method('getStatus')->willReturn(400);
        $this->curl->method('getBody')->willReturn('1');

        $this->httpDelivery->post($message, 'https://example.com/webhook');
    }

    public function testPostThrowsWhenNoBuilderRegisteredForFormat(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('No payload builder registered for message format');

        $delivery = new HttpDelivery($this->config, $this->curl, $this->logger, []);
        $delivery->post(new AdaptiveCardMessage(), 'https://example.com/webhook');
    }
}
