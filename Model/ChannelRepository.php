<?php

declare(strict_types=1);

namespace Muon\TeamsNotifierCore\Model;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Muon\TeamsNotifierCore\Api\ChannelRepositoryInterface;
use Muon\TeamsNotifierCore\Api\Data\ChannelInterface;
use Muon\TeamsNotifierCore\Model\ResourceModel\Channel as ChannelResource;
use Muon\TeamsNotifierCore\Model\ResourceModel\Channel\CollectionFactory;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * Repository — coupling is inherent to coordinating model factory, resource model, collection, and search results.
 */
class ChannelRepository implements ChannelRepositoryInterface
{
    /**
     * @param \Muon\TeamsNotifierCore\Model\ResourceModel\Channel                        $resource
     * @param \Muon\TeamsNotifierCore\Model\ChannelFactory                               $channelFactory
     * @param \Muon\TeamsNotifierCore\Model\ResourceModel\Channel\CollectionFactory      $collectionFactory
     * @param \Magento\Framework\Api\SearchResultsInterfaceFactory                       $searchResultsFactory
     */
    public function __construct(
        private readonly ChannelResource              $resource,
        private readonly ChannelFactory               $channelFactory,
        private readonly CollectionFactory            $collectionFactory,
        private readonly SearchResultsInterfaceFactory $searchResultsFactory
    ) {
    }

    /**
     * Persist a channel entity.
     *
     * @param \Muon\TeamsNotifierCore\Api\Data\ChannelInterface $channel
     * @return \Muon\TeamsNotifierCore\Api\Data\ChannelInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(ChannelInterface $channel): ChannelInterface
    {
        try {
            $this->resource->save($channel);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('Could not save channel: %1', $e->getMessage()), $e);
        }

        return $channel;
    }

    /**
     * Load a channel by primary key.
     *
     * @param int $channelId
     * @return \Muon\TeamsNotifierCore\Api\Data\ChannelInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById(int $channelId): ChannelInterface
    {
        /** @var \Muon\TeamsNotifierCore\Model\Channel $channel */
        $channel = $this->channelFactory->create();
        $this->resource->load($channel, $channelId);

        if (!$channel->getChannelId()) {
            throw new NoSuchEntityException(
                __('Teams channel with ID "%1" does not exist.', $channelId)
            );
        }

        return $channel;
    }

    /**
     * Load a channel by unique name slug.
     *
     * @param string $name
     * @return \Muon\TeamsNotifierCore\Api\Data\ChannelInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getByName(string $name): ChannelInterface
    {
        /** @var \Muon\TeamsNotifierCore\Model\Channel $channel */
        $channel = $this->channelFactory->create();
        $this->resource->load($channel, $name, ChannelInterface::NAME);

        if (!$channel->getChannelId()) {
            throw new NoSuchEntityException(
                __('Teams channel "%1" does not exist.', $name)
            );
        }

        return $channel;
    }

    /**
     * Delete a channel entity.
     *
     * @param \Muon\TeamsNotifierCore\Api\Data\ChannelInterface $channel
     * @return void
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function delete(ChannelInterface $channel): void
    {
        try {
            $this->resource->delete($channel);
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(__('Could not delete channel: %1', $e->getMessage()), $e);
        }
    }

    /**
     * Delete a channel by primary key.
     *
     * @param int $channelId
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function deleteById(int $channelId): void
    {
        $this->delete($this->getById($channelId));
    }

    /**
     * Retrieve a list of channels matching the given search criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $criteria
     * @return \Magento\Framework\Api\SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $criteria): SearchResultsInterface
    {
        $collection = $this->collectionFactory->create();

        foreach ($criteria->getFilterGroups() as $filterGroup) {
            foreach ($filterGroup->getFilters() as $filter) {
                $condition = $filter->getConditionType() ?: 'eq';
                $collection->addFieldToFilter($filter->getField(), [$condition => $filter->getValue()]);
            }
        }

        foreach ($criteria->getSortOrders() ?? [] as $sortOrder) {
            $collection->addOrder($sortOrder->getField(), $sortOrder->getDirection());
        }

        $collection->setCurPage($criteria->getCurrentPage());
        $collection->setPageSize($criteria->getPageSize());

        /** @var \Magento\Framework\Api\SearchResultsInterface $results */
        $results = $this->searchResultsFactory->create();
        $results->setSearchCriteria($criteria);
        $results->setItems($collection->getItems());
        $results->setTotalCount($collection->getSize());

        return $results;
    }
}
