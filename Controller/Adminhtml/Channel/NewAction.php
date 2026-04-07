<?php

declare(strict_types=1);

namespace Muon\TeamsNotifierCore\Controller\Adminhtml\Channel;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Forward;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Framework\App\Action\HttpGetActionInterface;

/**
 * Forwards to the Edit action with no channel_id, rendering a blank create form.
 */
class NewAction extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Muon_TeamsNotifierCore::channel';

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Backend\Model\View\Result\ForwardFactory $resultForwardFactory
     */
    public function __construct(
        Context $context,
        private readonly ForwardFactory $resultForwardFactory
    ) {
        parent::__construct($context);
    }

    /**
     * Forward to the edit action to render an empty form.
     *
     * @return \Magento\Backend\Model\View\Result\Forward
     */
    public function execute(): Forward
    {
        return $this->resultForwardFactory->create()->forward('edit');
    }
}
