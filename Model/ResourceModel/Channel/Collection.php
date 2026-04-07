<?php

declare(strict_types=1);

namespace Muon\TeamsNotifierCore\Model\ResourceModel\Channel;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Muon\TeamsNotifierCore\Model\Channel;
use Muon\TeamsNotifierCore\Model\ResourceModel\Channel as ChannelResource;

/**
 * Teams notification channel collection.
 *
 * Note: webhook_url values remain encrypted when retrieved via collection;
 * decrypt individually via the repository or model load if the URL is needed.
 */
class Collection extends AbstractCollection
{
    /**
     * Initialise the collection model and resource model bindings.
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(Channel::class, ChannelResource::class);
    }
}
