<?php

declare(strict_types=1);

namespace Muon\TeamsNotifierCore\Controller\Adminhtml\Template;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Muon\TeamsNotifierCore\Api\Data\TemplateInterface;
use Muon\TeamsNotifierCore\Api\TemplateRepositoryInterface;

/**
 * Deletes an Adaptive Card template.
 */
class Delete extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Muon_TeamsNotifierCore::template';

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Muon\TeamsNotifierCore\Api\TemplateRepositoryInterface $templateRepository
     */
    public function __construct(
        Context $context,
        private readonly TemplateRepositoryInterface $templateRepository
    ) {
        parent::__construct($context);
    }

    /**
     * Delete the template and redirect to the listing.
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute(): Redirect
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $templateId     = (int) $this->getRequest()->getParam(TemplateInterface::TEMPLATE_ID);

        if (!$templateId) {
            $this->messageManager->addErrorMessage(__('We cannot find a template to delete.'));
            return $resultRedirect->setPath('*/*/');
        }

        try {
            $this->templateRepository->deleteById($templateId);
            $this->messageManager->addSuccessMessage(__('The template has been deleted.'));
        } catch (\Throwable $e) {
            $this->messageManager->addErrorMessage(
                __('Something went wrong while deleting the template.')
            );
        }

        return $resultRedirect->setPath('*/*/');
    }
}
