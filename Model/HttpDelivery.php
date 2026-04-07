<?php

declare(strict_types=1);

namespace Muon\TeamsNotifierCore\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Client\Curl;
use Muon\TeamsNotifierCore\Api\Data\MessageInterface;
use Muon\TeamsNotifierCore\Model\Payload\PayloadBuilderInterface;
use Psr\Log\LoggerInterface;

/**
 * Low-level HTTP delivery for Teams Workflows webhooks.
 *
 * POSTs an Adaptive Card payload to the given Workflows webhook URL.
 * If a TriggerSecret is provided, it is sent as the "TriggerSecret" HTTP header
 * for Power Automate webhook authentication.
 *
 * Supported webhook URL format (current as of 2025):
 *   https://<env-id>.aa.environment.api.powerplatform.com/powerautomate/automations/...
 *
 * This class has no retry logic; retries are the responsibility of the queue Consumer.
 */
class HttpDelivery
{
    /** Power Automate Workflows webhooks return 202 Accepted (asynchronous processing). */
    private const HTTP_ACCEPTED       = 202;
    private const TRIGGER_SECRET_HDR  = 'TriggerSecret';

    /**
     * @param \Muon\TeamsNotifierCore\Model\Config $config
     * @param \Magento\Framework\HTTP\Client\Curl $curl
     * @param \Psr\Log\LoggerInterface $logger
     * @param array $payloadBuilders
     */
    public function __construct(
        private readonly Config $config,
        private readonly Curl $curl,
        private readonly LoggerInterface $logger,
        private readonly array $payloadBuilders = []
    ) {
    }

    /**
     * Build the payload and POST it to the given Workflows webhook URL.
     *
     * @param \Muon\TeamsNotifierCore\Api\Data\MessageInterface $message
     * @param string $webhookUrl Workflows webhook URL.
     * @param string $triggerSecret Optional TriggerSecret for Power Automate authentication.
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException On HTTP error or missing builder.
     */
    public function post(MessageInterface $message, string $webhookUrl, string $triggerSecret = ''): void
    {
        $format  = $message->getFormat();
        $builder = $this->payloadBuilders[$format] ?? null;

        if (!$builder instanceof PayloadBuilderInterface) {
            throw new LocalizedException(
                __('No payload builder registered for message format "%1".', $format)
            );
        }

        $payload = $builder->build($message);

        $this->curl->setTimeout($this->config->getTimeout());
        $this->curl->addHeader('Content-Type', 'application/json');

        if ($triggerSecret !== '') {
            $this->curl->addHeader(self::TRIGGER_SECRET_HDR, $triggerSecret);
        }

        $this->curl->post($webhookUrl, $payload);

        $status = $this->curl->getStatus();

        if ($status !== self::HTTP_ACCEPTED) {
            $body = $this->curl->getBody();
            $this->logger->error(
                'Muon_TeamsNotifierCore: Teams Workflows webhook returned unexpected response.',
                ['status' => $status, 'body' => $body, 'webhook_url' => $this->maskUrl($webhookUrl)]
            );
            throw new LocalizedException(
                __('Teams webhook returned HTTP %1. Check logs for details.', $status)
            );
        }

        $this->logger->debug(
            'Muon_TeamsNotifierCore: notification delivered.',
            ['format' => $format, 'webhook_url' => $this->maskUrl($webhookUrl)]
        );
    }

    /**
     * Mask the webhook URL for safe logging (keeps the scheme + host, replaces path).
     *
     * @param string $url
     * @return string
     */
    private function maskUrl(string $url): string
    {
        $parsed = parse_url($url); // phpcs:ignore Magento2.Functions.DiscouragedFunction
        if ($parsed === false) {
            return '***';
        }
        return ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '') . '/***';
    }
}
