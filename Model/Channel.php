<?php

declare(strict_types=1);

namespace Muon\TeamsNotifierCore\Model;

use Magento\Framework\Model\AbstractModel;
use Muon\TeamsNotifierCore\Api\Data\ChannelInterface;
use Muon\TeamsNotifierCore\Model\ResourceModel\Channel as ChannelResource;

/**
 * Teams notification channel ORM model.
 */
class Channel extends AbstractModel implements ChannelInterface
{
    /**
     * Initialise the resource model.
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(ChannelResource::class);
    }

    /**
     * Get the channel primary key.
     *
     * @return int|null
     */
    public function getChannelId(): ?int
    {
        $value = $this->getData(self::CHANNEL_ID);
        return $value !== null ? (int) $value : null;
    }

    /**
     * Set the channel primary key.
     *
     * @param int $channelId
     * @return $this
     */
    public function setChannelId(int $channelId): static
    {
        return $this->setData(self::CHANNEL_ID, $channelId);
    }

    /**
     * Get the unique channel name slug.
     *
     * @return string
     */
    public function getName(): string
    {
        return (string) $this->getData(self::NAME);
    }

    /**
     * Set the unique channel name slug.
     *
     * @param string $name
     * @return $this
     */
    public function setName(string $name): static
    {
        return $this->setData(self::NAME, $name);
    }

    /**
     * Get the human-readable label.
     *
     * @return string
     */
    public function getLabel(): string
    {
        return (string) $this->getData(self::LABEL);
    }

    /**
     * Set the human-readable label.
     *
     * @param string $label
     * @return $this
     */
    public function setLabel(string $label): static
    {
        return $this->setData(self::LABEL, $label);
    }

    /**
     * Get the decrypted Workflows webhook URL.
     *
     * @return string
     */
    public function getWebhookUrl(): string
    {
        return (string) $this->getData(self::WEBHOOK_URL);
    }

    /**
     * Set the Workflows webhook URL (encrypted transparently on persist).
     *
     * @param string $webhookUrl
     * @return $this
     */
    public function setWebhookUrl(string $webhookUrl): static
    {
        return $this->setData(self::WEBHOOK_URL, $webhookUrl);
    }

    /**
     * Get the optional decrypted TriggerSecret for authentication.
     *
     * @return string
     */
    public function getTriggerSecret(): string
    {
        return (string) $this->getData(self::TRIGGER_SECRET);
    }

    /**
     * Set the TriggerSecret (encrypted transparently on persist).
     *
     * @param string $triggerSecret
     * @return $this
     */
    public function setTriggerSecret(string $triggerSecret): static
    {
        return $this->setData(self::TRIGGER_SECRET, $triggerSecret);
    }

    /**
     * Check whether this channel accepts notifications.
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     * Magento convention: is_active DB column maps to getIsActive() — renaming would break Service Contract.
     */
    public function getIsActive(): bool
    {
        return (bool) $this->getData(self::IS_ACTIVE);
    }

    /**
     * Enable or disable this channel.
     *
     * @param bool $isActive
     * @return $this
     */
    public function setIsActive(bool $isActive): static
    {
        return $this->setData(self::IS_ACTIVE, (int) $isActive);
    }

    /**
     * Get the ID of the Adaptive Card template assigned to this channel.
     *
     * @return int|null
     */
    public function getTemplateId(): ?int
    {
        $value = $this->getData(self::TEMPLATE_ID);
        return $value !== null ? (int) $value : null;
    }

    /**
     * Set the Adaptive Card template ID for this channel.
     *
     * @param int|null $templateId
     * @return $this
     */
    public function setTemplateId(?int $templateId): static
    {
        return $this->setData(self::TEMPLATE_ID, $templateId);
    }

    /**
     * Get the ISO-8601 creation timestamp.
     *
     * @return string
     */
    public function getCreatedAt(): string
    {
        return (string) $this->getData(self::CREATED_AT);
    }

    /**
     * Get the ISO-8601 last-update timestamp.
     *
     * @return string
     */
    public function getUpdatedAt(): string
    {
        return (string) $this->getData(self::UPDATED_AT);
    }
}
