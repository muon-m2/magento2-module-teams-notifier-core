<?php

declare(strict_types=1);

namespace Muon\TeamsNotifierCore\Api\Data;

/**
 * Base message interface for Teams notifications delivered via Workflows webhooks.
 *
 * Microsoft Teams Incoming Webhooks (webhook.office.com / Office 365 Connectors)
 * were fully retired on April 30, 2026. This module targets the replacement:
 * Workflows webhooks (*.aa.environment.api.powerplatform.com) which require
 * Adaptive Card payloads. Consequently, only FORMAT_ADAPTIVE_CARD is supported.
 *
 * @api
 */
interface MessageInterface
{
    public const FORMAT_ADAPTIVE_CARD = 'adaptive_card';

    public const DEFAULT_THEME_COLOR = '0078D4';

    /**
     * Get the wire format discriminator.
     *
     * @return string Always FORMAT_ADAPTIVE_CARD for current implementations.
     */
    public function getFormat(): string;

    /**
     * Get the card heading.
     *
     * @return string
     */
    public function getTitle(): string;

    /**
     * Set the card heading.
     *
     * @param string $title
     * @return $this
     */
    public function setTitle(string $title): static;

    /**
     * Get the plain-text summary shown in desktop/mobile notifications.
     *
     * @return string
     */
    public function getSummary(): string;

    /**
     * Set the plain-text summary shown in desktop/mobile notifications.
     *
     * @param string $summary
     * @return $this
     */
    public function setSummary(string $summary): static;

    /**
     * Get the hex accent colour used for visual differentiation (e.g. "0078D4").
     *
     * Note: themeColor is not rendered in Adaptive Cards; it is kept for API
     * consistency and may be used in future card designs.
     *
     * @return string
     */
    public function getThemeColor(): string;

    /**
     * Set the hex accent colour.
     *
     * @param string $color
     * @return $this
     */
    public function setThemeColor(string $color): static;
}
