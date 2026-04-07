<?php

declare(strict_types=1);

namespace Muon\TeamsNotifierCore\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Muon\TeamsNotifierCore\Api\Data\TemplateInterface;

/**
 * CRUD repository for Adaptive Card templates.
 *
 * @api
 */
interface TemplateRepositoryInterface
{
    /**
     * Save a template.
     *
     * Validates the template JSON before persisting.
     *
     * @param \Muon\TeamsNotifierCore\Api\Data\TemplateInterface $template
     * @return \Muon\TeamsNotifierCore\Api\Data\TemplateInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\LocalizedException If JSON is invalid.
     */
    public function save(TemplateInterface $template): TemplateInterface;

    /**
     * Load a template by its primary key.
     *
     * @param int $templateId
     * @return \Muon\TeamsNotifierCore\Api\Data\TemplateInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById(int $templateId): TemplateInterface;

    /**
     * Load a template by its unique name slug.
     *
     * @param string $name
     * @return \Muon\TeamsNotifierCore\Api\Data\TemplateInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getByName(string $name): TemplateInterface;

    /**
     * Delete a template.
     *
     * @param \Muon\TeamsNotifierCore\Api\Data\TemplateInterface $template
     * @return void
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function delete(TemplateInterface $template): void;

    /**
     * Delete a template by its primary key.
     *
     * @param int $templateId
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function deleteById(int $templateId): void;

    /**
     * Search for templates matching the given criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $criteria
     * @return \Magento\Framework\Api\SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $criteria): SearchResultsInterface;
}
