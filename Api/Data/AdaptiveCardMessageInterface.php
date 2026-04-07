<?php

declare(strict_types=1);

namespace Muon\TeamsNotifierCore\Api\Data;

/**
 * Adaptive Card format — sent via the Incoming Webhook envelope:
 * <pre>
 * {
 *   "type": "message",
 *   "attachments": [{
 *     "contentType": "application/vnd.microsoft.card.adaptive",
 *     "content": { "$schema": "...", "type": "AdaptiveCard", "version": "1.4", "body": [...], "actions": [...] }
 *   }]
 * }
 * </pre>
 *
 * @api
 */
interface AdaptiveCardMessageInterface extends MessageInterface
{
    public const DEFAULT_CARD_VERSION = '1.4';

    /**
     * Get the Adaptive Card body elements array.
     *
     * Elements follow the Adaptive Card schema (TextBlock, Image, ColumnSet, etc.).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getCardBody(): array;

    /**
     * Set the Adaptive Card body elements.
     *
     * @param array $body
     * @return $this
     */
    public function setCardBody(array $body): static;

    /**
     * Get the Adaptive Card actions array (Action.OpenUrl, Action.Submit, etc.).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getCardActions(): array;

    /**
     * Set the Adaptive Card actions.
     *
     * @param array $actions
     * @return $this
     */
    public function setCardActions(array $actions): static;

    /**
     * Get the Adaptive Card schema version string (e.g. "1.4").
     *
     * @return string
     */
    public function getCardVersion(): string;

    /**
     * Set the Adaptive Card schema version string.
     *
     * @param string $version
     * @return $this
     */
    public function setCardVersion(string $version): static;
}
