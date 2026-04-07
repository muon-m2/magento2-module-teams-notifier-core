<?php

declare(strict_types=1);

namespace Muon\TeamsNotifierCore\Model\Channel;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Muon\TeamsNotifierCore\Model\ResourceModel\Channel\CollectionFactory;

/**
 * UI form data provider for the Teams channel edit form.
 */
class DataProvider extends AbstractDataProvider
{
    /** @var array<int|string, array<string, mixed>> */
    private array $loadedData = [];

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param \Muon\TeamsNotifierCore\Model\ResourceModel\Channel\CollectionFactory $collectionFactory
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
     * Sensitive fields (webhook_url, trigger_secret) are cleared before sending to the
     * browser to avoid exposing decrypted credentials; users must re-enter them to change.
     *
     * @return array<int|string, array<string, mixed>>
     */
    public function getData(): array
    {
        if (!empty($this->loadedData)) {
            return $this->loadedData;
        }

        foreach ($this->collection->getItems() as $channel) {
            $row = $channel->getData();
            // Do not expose encrypted sensitive fields stored in the collection row.
            $row['webhook_url']    = '';
            $row['trigger_secret'] = '';
            $this->loadedData[$channel->getId()] = $row;
        }

        $persistedData = $this->dataPersistor->get('muon_teamsnotifiercore_channel');
        if (!empty($persistedData)) {
            $channel = $this->collection->getNewEmptyItem();
            $channel->setData($persistedData);
            $persistedData['webhook_url']    = '';
            $persistedData['trigger_secret'] = '';
            $this->loadedData[$channel->getId()] = $persistedData;
            $this->dataPersistor->clear('muon_teamsnotifiercore_channel');
        }

        return $this->loadedData;
    }
}
