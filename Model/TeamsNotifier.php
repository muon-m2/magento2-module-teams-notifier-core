<?php

declare(strict_types=1);

namespace Muon\TeamsNotifierCore\Model;

use Magento\Framework\Exception\LocalizedException;
use Muon\TeamsNotifierCore\Api\ChannelRepositoryInterface;
use Muon\TeamsNotifierCore\Api\Data\AdaptiveCardMessageInterface;
use Muon\TeamsNotifierCore\Api\Data\ChannelInterface;
use Muon\TeamsNotifierCore\Api\Data\MessageInterface;
use Muon\TeamsNotifierCore\Api\Data\QueuedNotificationInterface;
use Muon\TeamsNotifierCore\Api\TeamsNotifierInterface;
use Muon\TeamsNotifierCore\Api\TemplateRepositoryInterface;
use Muon\TeamsNotifierCore\Model\Queue\Publisher;
use Muon\TeamsNotifierCore\Model\Queue\QueuedNotificationFactory;
use Psr\Log\LoggerInterface;

/**
 * Primary Teams notification service.
 *
 * Targets the current Teams Workflows webhook infrastructure
 * (*.aa.environment.api.powerplatform.com). Supports only Adaptive Card format.
 *
 * Delegates to HttpDelivery (sync mode) or Queue\Publisher (async mode)
 * based on admin configuration.
 *
 * When the target channel has an Adaptive Card template assigned, the template JSON
 * is loaded and ${placeholder} expressions are resolved with the caller-supplied $data
 * array before delivery. Channels without a template use the caller-supplied card body.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * Coordination service — coupling is inherent to orchestrating channel lookup, template
 * resolution, payload building, sync/async dispatch, and kill-switch enforcement.
 */
class TeamsNotifier implements TeamsNotifierInterface
{
    /**
     * @param \Muon\TeamsNotifierCore\Model\Config $config
     * @param \Muon\TeamsNotifierCore\Model\HttpDelivery $httpDelivery
     * @param \Muon\TeamsNotifierCore\Api\ChannelRepositoryInterface $channelRepository
     * @param \Muon\TeamsNotifierCore\Api\TemplateRepositoryInterface $templateRepository
     * @param \Muon\TeamsNotifierCore\Model\TemplateVariableSubstitutor $substitutor
     * @param \Muon\TeamsNotifierCore\Model\Queue\Publisher $publisher
     * @param \Muon\TeamsNotifierCore\Model\Queue\QueuedNotificationFactory $notificationFactory
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        private readonly Config $config,
        private readonly HttpDelivery $httpDelivery,
        private readonly ChannelRepositoryInterface $channelRepository,
        private readonly TemplateRepositoryInterface $templateRepository,
        private readonly TemplateVariableSubstitutor $substitutor,
        private readonly Publisher $publisher,
        private readonly QueuedNotificationFactory $notificationFactory,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Send a message to a named channel (or the configured default).
     *
     * @param \Muon\TeamsNotifierCore\Api\Data\MessageInterface $message
     * @param string|null $channelName
     * @param array $data Key→value map for ${placeholder} substitution in the channel template.
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function send(MessageInterface $message, ?string $channelName = null, array $data = []): void
    {
        if (!$this->config->isEnabled()) {
            $this->logger->debug('Muon_TeamsNotifierCore: notifications disabled — skipping send().');
            return;
        }

        $name    = $channelName ?? $this->config->getDefaultChannelName();
        $channel = $this->channelRepository->getByName($name);

        if (!$channel->getIsActive()) {
            throw new LocalizedException(
                __('Teams channel "%1" is inactive and cannot receive notifications.', $name)
            );
        }

        $this->applyTemplate($message, $channel, $data);

        $this->dispatch(
            $message,
            QueuedNotificationInterface::TARGET_TYPE_CHANNEL,
            $name,
            $channel->getWebhookUrl(),
            $channel->getTriggerSecret(),
            $data
        );
    }

    /**
     * Send a message to an arbitrary webhook URL, bypassing channel lookup.
     *
     * @param \Muon\TeamsNotifierCore\Api\Data\MessageInterface $message
     * @param string $webhookUrl
     * @param array $data Key→value map for ${placeholder} substitution (unused without a template).
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function sendToWebhook(
        MessageInterface $message,
        string $webhookUrl,
        array $data = []
    ): void {
        if (!$this->config->isEnabled()) {
            $this->logger->debug(
                'Muon_TeamsNotifierCore: notifications disabled — skipping sendToWebhook().'
            );
            return;
        }

        $this->dispatch(
            $message,
            QueuedNotificationInterface::TARGET_TYPE_WEBHOOK,
            $webhookUrl,
            $webhookUrl,
            '',
            $data
        );
    }

    /**
     * Apply the channel's Adaptive Card template to the message when one is assigned.
     *
     * Resolves ${placeholder} expressions with $data and overwrites the message's
     * card body and actions with the resolved template content. No-op when the channel
     * has no template assigned.
     *
     * @param \Muon\TeamsNotifierCore\Api\Data\MessageInterface $message
     * @param \Muon\TeamsNotifierCore\Api\Data\ChannelInterface $channel
     * @param array $data
     * @return void
     */
    private function applyTemplate(MessageInterface $message, ChannelInterface $channel, array $data): void
    {
        $templateId = $channel->getTemplateId();

        if ($templateId === null || !$message instanceof AdaptiveCardMessageInterface) {
            return;
        }

        try {
            $template = $this->templateRepository->getById($templateId);
        } catch (\Throwable $e) {
            $this->logger->warning(
                'Muon_TeamsNotifierCore: could not load template {id} for channel "{channel}", '
                . 'falling back to caller-supplied card body.',
                ['id' => $templateId, 'channel' => $channel->getName(), 'error' => $e->getMessage()]
            );
            return;
        }

        $decoded  = json_decode($template->getTemplateJson(), true) ?? [];
        $resolved = $this->substitutor->substitute($decoded, $data);

        $message->setCardBody($resolved['body'] ?? []);
        $message->setCardActions($resolved['actions'] ?? []);

        if (isset($resolved['version'])) {
            $message->setCardVersion((string) $resolved['version']);
        }
    }

    /**
     * Route to sync or async delivery.
     *
     * @param \Muon\TeamsNotifierCore\Api\Data\MessageInterface $message
     * @param string $targetType
     * @param string $targetValue
     * @param string $webhookUrl
     * @param string $triggerSecret
     * @param array $data
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function dispatch(
        MessageInterface $message,
        string $targetType,
        string $targetValue,
        string $webhookUrl,
        string $triggerSecret,
        array $data
    ): void {
        if ($this->config->isAsyncMode()) {
            $this->enqueue($message, $targetType, $targetValue, $triggerSecret, $data);
            return;
        }

        $this->httpDelivery->post($message, $webhookUrl, $triggerSecret);
    }

    /**
     * Serialise the message and publish it to the queue.
     *
     * @param \Muon\TeamsNotifierCore\Api\Data\MessageInterface $message
     * @param string $targetType
     * @param string $targetValue
     * @param string $triggerSecret
     * @param array $data
     * @return void
     */
    private function enqueue(
        MessageInterface $message,
        string $targetType,
        string $targetValue,
        string $triggerSecret,
        array $data
    ): void {
        $notification = $this->notificationFactory->create();
        $notification->setMessageData($this->serialiseMessage($message));
        $notification->setMessageFormat($message->getFormat());
        $notification->setTargetType($targetType);
        $notification->setTargetValue($targetValue);
        $notification->setTriggerSecret($triggerSecret);
        $notification->setTemplateData($data);
        $notification->setAttempt(1);
        $notification->setAvailableAt(0);

        $this->publisher->publish($notification);
    }

    /**
     * Convert a message object into a plain array for queue serialisation.
     *
     * Only Adaptive Card messages are supported by Teams Workflows webhooks.
     *
     * @param \Muon\TeamsNotifierCore\Api\Data\MessageInterface $message
     * @return array<string, mixed>
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function serialiseMessage(MessageInterface $message): array
    {
        if (!$message instanceof AdaptiveCardMessageInterface) {
            throw new LocalizedException(
                __(
                    'Message type "%1" is not supported. Use AdaptiveCardMessage — '
                    . 'Teams Workflows webhooks require Adaptive Card format.',
                    $message::class
                )
            );
        }

        return [
            'title'        => $message->getTitle(),
            'summary'      => $message->getSummary(),
            'theme_color'  => $message->getThemeColor(),
            'card_body'    => $message->getCardBody(),
            'card_actions' => $message->getCardActions(),
            'card_version' => $message->getCardVersion(),
        ];
    }
}
