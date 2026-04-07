<?php

declare(strict_types=1);

namespace Muon\TeamsNotifierCore\Model\ResourceModel\Template;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Muon\TeamsNotifierCore\Model\Template;
use Muon\TeamsNotifierCore\Model\ResourceModel\Template as TemplateResource;

/**
 * Collection for the Adaptive Card template entity.
 */
class Collection extends AbstractCollection
{
    /**
     * Initialise the collection with model and resource model.
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(Template::class, TemplateResource::class);
    }
}
