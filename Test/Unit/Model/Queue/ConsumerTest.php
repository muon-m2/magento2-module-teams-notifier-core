<?php

declare(strict_types=1);

namespace Muon\TeamsNotifierCore\Test\Unit\Model\Queue;

use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Muon\TeamsNotifierCore\Api\ChannelRepositoryInterface;
use Muon\TeamsNotifierCore\Api\Data\ChannelInterface;
use Muon\TeamsNotifierCore\Api\Data\MessageInterface;
use Muon\TeamsNotifierCore\Api\TemplateRepositoryInterface;
use Muon\TeamsNotifierCore\Model\Config;
use Muon\TeamsNotifierCore\Model\HttpDelivery;
use Muon\TeamsNotifierCore\Model\Queue\Consumer;
use Muon\TeamsNotifierCore\Model\Queue\Publisher;
use Muon\TeamsNotifierCore\Model\Queue\QueuedNotification;
use Muon\TeamsNotifierCore\Model\Queue\QueuedNotificationFactory;
use Muon\TeamsNotifierCore\Model\TemplateVariableSubstitutor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * Test class — coupling is inherent to testing a consumer that orchestrates 10 collaborators.
 */
class ConsumerTest extends TestCase
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
    /** @var \Magento\Framework\Event\ManagerInterface */
    private EventManager&MockObject $eventManager;
    /** @var \Psr\Log\LoggerInterface */
    private LoggerInterface&MockObject $logger;
    // phpcs:enable Magento2.Commenting.ClassPropertyPHPDocFormatting.Missing
    /** @var \Muon\TeamsNotifierCore\Model\Queue\Consumer */
    private Consumer $consumer;

    protected function setUp(): void
    {
        $this->config              = $this->createMock(Config::class);
        $this->httpDelivery        = $this->createMock(HttpDelivery::class);
        $this->channelRepository   = $this->createMock(ChannelRepositoryInterface::class);
        $this->templateRepository  = $this->createMock(TemplateRepositoryInterface::class);
        $this->publisher           = $this->createMock(Publisher::class);
        $this->notificationFactory = $this->createMock(QueuedNotificationFactory::class);
        $this->eventManager        = $this->createMock(EventManager::class);
        $this->logger              = $this->createMock(LoggerInterface::class);

        $this->notificationFactory->method('create')->willReturn(new QueuedNotification());

        $this->consumer = new Consumer(
            $this->config,
            $this->httpDelivery,
            $this->channelRepository,
            $this->templateRepository,
            new TemplateVariableSubstitutor(),
            $this->publisher,
            $this->notificationFactory,
            new Json(),
            $this->eventManager,
            $this->logger
        );
    }

    private function buildPayload(array $overrides = []): string
    {
        $defaults = [
            'message_data'   => [
                'title'        => 'Test',
                'summary'      => 'Test summary',
                'theme_color'  => '0078D4',
                'card_body'    => [['type' => 'TextBlock', 'text' => 'Body']],
                'card_actions' => [],
                'card_version' => '1.4',
            ],
            'message_format' => MessageInterface::FORMAT_ADAPTIVE_CARD,
            'target_type'    => 'webhook',
            'target_value'   => 'https://org.aa.environment.api.powerplatform.com/hook',
            'trigger_secret' => '',
            'template_data'  => [],
            'attempt'        => 1,
            'available_at'   => 0,
        ];

        return json_encode(array_merge($defaults, $overrides));
    }

    public function testProcessDeliversAdaptiveCardPayloadSuccessfully(): void
    {
        $this->httpDelivery->expects($this->once())->method('post');
        $this->publisher->expects($this->never())->method('publish');

        $this->consumer->process($this->buildPayload());
    }

    public function testProcessPassesTriggerSecretToHttpDelivery(): void
    {
        $this->httpDelivery->expects($this->once())
            ->method('post')
            ->with($this->anything(), $this->anything(), 'my-secret');

        $this->consumer->process($this->buildPayload(['trigger_secret' => 'my-secret']));
    }

    public function testProcessRequeuesOnFailureWhenAttemptsRemain(): void
    {
        $this->config->method('getMaxAttempts')->willReturn(3);
        $this->config->method('getRetryDelay')->willReturn(60);
        $this->config->method('getBackoffMultiplier')->willReturn(2);

        $this->httpDelivery
            ->method('post')
            ->willThrowException(new LocalizedException(__('Connection refused')));

        $this->publisher->expects($this->once())->method('publish');
        $this->eventManager->expects($this->never())->method('dispatch');

        $this->consumer->process($this->buildPayload(['attempt' => 1]));
    }

    public function testProcessDispatchesEventAfterMaxAttempts(): void
    {
        $this->config->method('getMaxAttempts')->willReturn(3);
        $this->config->method('getRetryDelay')->willReturn(60);
        $this->config->method('getBackoffMultiplier')->willReturn(2);

        $this->httpDelivery
            ->method('post')
            ->willThrowException(new LocalizedException(__('Connection refused')));

        $this->publisher->expects($this->never())->method('publish');
        $this->eventManager
            ->expects($this->once())
            ->method('dispatch')
            ->with('muon_teams_notifier_core_delivery_failed');

        $this->consumer->process($this->buildPayload(['attempt' => 3]));
    }

    public function testProcessResolvesChannelByNameAndPassesTriggerSecret(): void
    {
        $channel = $this->createMock(ChannelInterface::class);
        $channel->method('getIsActive')->willReturn(true);
        $channel->method('getWebhookUrl')->willReturn('https://org.aa.environment.api.powerplatform.com/hook');
        $channel->method('getTriggerSecret')->willReturn('channel-secret');

        $this->channelRepository->method('getByName')->willReturn($channel);

        $this->httpDelivery->expects($this->once())
            ->method('post')
            ->with($this->anything(), 'https://org.aa.environment.api.powerplatform.com/hook', 'channel-secret');

        $this->consumer->process($this->buildPayload([
            'target_type'  => 'channel',
            'target_value' => 'ops-alerts',
        ]));
    }

    public function testProcessAppliesChannelTemplateWithSubstitution(): void
    {
        $templateJson = json_encode([
            'type'    => 'AdaptiveCard',
            'version' => '1.4',
            'body'    => [['type' => 'TextBlock', 'text' => 'Status: ${status}']],
        ]);

        $template = $this->createMock(\Muon\TeamsNotifierCore\Api\Data\TemplateInterface::class);
        $template->method('getTemplateJson')->willReturn($templateJson);

        $channel = $this->createMock(ChannelInterface::class);
        $channel->method('getIsActive')->willReturn(true);
        $channel->method('getWebhookUrl')->willReturn('https://org.aa.environment.api.powerplatform.com/hook');
        $channel->method('getTriggerSecret')->willReturn('');
        $channel->method('getTemplateId')->willReturn(3);

        $this->channelRepository->method('getByName')->willReturn($channel);
        $this->templateRepository->method('getById')->with(3)->willReturn($template);

        $this->httpDelivery->expects($this->once())
            ->method('post')
            ->with(
                $this->callback(function (\Muon\TeamsNotifierCore\Api\Data\AdaptiveCardMessageInterface $msg) {
                    return $msg->getCardBody()[0]['text'] === 'Status: shipped';
                }),
                $this->anything(),
                $this->anything()
            );

        $this->consumer->process($this->buildPayload([
            'target_type'   => 'channel',
            'target_value'  => 'ops-alerts',
            'template_data' => ['status' => 'shipped'],
        ]));
    }

    public function testProcessPermanentlyFailsForUnsupportedMessageFormat(): void
    {
        // maxAttempts not configured → 0; attempt 1 >= 0, so failure is immediate and permanent.
        $this->config->method('getMaxAttempts')->willReturn(1);

        $this->publisher->expects($this->never())->method('publish');
        $this->eventManager
            ->expects($this->once())
            ->method('dispatch')
            ->with('muon_teams_notifier_core_delivery_failed');

        $this->consumer->process($this->buildPayload(['message_format' => 'legacy_format', 'attempt' => 1]));
    }
}
