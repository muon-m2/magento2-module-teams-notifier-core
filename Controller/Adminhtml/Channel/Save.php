<?php

declare(strict_types=1);

namespace Muon\TeamsNotifierCore\Controller\Adminhtml\Channel;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\LocalizedException;
use Muon\TeamsNotifierCore\Api\ChannelRepositoryInterface;
use Muon\TeamsNotifierCore\Api\Data\ChannelInterface;
use Muon\TeamsNotifierCore\Model\ChannelFactory;

/**
 * Persists a Teams notification channel (create or update).
 */
class Save extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Muon_TeamsNotifierCore::channel';

    /**
     * @param \Magento\Backend\App\Action\Context                        $context
     * @param \Muon\TeamsNotifierCore\Api\ChannelRepositoryInterface     $channelRepository
     * @param \Muon\TeamsNotifierCore\Model\ChannelFactory               $channelFactory
     * @param \Magento\Framework\App\Request\DataPersistorInterface      $dataPersistor
     */
    public function __construct(
        Context                                  $context,
        private readonly ChannelRepositoryInterface $channelRepository,
        private readonly ChannelFactory            $channelFactory,
        private readonly DataPersistorInterface    $dataPersistor
    ) {
        parent::__construct($context);
    }

    /**
     * Save the channel and redirect.
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * Inherent to handling create-vs-update, two optional encrypted fields, two catch
     * branches, and the "back" redirect — all required by Magento admin controller conventions.
     */
    public function execute(): Redirect
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $postData       = $this->getRequest()->getPostValue();

        if (empty($postData)) {
            return $resultRedirect->setPath('*/*/');
        }

        $channelId = isset($postData[ChannelInterface::CHANNEL_ID])
            ? (int) $postData[ChannelInterface::CHANNEL_ID]
            : null;

        try {
            $channel = $this->channelFactory->create();
            if ($channelId) {
                $channel = $this->channelRepository->getById($channelId);
            }

            $channel->setName((string) ($postData[ChannelInterface::NAME] ?? ''));
            $channel->setLabel((string) ($postData[ChannelInterface::LABEL] ?? ''));
            $channel->setIsActive((bool) ($postData[ChannelInterface::IS_ACTIVE] ?? false));

            // Only update sensitive fields when the user explicitly provides a new value;
            // an empty submission means "keep the existing encrypted value".
            $newUrl = trim((string) ($postData[ChannelInterface::WEBHOOK_URL] ?? ''));
            if ($newUrl !== '') {
                $channel->setWebhookUrl($newUrl);
            } elseif (!$channelId) {
                throw new LocalizedException(__('Webhook URL is required when creating a channel.'));
            }

            $newSecret = trim((string) ($postData[ChannelInterface::TRIGGER_SECRET] ?? ''));
            if ($newSecret !== '') {
                $channel->setTriggerSecret($newSecret);
            }

            $this->channelRepository->save($channel);
            $this->messageManager->addSuccessMessage(__('The channel has been saved.'));
            $this->dataPersistor->clear('muon_teamsnotifiercore_channel');

            if ($this->getRequest()->getParam('back')) {
                return $resultRedirect->setPath('*/*/edit', [
                    ChannelInterface::CHANNEL_ID => $channel->getChannelId(),
                ]);
            }

            return $resultRedirect->setPath('*/*/');
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Throwable $e) {
            $this->messageManager->addErrorMessage(__('Something went wrong while saving the channel.'));
        }

        $this->dataPersistor->set('muon_teamsnotifiercore_channel', $postData);

        return $channelId
            ? $resultRedirect->setPath('*/*/edit', [ChannelInterface::CHANNEL_ID => $channelId])
            : $resultRedirect->setPath('*/*/new');
    }
}
