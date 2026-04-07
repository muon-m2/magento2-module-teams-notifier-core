<?php

declare(strict_types=1);

namespace Muon\TeamsNotifierCore\Controller\Adminhtml\Template;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\LocalizedException;
use Muon\TeamsNotifierCore\Api\Data\TemplateInterface;
use Muon\TeamsNotifierCore\Api\TemplateRepositoryInterface;
use Muon\TeamsNotifierCore\Model\TemplateFactory;

/**
 * Persists an Adaptive Card template (create or update).
 */
class Save extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Muon_TeamsNotifierCore::template';

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Muon\TeamsNotifierCore\Api\TemplateRepositoryInterface $templateRepository
     * @param \Muon\TeamsNotifierCore\Model\TemplateFactory $templateFactory
     * @param \Magento\Framework\App\Request\DataPersistorInterface $dataPersistor
     */
    public function __construct(
        Context $context,
        private readonly TemplateRepositoryInterface $templateRepository,
        private readonly TemplateFactory $templateFactory,
        private readonly DataPersistorInterface $dataPersistor
    ) {
        parent::__construct($context);
    }

    /**
     * Save the template and redirect.
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute(): Redirect
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $postData       = $this->getRequest()->getPostValue();

        if (empty($postData)) {
            return $resultRedirect->setPath('*/*/');
        }

        $templateId = isset($postData[TemplateInterface::TEMPLATE_ID])
            ? (int) $postData[TemplateInterface::TEMPLATE_ID]
            : null;

        try {
            $template = $this->templateFactory->create();
            if ($templateId) {
                $template = $this->templateRepository->getById($templateId);
            }

            $template->setName((string) ($postData[TemplateInterface::NAME] ?? ''));
            $template->setLabel((string) ($postData[TemplateInterface::LABEL] ?? ''));
            $template->setTemplateJson((string) ($postData[TemplateInterface::TEMPLATE_JSON] ?? ''));

            $this->templateRepository->save($template);
            $this->messageManager->addSuccessMessage(__('The template has been saved.'));
            $this->dataPersistor->clear('muon_teamsnotifiercore_template');

            if ($this->getRequest()->getParam('back')) {
                return $resultRedirect->setPath('*/*/edit', [
                    TemplateInterface::TEMPLATE_ID => $template->getTemplateId(),
                ]);
            }

            return $resultRedirect->setPath('*/*/');
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Throwable $e) {
            $this->messageManager->addErrorMessage(
                __('Something went wrong while saving the template.')
            );
        }

        $this->dataPersistor->set('muon_teamsnotifiercore_template', $postData);

        return $templateId
            ? $resultRedirect->setPath('*/*/edit', [TemplateInterface::TEMPLATE_ID => $templateId])
            : $resultRedirect->setPath('*/*/new');
    }
}
