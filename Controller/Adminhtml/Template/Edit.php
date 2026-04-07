<?php

declare(strict_types=1);

namespace Muon\TeamsNotifierCore\Controller\Adminhtml\Template;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\PageFactory;
use Muon\TeamsNotifierCore\Api\Data\TemplateInterface;
use Muon\TeamsNotifierCore\Api\TemplateRepositoryInterface;

/**
 * Adaptive Card template create / edit form page.
 */
class Edit extends Action
{
    public const ADMIN_RESOURCE = 'Muon_TeamsNotifierCore::template';

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Muon\TeamsNotifierCore\Api\TemplateRepositoryInterface $templateRepository
     */
    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory,
        private readonly TemplateRepositoryInterface $templateRepository
    ) {
        parent::__construct($context);
    }

    /**
     * Render the template create/edit form.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute(): ResultInterface
    {
        $templateId = (int) $this->getRequest()->getParam(TemplateInterface::TEMPLATE_ID);
        $title      = __('New Adaptive Card Template');

        if ($templateId) {
            try {
                $template = $this->templateRepository->getById($templateId);
                $title    = __('Edit Template: %1', $template->getLabel());
            } catch (NoSuchEntityException $e) {
                $this->messageManager->addErrorMessage(__('This template no longer exists.'));
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            }
        }

        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Muon_TeamsNotifierCore::template_menu');
        $resultPage->getConfig()->getTitle()->prepend($title);

        return $resultPage;
    }
}
