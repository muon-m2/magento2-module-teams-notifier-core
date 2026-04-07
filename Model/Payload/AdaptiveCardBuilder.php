<?php

declare(strict_types=1);

namespace Muon\TeamsNotifierCore\Model\Payload;

use Magento\Framework\Serialize\Serializer\Json;
use Muon\TeamsNotifierCore\Api\Data\AdaptiveCardMessageInterface;
use Muon\TeamsNotifierCore\Api\Data\MessageInterface;
use InvalidArgumentException;

/**
 * Builds the Adaptive Card webhook envelope JSON for Teams Incoming Webhooks.
 *
 * Teams requires the Adaptive Card to be wrapped in a message/attachment envelope:
 * https://learn.microsoft.com/en-us/microsoftteams/platform/webhooks-and-connectors/how-to/connectors-using
 */
class AdaptiveCardBuilder implements PayloadBuilderInterface
{
    private const CONTENT_TYPE = 'application/vnd.microsoft.card.adaptive';
    private const CARD_SCHEMA  = 'http://adaptivecards.io/schemas/adaptive-card.json';

    /**
     * @param \Magento\Framework\Serialize\Serializer\Json $json
     */
    public function __construct(
        private readonly Json $json
    ) {
    }

    /**
     * Build the Adaptive Card envelope JSON payload.
     *
     * @param \Muon\TeamsNotifierCore\Api\Data\MessageInterface $message
     * @return string
     * @throws \InvalidArgumentException If $message is not an AdaptiveCardMessageInterface.
     */
    public function build(MessageInterface $message): string
    {
        if (!$message instanceof AdaptiveCardMessageInterface) {
            throw new InvalidArgumentException(
                sprintf('Expected %s, got %s.', AdaptiveCardMessageInterface::class, $message::class)
            );
        }

        $body = $message->getCardBody();

        // Prepend a title TextBlock when a title is set and the body does not start with one already.
        if ($message->getTitle() !== '' && (empty($body) || ($body[0]['type'] ?? '') !== 'TextBlock')) {
            array_unshift($body, [
                'type'   => 'TextBlock',
                'text'   => $message->getTitle(),
                'weight' => 'bolder',
                'size'   => 'medium',
                'wrap'   => true,
            ]);
        }

        $card = [
            '$schema' => self::CARD_SCHEMA,
            'type'    => 'AdaptiveCard',
            'version' => $message->getCardVersion(),
            'body'    => $body,
        ];

        $actions = $message->getCardActions();
        if (!empty($actions)) {
            $card['actions'] = $actions;
        }

        $payload = [
            'type'        => 'message',
            'attachments' => [
                [
                    'contentType' => self::CONTENT_TYPE,
                    'contentUrl'  => null,
                    'content'     => $card,
                ],
            ],
        ];

        return $this->json->serialize($payload);
    }
}
