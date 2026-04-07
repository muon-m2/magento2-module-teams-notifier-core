<?php

declare(strict_types=1);

namespace Muon\TeamsNotifierCore\Controller\Adminhtml\Template;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Forward;
use Magento\Framework\Controller\Result\ForwardFactory;

/**
 * Forwards to the edit action to render the new-template form.
 */
class NewAction extends Action
{
    public const ADMIN_RESOURCE = 'Muon_TeamsNotifierCore::template';

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\ForwardFactory $forwardFactory
     */
    public function __construct(
        Context $context,
        private readonly ForwardFactory $forwardFactory
    ) {
        parent::__construct($context);
    }

    /**
     * Forward to the edit action.
     *
     * @return \Magento\Framework\Controller\Result\Forward
     */
    public function execute(): Forward
    {
        /** @var Forward $forward */
        $forward = $this->forwardFactory->create();

        return $forward->forward('edit');
    }
}
