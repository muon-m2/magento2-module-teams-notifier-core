<?php

declare(strict_types=1);

namespace Muon\TeamsNotifierCore\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Typed accessor for Muon_TeamsNotifierCore admin configuration.
 */
class Config
{
    private const XML_ENABLED            = 'muon_teamsnotifiercore/general/enabled';
    private const XML_DEFAULT_CHANNEL    = 'muon_teamsnotifiercore/general/default_channel';
    private const XML_TIMEOUT            = 'muon_teamsnotifiercore/general/timeout';
    private const XML_DELIVERY_MODE      = 'muon_teamsnotifiercore/general/delivery_mode';
    private const XML_MAX_ATTEMPTS       = 'muon_teamsnotifiercore/queue/max_attempts';
    private const XML_RETRY_DELAY        = 'muon_teamsnotifiercore/queue/retry_delay';
    private const XML_BACKOFF_MULTIPLIER = 'muon_teamsnotifiercore/queue/backoff_multiplier';

    public const DELIVERY_MODE_SYNC  = 'sync';
    public const DELIVERY_MODE_ASYNC = 'async';

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * Check whether the module is globally enabled.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_ENABLED, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get the default channel name slug.
     *
     * @return string
     */
    public function getDefaultChannelName(): string
    {
        return (string) $this->scopeConfig->getValue(self::XML_DEFAULT_CHANNEL, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get the cURL timeout in seconds.
     *
     * @return int
     */
    public function getTimeout(): int
    {
        return max(1, (int) $this->scopeConfig->getValue(self::XML_TIMEOUT, ScopeInterface::SCOPE_STORE));
    }

    /**
     * Get the configured delivery mode ("sync" or "async").
     *
     * @return string
     */
    public function getDeliveryMode(): string
    {
        return (string) $this->scopeConfig->getValue(self::XML_DELIVERY_MODE, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Check whether async (queue) delivery is enabled.
     *
     * @return bool
     */
    public function isAsyncMode(): bool
    {
        return $this->getDeliveryMode() === self::DELIVERY_MODE_ASYNC;
    }

    /**
     * Get the maximum number of delivery attempts before a notification is abandoned.
     *
     * @return int
     */
    public function getMaxAttempts(): int
    {
        return max(1, (int) $this->scopeConfig->getValue(self::XML_MAX_ATTEMPTS, ScopeInterface::SCOPE_STORE));
    }

    /**
     * Get the base retry delay in seconds.
     *
     * @return int
     */
    public function getRetryDelay(): int
    {
        return max(1, (int) $this->scopeConfig->getValue(self::XML_RETRY_DELAY, ScopeInterface::SCOPE_STORE));
    }

    /**
     * Get the exponential backoff multiplier.
     *
     * @return int
     */
    public function getBackoffMultiplier(): int
    {
        return max(1, (int) $this->scopeConfig->getValue(self::XML_BACKOFF_MULTIPLIER, ScopeInterface::SCOPE_STORE));
    }
}
