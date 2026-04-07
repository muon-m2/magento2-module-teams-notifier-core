<?php

declare(strict_types=1);

namespace Muon\TeamsNotifierCore\Controller\Adminhtml\Channel;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

/**
 * Renders the Teams channel create/edit form.
 */
class Edit extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Muon_TeamsNotifierCore::channel';

    /**
     * @param \Magento\Backend\App\Action\Context        $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        Context                        $context,
        private readonly PageFactory   $resultPageFactory
    ) {
        parent::__construct($context);
    }

    /**
     * Render the channel form page.
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute(): Page
    {
        $channelId  = (int) $this->getRequest()->getParam('channel_id');
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Muon_TeamsNotifierCore::channel');

        $title = $channelId
            ? __('Edit Channel')
            : __('New Channel');

        $resultPage->getConfig()->getTitle()->prepend(__('Teams Notifier — Channels'));
        $resultPage->getConfig()->getTitle()->prepend($title);

        return $resultPage;
    }
}
