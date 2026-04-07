<?php

declare(strict_types=1);

namespace Muon\TeamsNotifierCore\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Resource model for the Adaptive Card template entity.
 */
class Template extends AbstractDb
{
    /**
     * Initialise the resource model with table name and primary key.
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init('muon_teamsnotifiercore_template', 'template_id');
    }
}
