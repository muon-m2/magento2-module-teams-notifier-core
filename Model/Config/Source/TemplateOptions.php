<?php

declare(strict_types=1);

namespace Muon\TeamsNotifierCore\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Muon\TeamsNotifierCore\Model\ResourceModel\Template\CollectionFactory;

/**
 * Option source for the template select field on the channel form.
 *
 * Returns a list of all available Adaptive Card templates plus a "None" option.
 */
class TemplateOptions implements OptionSourceInterface
{
    /**
     * @param \Muon\TeamsNotifierCore\Model\ResourceModel\Template\CollectionFactory $collectionFactory
     */
    public function __construct(
        private readonly CollectionFactory $collectionFactory
    ) {
    }

    /**
     * Return array of options for the template select element.
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        $options = [
            ['value' => '', 'label' => __('— None (use caller-supplied card body) —')],
        ];

        $collection = $this->collectionFactory->create();
        $collection->addOrder('label', 'ASC');

        foreach ($collection as $template) {
            $options[] = [
                'value' => $template->getId(),
                'label' => $template->getLabel() . ' (' . $template->getName() . ')',
            ];
        }

        return $options;
    }
}
