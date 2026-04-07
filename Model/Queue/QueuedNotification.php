<?php

declare(strict_types=1);

namespace Muon\TeamsNotifierCore\Model\Queue;

use Muon\TeamsNotifierCore\Api\Data\QueuedNotificationInterface;

/**
 * Queue message DTO — carries all data needed to reconstruct and deliver one notification.
 */
class QueuedNotification implements QueuedNotificationInterface
{
    /** @var array<string, mixed> */
    private array $messageData  = [];
    /** @var string */
    private string $messageFormat = '';
    /** @var string */
    private string $targetType    = '';
    /** @var string */
    private string $targetValue   = '';
    /** @var string */
    private string $triggerSecret = '';
    /** @var int */
    private int $attempt        = 1;
    /** @var int */
    private int $availableAt    = 0;
    /** @var array */
    private array $templateData = [];

    /**
     * Get the serialised message fields.
     *
     * @return array<string, mixed>
     */
    public function getMessageData(): array
    {
        return $this->messageData;
    }

    /**
     * Set the serialised message fields.
     *
     * @param array $data
     * @return $this
     */
    public function setMessageData(array $data): static
    {
        $this->messageData = $data;
        return $this;
    }

    /**
     * Get the message format discriminator.
     *
     * @return string
     */
    public function getMessageFormat(): string
    {
        return $this->messageFormat;
    }

    /**
     * Set the message format discriminator.
     *
     * @param string $format
     * @return $this
     */
    public function setMessageFormat(string $format): static
    {
        $this->messageFormat = $format;
        return $this;
    }

    /**
     * Get the target type ("channel" or "webhook").
     *
     * @return string
     */
    public function getTargetType(): string
    {
        return $this->targetType;
    }

    /**
     * Set the target type.
     *
     * @param string $type
     * @return $this
     */
    public function setTargetType(string $type): static
    {
        $this->targetType = $type;
        return $this;
    }

    /**
     * Get the target value (channel name slug or raw webhook URL).
     *
     * @return string
     */
    public function getTargetValue(): string
    {
        return $this->targetValue;
    }

    /**
     * Set the target value.
     *
     * @param string $value
     * @return $this
     */
    public function setTargetValue(string $value): static
    {
        $this->targetValue = $value;
        return $this;
    }

    /**
     * Get the optional TriggerSecret authentication header value.
     *
     * @return string
     */
    public function getTriggerSecret(): string
    {
        return $this->triggerSecret;
    }

    /**
     * Set the TriggerSecret authentication header value.
     *
     * @param string $triggerSecret
     * @return $this
     */
    public function setTriggerSecret(string $triggerSecret): static
    {
        $this->triggerSecret = $triggerSecret;
        return $this;
    }

    /**
     * Get the current delivery attempt number (1-based).
     *
     * @return int
     */
    public function getAttempt(): int
    {
        return $this->attempt;
    }

    /**
     * Set the delivery attempt number.
     *
     * @param int $attempt
     * @return $this
     */
    public function setAttempt(int $attempt): static
    {
        $this->attempt = $attempt;
        return $this;
    }

    /**
     * Get the Unix timestamp at which the consumer should attempt delivery.
     *
     * @return int
     */
    public function getAvailableAt(): int
    {
        return $this->availableAt;
    }

    /**
     * Set the Unix timestamp at which the consumer should attempt delivery.
     *
     * @param int $timestamp
     * @return $this
     */
    public function setAvailableAt(int $timestamp): static
    {
        $this->availableAt = $timestamp;
        return $this;
    }

    /**
     * Get the template variable data for ${placeholder} substitution at delivery time.
     *
     * @return array
     */
    public function getTemplateData(): array
    {
        return $this->templateData;
    }

    /**
     * Set the template variable data for ${placeholder} substitution.
     *
     * @param array $data
     * @return $this
     */
    public function setTemplateData(array $data): static
    {
        $this->templateData = $data;
        return $this;
    }
}
