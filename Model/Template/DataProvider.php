<?php

declare(strict_types=1);

namespace Muon\TeamsNotifierCore\Model\Template;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Muon\TeamsNotifierCore\Model\ResourceModel\Template\CollectionFactory;

/**
 * UI form data provider for the Adaptive Card template edit form.
 */
class DataProvider extends AbstractDataProvider
{
    /** @var array */
    private array $loadedData = [];

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param \Muon\TeamsNotifierCore\Model\ResourceModel\Template\CollectionFactory $collectionFactory
     * @param \Magento\Framework\App\Request\DataPersistorInterface $dataPersistor
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        CollectionFactory $collectionFactory,
        private readonly DataPersistorInterface $dataPersistor,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collectionFactory->create();
    }

    /**
     * Get data for the form.
     *
     * @return array
     */
    public function getData(): array
    {
        if (!empty($this->loadedData)) {
            return $this->loadedData;
        }

        foreach ($this->collection->getItems() as $template) {
            $this->loadedData[$template->getId()] = $template->getData();
        }

        $persistedData = $this->dataPersistor->get('muon_teamsnotifiercore_template');
        if (!empty($persistedData)) {
            $template = $this->collection->getNewEmptyItem();
            $template->setData($persistedData);
            $this->loadedData[$template->getId()] = $persistedData;
            $this->dataPersistor->clear('muon_teamsnotifiercore_template');
        }

        return $this->loadedData;
    }
}
