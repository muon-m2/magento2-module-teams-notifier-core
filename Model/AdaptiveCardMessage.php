<?php

declare(strict_types=1);

namespace Muon\TeamsNotifierCore\Model;

use Muon\TeamsNotifierCore\Api\Data\AdaptiveCardMessageInterface;

/**
 * Adaptive Card notification value-object.
 */
class AdaptiveCardMessage implements AdaptiveCardMessageInterface
{
    /** @var string */
    private string $title       = '';
    /** @var string */
    private string $summary     = '';
    /** @var string */
    private string $themeColor  = self::DEFAULT_THEME_COLOR;
    /** @var array<int, array<string, mixed>> */
    private array $cardBody    = [];
    /** @var array<int, array<string, mixed>> */
    private array $cardActions = [];
    /** @var string */
    private string $cardVersion = self::DEFAULT_CARD_VERSION;

    /**
     * Get the wire format discriminator.
     *
     * @return string
     */
    public function getFormat(): string
    {
        return self::FORMAT_ADAPTIVE_CARD;
    }

    /**
     * Get the card title (mapped to a TextBlock in the body if non-empty).
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Set the card title.
     *
     * @param string $title
     * @return $this
     */
    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Get the plain-text notification summary.
     *
     * @return string
     */
    public function getSummary(): string
    {
        return $this->summary;
    }

    /**
     * Set the plain-text notification summary.
     *
     * @param string $summary
     * @return $this
     */
    public function setSummary(string $summary): static
    {
        $this->summary = $summary;
        return $this;
    }

    /**
     * Get the hex accent colour (not used in Adaptive Card wire format but kept for API consistency).
     *
     * @return string
     */
    public function getThemeColor(): string
    {
        return $this->themeColor;
    }

    /**
     * Set the hex accent colour.
     *
     * @param string $color
     * @return $this
     */
    public function setThemeColor(string $color): static
    {
        $this->themeColor = $color;
        return $this;
    }

    /**
     * Get the Adaptive Card body elements.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getCardBody(): array
    {
        return $this->cardBody;
    }

    /**
     * Set the Adaptive Card body elements.
     *
     * @param array $body
     * @return $this
     */
    public function setCardBody(array $body): static
    {
        $this->cardBody = $body;
        return $this;
    }

    /**
     * Get the Adaptive Card actions.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getCardActions(): array
    {
        return $this->cardActions;
    }

    /**
     * Set the Adaptive Card actions.
     *
     * @param array $actions
     * @return $this
     */
    public function setCardActions(array $actions): static
    {
        $this->cardActions = $actions;
        return $this;
    }

    /**
     * Get the Adaptive Card schema version string.
     *
     * @return string
     */
    public function getCardVersion(): string
    {
        return $this->cardVersion;
    }

    /**
     * Set the Adaptive Card schema version string.
     *
     * @param string $version
     * @return $this
     */
    public function setCardVersion(string $version): static
    {
        $this->cardVersion = $version;
        return $this;
    }
}
