<?php

declare(strict_types=1);

namespace Muon\TeamsNotifierCore\Model\Queue;

use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Muon\TeamsNotifierCore\Api\Data\QueuedNotificationInterface;

/**
 * Publishes a QueuedNotification to the Teams notifier AMQP topic.
 */
class Publisher
{
    private const TOPIC = 'muon.teams_notifier_core.send';

    /**
     * @param \Magento\Framework\MessageQueue\PublisherInterface $messagePublisher
     * @param \Magento\Framework\Serialize\Serializer\Json       $json
     */
    public function __construct(
        private readonly PublisherInterface $messagePublisher,
        private readonly Json               $json
    ) {
    }

    /**
     * Serialise the notification to JSON and publish it to the queue.
     *
     * @param \Muon\TeamsNotifierCore\Api\Data\QueuedNotificationInterface $notification
     * @return void
     */
    public function publish(QueuedNotificationInterface $notification): void
    {
        $payload = $this->json->serialize([
            'message_data'   => $notification->getMessageData(),
            'message_format' => $notification->getMessageFormat(),
            'target_type'    => $notification->getTargetType(),
            'target_value'   => $notification->getTargetValue(),
            'trigger_secret' => $notification->getTriggerSecret(),
            'attempt'        => $notification->getAttempt(),
            'available_at'   => $notification->getAvailableAt(),
        ]);

        $this->messagePublisher->publish(self::TOPIC, $payload);
    }
}
