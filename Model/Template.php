<?php

declare(strict_types=1);

namespace Muon\TeamsNotifierCore\Model;

use Magento\Framework\Model\AbstractModel;
use Muon\TeamsNotifierCore\Api\Data\TemplateInterface;
use Muon\TeamsNotifierCore\Model\ResourceModel\Template as TemplateResource;

/**
 * Adaptive Card template ORM model.
 */
class Template extends AbstractModel implements TemplateInterface
{
    /**
     * Initialise the resource model.
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(TemplateResource::class);
    }

    /**
     * Get the template primary key.
     *
     * @return int|null
     */
    public function getTemplateId(): ?int
    {
        $value = $this->getData(self::TEMPLATE_ID);
        return $value !== null ? (int) $value : null;
    }

    /**
     * Set the template primary key.
     *
     * @param int $templateId
     * @return $this
     */
    public function setTemplateId(int $templateId): static
    {
        return $this->setData(self::TEMPLATE_ID, $templateId);
    }

    /**
     * Get the unique template name slug.
     *
     * @return string
     */
    public function getName(): string
    {
        return (string) $this->getData(self::NAME);
    }

    /**
     * Set the unique template name slug.
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
     * Get the raw Adaptive Card JSON string.
     *
     * @return string
     */
    public function getTemplateJson(): string
    {
        return (string) $this->getData(self::TEMPLATE_JSON);
    }

    /**
     * Set the raw Adaptive Card JSON string.
     *
     * @param string $json
     * @return $this
     */
    public function setTemplateJson(string $json): static
    {
        return $this->setData(self::TEMPLATE_JSON, $json);
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
