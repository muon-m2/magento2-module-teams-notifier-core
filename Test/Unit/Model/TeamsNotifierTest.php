<?php

declare(strict_types=1);

namespace Muon\TeamsNotifierCore\Test\Unit\Model;

use Magento\Framework\Exception\LocalizedException;
use Muon\TeamsNotifierCore\Api\ChannelRepositoryInterface;
use Muon\TeamsNotifierCore\Api\Data\ChannelInterface;
use Muon\TeamsNotifierCore\Api\TemplateRepositoryInterface;
use Muon\TeamsNotifierCore\Model\AdaptiveCardMessage;
use Muon\TeamsNotifierCore\Model\Config;
use Muon\TeamsNotifierCore\Model\HttpDelivery;
use Muon\TeamsNotifierCore\Model\Queue\Publisher;
use Muon\TeamsNotifierCore\Model\Queue\QueuedNotification;
use Muon\TeamsNotifierCore\Model\Queue\QueuedNotificationFactory;
use Muon\TeamsNotifierCore\Model\TeamsNotifier;
use Muon\TeamsNotifierCore\Model\TemplateVariableSubstitutor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * Test class — coupling reflects TeamsNotifier's coordination of channels, templates,
 * queuing, and delivery.
 */
class TeamsNotifierTest extends TestCase
{
    // phpcs:disable Magento2.Commenting.ClassPropertyPHPDocFormatting.Missing
    /** @var \Muon\TeamsNotifierCore\Model\Config */
    private Config&MockObject $config;
    /** @var \Muon\TeamsNotifierCore\Model\HttpDelivery */
    private HttpDelivery&MockObject $httpDelivery;
    /** @var \Muon\TeamsNotifierCore\Api\ChannelRepositoryInterface */
    private ChannelRepositoryInterface&MockObject $channelRepository;
    /** @var \Muon\TeamsNotifierCore\Api\TemplateRepositoryInterface */
    private TemplateRepositoryInterface&MockObject $templateRepository;
    /** @var \Muon\TeamsNotifierCore\Model\Queue\Publisher */
    private Publisher&MockObject $publisher;
    /** @var \Muon\TeamsNotifierCore\Model\Queue\QueuedNotificationFactory */
    private QueuedNotificationFactory&MockObject $notificationFactory;
    /** @var \Psr\Log\LoggerInterface */
    private LoggerInterface&MockObject $logger;
    // phpcs:enable Magento2.Commenting.ClassPropertyPHPDocFormatting.Missing
    /** @var \Muon\TeamsNotifierCore\Model\TeamsNotifier */
    private TeamsNotifier $notifier;

    protected function setUp(): void
    {
        $this->config              = $this->createMock(Config::class);
        $this->httpDelivery        = $this->createMock(HttpDelivery::class);
        $this->channelRepository   = $this->createMock(ChannelRepositoryInterface::class);
        $this->templateRepository  = $this->createMock(TemplateRepositoryInterface::class);
        $this->publisher           = $this->createMock(Publisher::class);
        $this->notificationFactory = $this->createMock(QueuedNotificationFactory::class);
        $this->logger              = $this->createMock(LoggerInterface::class);

        $this->notifier = new TeamsNotifier(
            $this->config,
            $this->httpDelivery,
            $this->channelRepository,
            $this->templateRepository,
            new TemplateVariableSubstitutor(),
            $this->publisher,
            $this->notificationFactory,
            $this->logger
        );
    }

    public function testSendDoesNothingWhenModuleDisabled(): void
    {
        $this->config->method('isEnabled')->willReturn(false);

        $this->httpDelivery->expects($this->never())->method('post');
        $this->publisher->expects($this->never())->method('publish');

        $this->notifier->send(new AdaptiveCardMessage());
    }

    public function testSendThrowsWhenChannelIsInactive(): void
    {
        $this->expectException(LocalizedException::class);

        $channel = $this->createMock(ChannelInterface::class);
        $channel->method('getIsActive')->willReturn(false);

        $this->config->method('isEnabled')->willReturn(true);
        $this->config->method('getDefaultChannelName')->willReturn('ops');
        $this->channelRepository->method('getByName')->willReturn($channel);

        $this->notifier->send(new AdaptiveCardMessage());
    }

    public function testSendCallsHttpDeliveryInSyncMode(): void
    {
        $channel = $this->createMock(ChannelInterface::class);
        $channel->method('getIsActive')->willReturn(true);
        $channel->method('getWebhookUrl')->willReturn('https://org.aa.environment.api.powerplatform.com/hook');
        $channel->method('getTriggerSecret')->willReturn('');

        $this->config->method('isEnabled')->willReturn(true);
        $this->config->method('isAsyncMode')->willReturn(false);
        $this->config->method('getDefaultChannelName')->willReturn('ops');
        $this->channelRepository->method('getByName')->willReturn($channel);

        $this->httpDelivery->expects($this->once())->method('post');

        $this->notifier->send(new AdaptiveCardMessage());
    }

    public function testSendPassesTriggerSecretToHttpDelivery(): void
    {
        $channel = $this->createMock(ChannelInterface::class);
        $channel->method('getIsActive')->willReturn(true);
        $channel->method('getWebhookUrl')->willReturn('https://org.aa.environment.api.powerplatform.com/hook');
        $channel->method('getTriggerSecret')->willReturn('my-secret');

        $this->config->method('isEnabled')->willReturn(true);
        $this->config->method('isAsyncMode')->willReturn(false);
        $this->config->method('getDefaultChannelName')->willReturn('ops');
        $this->channelRepository->method('getByName')->willReturn($channel);

        $this->httpDelivery->expects($this->once())
            ->method('post')
            ->with($this->anything(), $this->anything(), 'my-secret');

        $this->notifier->send(new AdaptiveCardMessage());
    }

    public function testSendPublishesToQueueInAsyncMode(): void
    {
        $channel = $this->createMock(ChannelInterface::class);
        $channel->method('getIsActive')->willReturn(true);
        $channel->method('getWebhookUrl')->willReturn('https://org.aa.environment.api.powerplatform.com/hook');
        $channel->method('getTriggerSecret')->willReturn('');

        $this->config->method('isEnabled')->willReturn(true);
        $this->config->method('isAsyncMode')->willReturn(true);
        $this->config->method('getDefaultChannelName')->willReturn('ops');
        $this->channelRepository->method('getByName')->willReturn($channel);

        $notification = new QueuedNotification();
        $this->notificationFactory->method('create')->willReturn($notification);

        $this->publisher->expects($this->once())->method('publish');
        $this->httpDelivery->expects($this->never())->method('post');

        $this->notifier->send(new AdaptiveCardMessage());
    }

    public function testSendToWebhookDoesNothingWhenModuleDisabled(): void
    {
        $this->config->method('isEnabled')->willReturn(false);

        $this->httpDelivery->expects($this->never())->method('post');

        $this->notifier->sendToWebhook(new AdaptiveCardMessage(), 'https://example.com/hook');
    }

    public function testSendToWebhookCallsHttpDeliveryDirectly(): void
    {
        $this->config->method('isEnabled')->willReturn(true);
        $this->config->method('isAsyncMode')->willReturn(false);

        $this->httpDelivery->expects($this->once())
            ->method('post')
            ->with($this->anything(), 'https://example.com/hook', '');

        $this->notifier->sendToWebhook(new AdaptiveCardMessage(), 'https://example.com/hook');
    }

    public function testSendAppliesChannelTemplateWhenAssigned(): void
    {
        $templateJson = json_encode([
            'type'    => 'AdaptiveCard',
            'version' => '1.4',
            'body'    => [['type' => 'TextBlock', 'text' => 'Order ${order_id}']],
        ]);

        $template = $this->createMock(\Muon\TeamsNotifierCore\Api\Data\TemplateInterface::class);
        $template->method('getTemplateJson')->willReturn($templateJson);

        $channel = $this->createMock(ChannelInterface::class);
        $channel->method('getIsActive')->willReturn(true);
        $channel->method('getWebhookUrl')->willReturn('https://org.aa.environment.api.powerplatform.com/hook');
        $channel->method('getTriggerSecret')->willReturn('');
        $channel->method('getTemplateId')->willReturn(7);
        $channel->method('getName')->willReturn('ops');

        $this->config->method('isEnabled')->willReturn(true);
        $this->config->method('isAsyncMode')->willReturn(false);
        $this->config->method('getDefaultChannelName')->willReturn('ops');
        $this->channelRepository->method('getByName')->willReturn($channel);
        $this->templateRepository->method('getById')->with(7)->willReturn($template);

        $this->httpDelivery->expects($this->once())
            ->method('post')
            ->with(
                $this->callback(function (\Muon\TeamsNotifierCore\Api\Data\AdaptiveCardMessageInterface $msg) {
                    return $msg->getCardBody()[0]['text'] === 'Order #99';
                }),
                $this->anything(),
                $this->anything()
            );

        $this->notifier->send(new AdaptiveCardMessage(), null, ['order_id' => '#99']);
    }
}
