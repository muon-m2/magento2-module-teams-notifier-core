<?php

declare(strict_types=1);

namespace Muon\TeamsNotifierCore\Controller\Adminhtml\Channel;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Exception\LocalizedException;
use Muon\TeamsNotifierCore\Api\ChannelRepositoryInterface;

/**
 * Deletes a Teams notification channel.
 */
class Delete extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Muon_TeamsNotifierCore::channel';

    /**
     * @param \Magento\Backend\App\Action\Context                    $context
     * @param \Muon\TeamsNotifierCore\Api\ChannelRepositoryInterface $channelRepository
     */
    public function __construct(
        Context                                  $context,
        private readonly ChannelRepositoryInterface $channelRepository
    ) {
        parent::__construct($context);
    }

    /**
     * Delete the channel and redirect back to the grid.
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute(): Redirect
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $channelId      = (int) $this->getRequest()->getParam('channel_id');

        if (!$channelId) {
            $this->messageManager->addErrorMessage(__('Invalid channel ID.'));
            return $resultRedirect->setPath('*/*/');
        }

        try {
            $this->channelRepository->deleteById($channelId);
            $this->messageManager->addSuccessMessage(__('The channel has been deleted.'));
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Throwable $e) {
            $this->messageManager->addErrorMessage(__('Something went wrong while deleting the channel.'));
        }

        return $resultRedirect->setPath('*/*/');
    }
}
