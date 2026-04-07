<?php

declare(strict_types=1);

namespace Muon\TeamsNotifierCore\Api;

use Muon\TeamsNotifierCore\Api\Data\MessageInterface;

/**
 * Teams notification service — primary entry point for all consuming modules.
 *
 * Delivery is synchronous or queued depending on the "Delivery Mode" admin setting.
 * In async mode both methods enqueue a job and return immediately; HTTP errors are
 * handled by the queue consumer's retry logic and are never propagated to the caller.
 *
 * @api
 */
interface TeamsNotifierInterface
{
    /**
     * Send a message to a named channel.
     *
     * When $channelName is null, the admin-configured default channel is used.
     * The channel must exist and be active; otherwise a LocalizedException is thrown
     * even in async mode (channel lookup happens synchronously).
     *
     * If the channel has an Adaptive Card template assigned, the template is resolved
     * with $data before delivery. Unknown placeholders are left as-is.
     *
     * @param \Muon\TeamsNotifierCore\Api\Data\MessageInterface $message
     * @param string|null $channelName Slug of a saved channel, or null for the default.
     * @param array $data Key→value map for ${placeholder} substitution in the channel template.
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException  Channel not found.
     * @throws \Magento\Framework\Exception\LocalizedException      Module disabled or channel inactive.
     */
    public function send(MessageInterface $message, ?string $channelName = null, array $data = []): void;

    /**
     * Send a message to an arbitrary webhook URL, bypassing channel lookup entirely.
     *
     * Useful when the caller manages its own webhook URL or needs a one-off override.
     * In async mode the message is queued and delivery errors are not propagated.
     *
     * @param \Muon\TeamsNotifierCore\Api\Data\MessageInterface $message
     * @param string $webhookUrl Full HTTPS Incoming Webhook URL.
     * @param array $data Key→value map for ${placeholder} substitution (unused without a template).
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException Sync delivery failure or module disabled.
     */
    public function sendToWebhook(MessageInterface $message, string $webhookUrl, array $data = []): void;
}
