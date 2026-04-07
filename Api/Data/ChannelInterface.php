<?php

declare(strict_types=1);

namespace Muon\TeamsNotifierCore\Api\Data;

/**
 * Teams notification channel entity.
 *
 * Channels represent named Workflows webhook endpoints created via the Teams
 * "Workflows" app (channel settings → Workflows → "Post to a channel when a
 * webhook request is received").
 *
 * @api
 */
interface ChannelInterface
{
    public const CHANNEL_ID     = 'channel_id';
    public const NAME           = 'name';
    public const LABEL          = 'label';
    public const WEBHOOK_URL    = 'webhook_url';
    public const TRIGGER_SECRET = 'trigger_secret';
    public const IS_ACTIVE      = 'is_active';
    public const TEMPLATE_ID    = 'template_id';
    public const CREATED_AT     = 'created_at';
    public const UPDATED_AT     = 'updated_at';

    /**
     * Get the channel primary key.
     *
     * @return int|null
     */
    public function getChannelId(): ?int;

    /**
     * Set the channel primary key.
     *
     * @param int $channelId
     * @return $this
     */
    public function setChannelId(int $channelId): static;

    /**
     * Get the unique channel name slug used in send() calls (e.g. "ops-alerts").
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Set the unique channel name slug.
     *
     * @param string $name
     * @return $this
     */
    public function setName(string $name): static;

    /**
     * Get the human-readable label shown in the admin grid.
     *
     * @return string
     */
    public function getLabel(): string;

    /**
     * Set the human-readable label.
     *
     * @param string $label
     * @return $this
     */
    public function setLabel(string $label): static;

    /**
     * Get the decrypted Workflows webhook URL.
     *
     * @return string
     */
    public function getWebhookUrl(): string;

    /**
     * Set the Workflows webhook URL (plaintext; encrypted transparently on persist).
     *
     * @param string $webhookUrl
     * @return $this
     */
    public function setWebhookUrl(string $webhookUrl): static;

    /**
     * Get the optional TriggerSecret value sent as an HTTP header for authentication.
     *
     * Returns an empty string when no secret is configured for this channel.
     * The secret is stored encrypted and decrypted on load.
     *
     * @return string
     */
    public function getTriggerSecret(): string;

    /**
     * Set the TriggerSecret value (plaintext; encrypted transparently on persist).
     *
     * Set to an empty string to remove authentication for this channel.
     *
     * @param string $triggerSecret
     * @return $this
     */
    public function setTriggerSecret(string $triggerSecret): static;

    /**
     * Check whether this channel accepts notifications.
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     * Magento convention: is_active DB column maps to getIsActive() — renaming would break Service Contract.
     */
    public function getIsActive(): bool;

    /**
     * Enable or disable this channel.
     *
     * @param bool $isActive
     * @return $this
     */
    public function setIsActive(bool $isActive): static;

    /**
     * Get the ID of the Adaptive Card template assigned to this channel, or null if none.
     *
     * @return int|null
     */
    public function getTemplateId(): ?int;

    /**
     * Set the Adaptive Card template ID for this channel.
     *
     * Pass null to remove the template association.
     *
     * @param int|null $templateId
     * @return $this
     */
    public function setTemplateId(?int $templateId): static;

    /**
     * Get the ISO-8601 creation timestamp.
     *
     * @return string
     */
    public function getCreatedAt(): string;

    /**
     * Get the ISO-8601 last-update timestamp.
     *
     * @return string
     */
    public function getUpdatedAt(): string;
}
