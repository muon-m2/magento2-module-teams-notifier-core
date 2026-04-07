<?php

declare(strict_types=1);

namespace Muon\TeamsNotifierCore\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Muon\TeamsNotifierCore\Api\Data\ChannelInterface;

/**
 * Teams notification channel CRUD repository.
 *
 * @api
 */
interface ChannelRepositoryInterface
{
    /**
     * Persist a channel entity.
     *
     * @param \Muon\TeamsNotifierCore\Api\Data\ChannelInterface $channel
     * @return \Muon\TeamsNotifierCore\Api\Data\ChannelInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(ChannelInterface $channel): ChannelInterface;

    /**
     * Load a channel by its primary key.
     *
     * @param int $channelId
     * @return \Muon\TeamsNotifierCore\Api\Data\ChannelInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById(int $channelId): ChannelInterface;

    /**
     * Load a channel by its unique name slug.
     *
     * @param string $name
     * @return \Muon\TeamsNotifierCore\Api\Data\ChannelInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getByName(string $name): ChannelInterface;

    /**
     * Delete a channel entity.
     *
     * @param \Muon\TeamsNotifierCore\Api\Data\ChannelInterface $channel
     * @return void
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function delete(ChannelInterface $channel): void;

    /**
     * Delete a channel by primary key.
     *
     * @param int $channelId
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function deleteById(int $channelId): void;

    /**
     * Retrieve a list of channels matching the given search criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $criteria
     * @return \Magento\Framework\Api\SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $criteria): SearchResultsInterface;
}
