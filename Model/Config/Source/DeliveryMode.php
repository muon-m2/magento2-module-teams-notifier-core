<?php

declare(strict_types=1);

namespace Muon\TeamsNotifierCore\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Muon\TeamsNotifierCore\Model\Config;

/**
 * Delivery mode options for the admin system.xml select field.
 */
class DeliveryMode implements OptionSourceInterface
{
    /**
     * Return the list of available delivery modes.
     *
     * @return array<int, array{value: string, label: \Magento\Framework\Phrase}>
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => Config::DELIVERY_MODE_SYNC,  'label' => __('Synchronous (direct HTTP POST)')],
            ['value' => Config::DELIVERY_MODE_ASYNC, 'label' => __('Asynchronous (via Message Queue with retry)')],
        ];
    }
}
