<?php

declare(strict_types=1);

namespace Muon\TeamsNotifierCore\Controller\Adminhtml\Template;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

/**
 * Adaptive Card template listing page.
 */
class Index extends Action
{
    public const ADMIN_RESOURCE = 'Muon_TeamsNotifierCore::template';

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
    }

    /**
     * Render the template listing page.
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute(): Page
    {
        /** @var Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Muon_TeamsNotifierCore::template_menu');
        $resultPage->getConfig()->getTitle()->prepend(__('Adaptive Card Templates'));

        return $resultPage;
    }
}
