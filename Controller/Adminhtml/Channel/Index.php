<?php

declare(strict_types=1);

namespace Muon\TeamsNotifierCore\Controller\Adminhtml\Channel;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

/**
 * Displays the Teams notification channels grid.
 */
class Index extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Muon_TeamsNotifierCore::channel';

    /**
     * @param \Magento\Backend\App\Action\Context          $context
     * @param \Magento\Framework\View\Result\PageFactory   $resultPageFactory
     */
    public function __construct(
        Context                        $context,
        private readonly PageFactory   $resultPageFactory
    ) {
        parent::__construct($context);
    }

    /**
     * Render the channels grid page.
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute(): Page
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Muon_TeamsNotifierCore::channel');
        $resultPage->getConfig()->getTitle()->prepend(__('Teams Notifier — Channels'));

        return $resultPage;
    }
}
