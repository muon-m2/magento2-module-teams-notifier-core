<?php

declare(strict_types=1);

namespace Muon\TeamsNotifierCore\Ui\Component\Listing\Column;

use Magento\Framework\Escaper;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Renders Edit and Delete action links in the Adaptive Card template listing grid.
 */
class TemplateActions extends Column
{
    private const EDIT_URL   = 'muon_teamsnotifiercore/template/edit';
    private const DELETE_URL = 'muon_teamsnotifiercore/template/delete';

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
     * Add Edit and Delete links to each row in the grid.
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        foreach ($dataSource['data']['items'] as &$item) {
            $templateId = (int) $item['template_id'];
            $label      = $this->escaper->escapeHtml($item['label'] ?? '');

            $item[$this->getData('name')] = [
                'edit' => [
                    'href'  => $this->urlBuilder->getUrl(self::EDIT_URL, ['template_id' => $templateId]),
                    'label' => __('Edit'),
                ],
                'delete' => [
                    'href'    => $this->urlBuilder->getUrl(self::DELETE_URL, ['template_id' => $templateId]),
                    'label'   => __('Delete'),
                    'confirm' => [
                        'title'   => __('Delete "%1"', $label),
                        'message' => __(
                            'Are you sure you want to delete the "%1" template?',
                            $label
                        ),
                    ],
                    'post' => true,
                ],
            ];
        }

        return $dataSource;
    }
}
