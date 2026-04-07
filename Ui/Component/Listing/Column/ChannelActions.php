<?php

declare(strict_types=1);

namespace Muon\TeamsNotifierCore\Ui\Component\Listing\Column;

use Magento\Framework\Escaper;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Renders Edit and Delete action links in the channels grid.
 */
class ChannelActions extends Column
{
    private const URL_PATH_EDIT   = 'muon_teamsnotifiercore/channel/edit';
    private const URL_PATH_DELETE = 'muon_teamsnotifiercore/channel/delete';

    /**
     * @param \Magento\Framework\View\Element\UiComponent\ContextInterface $context
     * @param \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Magento\Framework\Escaper $escaper
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        private readonly UrlInterface $urlBuilder,
        private readonly Escaper $escaper,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Inject Edit and Delete links into each grid row.
     *
     * @param array $dataSource
     * @return array
     * @SuppressWarnings(PHPMD.MissingImport)
     * #[\Override] is a built-in PHP 8.3 attribute — PHPMD false-positive, no import needed.
     */
    #[\Override]
    public function prepareDataSource(array $dataSource): array
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        foreach ($dataSource['data']['items'] as &$item) {
            if (!isset($item['channel_id'])) {
                continue;
            }

            $columnName = $this->getData('name');
            $label      = $this->escaper->escapeHtml((string) ($item['label'] ?? ''));

            $item[$columnName]['edit'] = [
                'href'  => $this->urlBuilder->getUrl(
                    self::URL_PATH_EDIT,
                    ['channel_id' => $item['channel_id']]
                ),
                'label' => __('Edit'),
            ];

            $item[$columnName]['delete'] = [
                'href'    => $this->urlBuilder->getUrl(
                    self::URL_PATH_DELETE,
                    ['channel_id' => $item['channel_id']]
                ),
                'label'   => __('Delete'),
                'confirm' => [
                    'title'   => __('Delete "%1"', $label),
                    'message' => __('Are you sure you want to delete the "%1" channel?', $label),
                ],
                'post'    => true,
            ];
        }

        return $dataSource;
    }
}
