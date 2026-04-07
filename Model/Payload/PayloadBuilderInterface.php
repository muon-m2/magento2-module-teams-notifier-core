<?php

declare(strict_types=1);

namespace Muon\TeamsNotifierCore\Model\Payload;

use Muon\TeamsNotifierCore\Api\Data\MessageInterface;

/**
 * Internal strategy interface: converts a MessageInterface into a JSON string
 * ready to POST to a Teams Incoming Webhook.
 */
interface PayloadBuilderInterface
{
    /**
     * Build the JSON request body for the given message.
     *
     * @param \Muon\TeamsNotifierCore\Api\Data\MessageInterface $message
     * @return string JSON string.
     * @throws \InvalidArgumentException If $message is not the expected sub-type.
     */
    public function build(MessageInterface $message): string;
}
