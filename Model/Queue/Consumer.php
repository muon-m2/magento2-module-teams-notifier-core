<?php

declare(strict_types=1);

namespace Muon\TeamsNotifierCore\Model\Queue;

use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Muon\TeamsNotifierCore\Api\ChannelRepositoryInterface;
use Muon\TeamsNotifierCore\Api\Data\AdaptiveCardMessageInterface;
use Muon\TeamsNotifierCore\Api\Data\MessageInterface;
use Muon\TeamsNotifierCore\Api\Data\QueuedNotificationInterface;
use Muon\TeamsNotifierCore\Api\TemplateRepositoryInterface;
use Muon\TeamsNotifierCore\Model\AdaptiveCardMessage;
use Muon\TeamsNotifierCore\Model\Config;
use Muon\TeamsNotifierCore\Model\HttpDelivery;
use Muon\TeamsNotifierCore\Model\TemplateVariableSubstitutor;
use Psr\Log\LoggerInterface;

/**
 * Queue consumer for async Teams Workflows webhook delivery with exponential-backoff retry.
 *
 * Start via: bin/magento queue:consumers:start muonTeamsNotifierCoreSend
 *
 * Retry algorithm:
 *   - On delivery failure: increment attempt, compute next available_at = now + base_delay * multiplier^(attempt-1).
 *   - Re-publish the message; consumer processes other messages while the delay elapses.
 *   - On available_at in the future: sleep up to MAX_SLEEP_SECONDS, then re-publish.
 *   - After max_attempts: log critical + dispatch muon_teams_notifier_core_delivery_failed event.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * Coordination service — coupling is inherent to orchestrating retry logic, channel lookup,
 * template resolution, message reconstruction, HTTP delivery, and observability event dispatch.
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 * Ten constructor parameters are required to coordinate all collaborators without hiding
 * dependencies behind a service locator.
 */
class Consumer
{
    /**
     * Maximum time (seconds) the consumer will sleep when a message is not yet ready.
     */
    private const MAX_SLEEP_SECONDS = 30;

    /**
     * @param \Muon\TeamsNotifierCore\Model\Config $config
     * @param \Muon\TeamsNotifierCore\Model\HttpDelivery $httpDelivery
     * @param \Muon\TeamsNotifierCore\Api\ChannelRepositoryInterface $channelRepository
     * @param \Muon\TeamsNotifierCore\Api\TemplateRepositoryInterface $templateRepository
     * @param \Muon\TeamsNotifierCore\Model\TemplateVariableSubstitutor $substitutor
     * @param \Muon\TeamsNotifierCore\Model\Queue\Publisher $publisher
     * @param \Muon\TeamsNotifierCore\Model\Queue\QueuedNotificationFactory $notificationFactory
     * @param \Magento\Framework\Serialize\Serializer\Json $json
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
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
        private readonly Json $json,
        private readonly EventManager $eventManager,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Process a queued notification JSON payload.
     *
     * Called by the Magento consumer framework for each message on the topic.
     *
     * @param string $payload JSON-encoded notification data.
     * @return void
     */
    public function process(string $payload): void
    {
        $data = $this->json->unserialize($payload);

        $notification = $this->notificationFactory->create();
        $notification->setMessageData($data['message_data'] ?? []);
        $notification->setMessageFormat($data['message_format'] ?? '');
        $notification->setTargetType($data['target_type'] ?? '');
        $notification->setTargetValue($data['target_value'] ?? '');
        $notification->setTriggerSecret($data['trigger_secret'] ?? '');
        $notification->setTemplateData($data['template_data'] ?? []);
        $notification->setAttempt((int) ($data['attempt'] ?? 1));
        $notification->setAvailableAt((int) ($data['available_at'] ?? 0));

        $availableAt = $notification->getAvailableAt();
        $now         = time();

        if ($availableAt > $now) {
            $wait = min($availableAt - $now, self::MAX_SLEEP_SECONDS);
            sleep($wait); // phpcs:ignore Magento2.Functions.DiscouragedFunction

            if (time() < $availableAt) {
                // Still not ready — re-queue for a later worker cycle.
                $this->publisher->publish($notification);
                return;
            }
        }

        try {
            [$webhookUrl, $triggerSecret] = $this->resolveDeliveryTarget($notification);
            $message                      = $this->reconstructMessage($notification);
            $this->httpDelivery->post($message, $webhookUrl, $triggerSecret);
        } catch (\Throwable $e) {
            $this->handleFailure($notification, $e);
        }
    }

    /**
     * Resolve the webhook URL and TriggerSecret for the notification.
     *
     * For channel-type notifications, also applies any assigned Adaptive Card template
     * with the stored template_data so the resolved card body is used for delivery.
     *
     * @param \Muon\TeamsNotifierCore\Api\Data\QueuedNotificationInterface $notification
     * @return array{0: string, 1: string} [webhookUrl, triggerSecret]
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function resolveDeliveryTarget(QueuedNotificationInterface $notification): array
    {
        if ($notification->getTargetType() === QueuedNotificationInterface::TARGET_TYPE_WEBHOOK) {
            return [$notification->getTargetValue(), $notification->getTriggerSecret()];
        }

        $channel = $this->channelRepository->getByName($notification->getTargetValue());

        if (!$channel->getIsActive()) {
            throw new LocalizedException(
                __('Channel "%1" is inactive.', $notification->getTargetValue())
            );
        }

        return [$channel->getWebhookUrl(), $channel->getTriggerSecret()];
    }

    /**
     * Reconstruct the Adaptive Card message, applying the channel template when assigned.
     *
     * Template substitution happens here (at consume time) so that any template edits
     * made between enqueue and delivery are reflected in the final card.
     *
     * @param \Muon\TeamsNotifierCore\Api\Data\QueuedNotificationInterface $notification
     * @return \Muon\TeamsNotifierCore\Api\Data\MessageInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function reconstructMessage(QueuedNotificationInterface $notification): MessageInterface
    {
        $format = $notification->getMessageFormat();

        if ($format !== MessageInterface::FORMAT_ADAPTIVE_CARD) {
            throw new LocalizedException(
                __(
                    'Message format "%1" is not supported by Teams Workflows webhooks. '
                    . 'Only Adaptive Card format is accepted.',
                    $format
                )
            );
        }

        $data    = $notification->getMessageData();
        $message = new AdaptiveCardMessage();
        $message->setTitle($data['title'] ?? '');
        $message->setSummary($data['summary'] ?? '');
        $message->setThemeColor($data['theme_color'] ?? MessageInterface::DEFAULT_THEME_COLOR);
        $message->setCardBody($data['card_body'] ?? []);
        $message->setCardActions($data['card_actions'] ?? []);
        $message->setCardVersion($data['card_version'] ?? AdaptiveCardMessageInterface::DEFAULT_CARD_VERSION);

        if ($notification->getTargetType() === QueuedNotificationInterface::TARGET_TYPE_CHANNEL) {
            $this->applyChannelTemplate($message, $notification);
        }

        return $message;
    }

    /**
     * Apply the channel's Adaptive Card template to the message when one is assigned.
     *
     * @param \Muon\TeamsNotifierCore\Model\AdaptiveCardMessage $message
     * @param \Muon\TeamsNotifierCore\Api\Data\QueuedNotificationInterface $notification
     * @return void
     */
    private function applyChannelTemplate(
        AdaptiveCardMessage $message,
        QueuedNotificationInterface $notification
    ): void {
        try {
            $channel    = $this->channelRepository->getByName($notification->getTargetValue());
            $templateId = $channel->getTemplateId();

            if ($templateId === null) {
                return;
            }

            $template = $this->templateRepository->getById($templateId);
            $decoded  = json_decode($template->getTemplateJson(), true) ?? [];
            $resolved = $this->substitutor->substitute($decoded, $notification->getTemplateData());

            $message->setCardBody($resolved['body'] ?? []);
            $message->setCardActions($resolved['actions'] ?? []);

            if (isset($resolved['version'])) {
                $message->setCardVersion((string) $resolved['version']);
            }
        } catch (\Throwable $e) {
            $this->logger->warning(
                'Muon_TeamsNotifierCore: could not apply template for channel "{channel}" '
                . 'during consume — using serialised card body.',
                ['channel' => $notification->getTargetValue(), 'error' => $e->getMessage()]
            );
        }
    }

    /**
     * Handle a delivery failure: retry with backoff or abandon with event dispatch.
     *
     * @param \Muon\TeamsNotifierCore\Api\Data\QueuedNotificationInterface $notification
     * @param \Throwable $exception
     * @return void
     */
    private function handleFailure(QueuedNotificationInterface $notification, \Throwable $exception): void
    {
        $attempt     = $notification->getAttempt();
        $maxAttempts = $this->config->getMaxAttempts();

        $this->logger->warning(
            'Muon_TeamsNotifierCore: delivery attempt {attempt}/{max} failed.',
            [
                'attempt' => $attempt,
                'max'     => $maxAttempts,
                'target'  => $notification->getTargetValue(),
                'error'   => $exception->getMessage(),
            ]
        );

        if ($attempt < $maxAttempts) {
            $delay = $this->config->getRetryDelay()
                * ($this->config->getBackoffMultiplier() ** ($attempt - 1));

            $notification->setAttempt($attempt + 1);
            $notification->setAvailableAt(time() + (int) $delay);
            $this->publisher->publish($notification);

            $this->logger->info(
                'Muon_TeamsNotifierCore: scheduled retry attempt {next} in {delay}s.',
                ['next' => $attempt + 1, 'delay' => $delay]
            );

            return;
        }

        $this->logger->critical(
            'Muon_TeamsNotifierCore: notification permanently failed after {max} attempts.',
            [
                'max'         => $maxAttempts,
                'target_type' => $notification->getTargetType(),
                'target'      => $notification->getTargetValue(),
                'format'      => $notification->getMessageFormat(),
                'error'       => $exception->getMessage(),
            ]
        );

        $this->eventManager->dispatch('muon_teams_notifier_core_delivery_failed', [
            'notification' => $notification,
            'exception'    => $exception,
        ]);
    }
}
