<?php

declare(strict_types=1);

namespace Muon\TeamsNotifierCore\Model;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Muon\TeamsNotifierCore\Api\Data\TemplateInterface;
use Muon\TeamsNotifierCore\Api\TemplateRepositoryInterface;
use Muon\TeamsNotifierCore\Model\ResourceModel\Template as TemplateResource;
use Muon\TeamsNotifierCore\Model\ResourceModel\Template\CollectionFactory;
use Muon\TeamsNotifierCore\Model\Template\JsonValidator;

/**
 * CRUD repository for Adaptive Card templates.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * Repository pattern — coupling is inherent to coordinating model factory, resource model,
 * collection factory, search results factory, and JSON validator.
 */
class TemplateRepository implements TemplateRepositoryInterface
{
    /**
     * @param \Muon\TeamsNotifierCore\Model\TemplateFactory $templateFactory
     * @param \Muon\TeamsNotifierCore\Model\ResourceModel\Template $resource
     * @param \Muon\TeamsNotifierCore\Model\ResourceModel\Template\CollectionFactory $collectionFactory
     * @param \Magento\Framework\Api\SearchResultsInterfaceFactory $searchResultsFactory
     * @param \Muon\TeamsNotifierCore\Model\Template\JsonValidator $jsonValidator
     */
    public function __construct(
        private readonly TemplateFactory $templateFactory,
        private readonly TemplateResource $resource,
        private readonly CollectionFactory $collectionFactory,
        private readonly SearchResultsInterfaceFactory $searchResultsFactory,
        private readonly JsonValidator $jsonValidator
    ) {
    }

    /**
     * Save a template after validating its JSON.
     *
     * @param \Muon\TeamsNotifierCore\Api\Data\TemplateInterface $template
     * @return \Muon\TeamsNotifierCore\Api\Data\TemplateInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(TemplateInterface $template): TemplateInterface
    {
        $this->jsonValidator->validate($template->getTemplateJson());

        try {
            $this->resource->save($template);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(
                __('Could not save the template: %1', $e->getMessage()),
                $e
            );
        }

        return $template;
    }

    /**
     * Load a template by its primary key.
     *
     * @param int $templateId
     * @return \Muon\TeamsNotifierCore\Api\Data\TemplateInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById(int $templateId): TemplateInterface
    {
        $template = $this->templateFactory->create();
        $this->resource->load($template, $templateId);

        if ($template->getTemplateId() === null) {
            throw new NoSuchEntityException(
                __('Template with ID "%1" does not exist.', $templateId)
            );
        }

        return $template;
    }

    /**
     * Load a template by its unique name slug.
     *
     * @param string $name
     * @return \Muon\TeamsNotifierCore\Api\Data\TemplateInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getByName(string $name): TemplateInterface
    {
        $template = $this->templateFactory->create();
        $this->resource->load($template, $name, TemplateInterface::NAME);

        if ($template->getTemplateId() === null) {
            throw new NoSuchEntityException(
                __('Template with name "%1" does not exist.', $name)
            );
        }

        return $template;
    }

    /**
     * Delete a template.
     *
     * @param \Muon\TeamsNotifierCore\Api\Data\TemplateInterface $template
     * @return void
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function delete(TemplateInterface $template): void
    {
        try {
            $this->resource->delete($template);
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(
                __('Could not delete the template: %1', $e->getMessage()),
                $e
            );
        }
    }

    /**
     * Delete a template by its primary key.
     *
     * @param int $templateId
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function deleteById(int $templateId): void
    {
        $this->delete($this->getById($templateId));
    }

    /**
     * Search for templates matching the given criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $criteria
     * @return \Magento\Framework\Api\SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $criteria): SearchResultsInterface
    {
        $collection = $this->collectionFactory->create();

        foreach ($criteria->getFilterGroups() as $filterGroup) {
            $this->applyFilterGroup($collection, $filterGroup);
        }

        $sortOrders = $criteria->getSortOrders();
        if ($sortOrders) {
            foreach ($sortOrders as $sortOrder) {
                $collection->addOrder(
                    $sortOrder->getField(),
                    $sortOrder->getDirection()
                );
            }
        }

        $collection->setCurPage($criteria->getCurrentPage());
        $collection->setPageSize($criteria->getPageSize());
        $collection->load();

        $results = $this->searchResultsFactory->create();
        $results->setSearchCriteria($criteria);
        $results->setItems($collection->getItems());
        $results->setTotalCount($collection->getSize());

        return $results;
    }

    /**
     * Apply a filter group to the collection.
     *
     * @param \Muon\TeamsNotifierCore\Model\ResourceModel\Template\Collection $collection
     * @param \Magento\Framework\Api\Search\FilterGroup $filterGroup
     * @return void
     */
    private function applyFilterGroup(
        \Muon\TeamsNotifierCore\Model\ResourceModel\Template\Collection $collection,
        FilterGroup $filterGroup
    ): void {
        foreach ($filterGroup->getFilters() as $filter) {
            $condition = $filter->getConditionType() ?: 'eq';
            $collection->addFieldToFilter($filter->getField(), [$condition => $filter->getValue()]);
        }
    }
}
