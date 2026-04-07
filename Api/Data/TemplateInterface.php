<?php

declare(strict_types=1);

namespace Muon\TeamsNotifierCore\Api\Data;

/**
 * Adaptive Card template entity.
 *
 * A template stores a reusable Adaptive Card JSON structure that can be assigned
 * to one or more channels. Templates may contain ${placeholder} expressions that
 * are resolved with caller-supplied data at send time.
 *
 * @api
 */
interface TemplateInterface
{
    public const TEMPLATE_ID   = 'template_id';
    public const NAME          = 'name';
    public const LABEL         = 'label';
    public const TEMPLATE_JSON = 'template_json';
    public const CREATED_AT    = 'created_at';
    public const UPDATED_AT    = 'updated_at';

    /**
     * Get the template primary key.
     *
     * @return int|null
     */
    public function getTemplateId(): ?int;

    /**
     * Set the template primary key.
     *
     * @param int $templateId
     * @return $this
     */
    public function setTemplateId(int $templateId): static;

    /**
     * Get the unique template name slug used in code (e.g. "order-alert").
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Set the unique template name slug.
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
     * Get the raw Adaptive Card JSON string.
     *
     * The JSON root must be an Adaptive Card object with "type": "AdaptiveCard",
     * "body", and "version" fields. String values may contain ${placeholder}
     * expressions resolved at send time.
     *
     * @return string
     */
    public function getTemplateJson(): string;

    /**
     * Set the raw Adaptive Card JSON string.
     *
     * @param string $json
     * @return $this
     */
    public function setTemplateJson(string $json): static;

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
