<?php

declare(strict_types=1);

namespace Muon\TeamsNotifierCore\Api\Data;

/**
 * Serialisable DTO carrying everything needed to deliver one notification from the queue consumer.
 *
 * @api
 */
interface QueuedNotificationInterface
{
    public const TARGET_TYPE_CHANNEL = 'channel';
    public const TARGET_TYPE_WEBHOOK = 'webhook';

    /**
     * Get the serialised message fields (title, summary, themeColor, card body, etc.).
     *
     * @return array<string, mixed>
     */
    public function getMessageData(): array;

    /**
     * Set the serialised message fields.
     *
     * @param array $data
     * @return $this
     */
    public function setMessageData(array $data): static;

    /**
     * Get the message format — one of MessageInterface::FORMAT_* constants.
     *
     * @return string
     */
    public function getMessageFormat(): string;

    /**
     * Set the message format.
     *
     * @param string $format
     * @return $this
     */
    public function setMessageFormat(string $format): static;

    /**
     * Get the target type — "channel" (named) or "webhook" (ad-hoc URL).
     *
     * @return string
     */
    public function getTargetType(): string;

    /**
     * Set the target type.
     *
     * @param string $type
     * @return $this
     */
    public function setTargetType(string $type): static;

    /**
     * Get the target value — channel name slug or raw webhook URL.
     *
     * @return string
     */
    public function getTargetValue(): string;

    /**
     * Set the target value.
     *
     * @param string $value
     * @return $this
     */
    public function setTargetValue(string $value): static;

    /**
     * Get the optional TriggerSecret authentication header value.
     *
     * Returns an empty string when no authentication is required.
     *
     * @return string
     */
    public function getTriggerSecret(): string;

    /**
     * Set the TriggerSecret authentication header value.
     *
     * @param string $triggerSecret
     * @return $this
     */
    public function setTriggerSecret(string $triggerSecret): static;

    /**
     * Get the current delivery attempt number (1-based).
     *
     * @return int
     */
    public function getAttempt(): int;

    /**
     * Set the delivery attempt number.
     *
     * @param int $attempt
     * @return $this
     */
    public function setAttempt(int $attempt): static;

    /**
     * Get the Unix timestamp at which the consumer should attempt delivery.
     *
     * A value of 0 means "deliver immediately".
     *
     * @return int
     */
    public function getAvailableAt(): int;

    /**
     * Set the Unix timestamp at which the consumer should attempt delivery.
     *
     * @param int $timestamp
     * @return $this
     */
    public function setAvailableAt(int $timestamp): static;

    /**
     * Get the template variable data used for ${placeholder} substitution at delivery time.
     *
     * Returns an empty array when no template data was supplied at enqueue time.
     *
     * @return array
     */
    public function getTemplateData(): array;

    /**
     * Set the template variable data for ${placeholder} substitution.
     *
     * @param array $data
     * @return $this
     */
    public function setTemplateData(array $data): static;
}
