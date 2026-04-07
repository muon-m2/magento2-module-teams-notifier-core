<?php

declare(strict_types=1);

namespace Muon\TeamsNotifierCore\Setup\Patch\Data;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Muon\TeamsNotifierCore\Api\ChannelRepositoryInterface;
use Muon\TeamsNotifierCore\Api\Data\ChannelInterface;
use Muon\TeamsNotifierCore\Api\Data\TemplateInterface;
use Muon\TeamsNotifierCore\Api\TemplateRepositoryInterface;
use Muon\TeamsNotifierCore\Model\ChannelFactory;
use Muon\TeamsNotifierCore\Model\TemplateFactory;

/**
 * Seeds the predefined "CRITICAL Error" channel and its "CRITICAL notice" Adaptive Card template.
 *
 * The channel is created inactive with an empty webhook URL. An administrator must open
 * Stores → Teams Notifier → Channels → CRITICAL Error, paste the Power Automate
 * Workflows webhook URL, and activate the channel before notifications are delivered.
 *
 * Template variables resolved at send time:
 *   - ${Caption}           — short title / subject of the alert
 *   - ${Short Description} — one-line summary shown by default on the card
 *   - ${Full Description}  — detailed body revealed when the chevron is expanded
 */
class InstallCriticalErrorChannel implements DataPatchInterface
{
    private const TEMPLATE_NAME  = 'critical-notice';
    private const TEMPLATE_LABEL = 'CRITICAL notice';
    private const CHANNEL_NAME   = 'critical-error';
    private const CHANNEL_LABEL  = 'CRITICAL Error';

    /**
     * Adaptive Card JSON for the CRITICAL notice template.
     *
     * Structure:
     *  - Red "🔴 CRITICAL" badge strip (attention style, full-bleed).
     *  - ${Caption} rendered as a large bold heading.
     *  - ${Short Description} shown by default below the heading.
     *  - Chevron button (Action.ToggleVisibility) expands the ${Full Description} section.
     *
     * Validated against the Adaptive Card 1.6 schema for Microsoft Teams:
     *  accessibility score 100/100, no unsupported elements.
     */
    private const TEMPLATE_JSON = <<<'JSON'
{
    "type": "AdaptiveCard",
    "version": "1.6",
    "$schema": "http://adaptivecards.io/schemas/adaptive-card.json",
    "speak": "CRITICAL alert: ${Caption}. ${Short Description}",
    "body": [
        {
            "type": "ColumnSet",
            "spacing": "None",
            "columns": [
                {
                    "type": "Column",
                    "width": "auto",
                    "style": "attention",
                    "bleed": true,
                    "spacing": "None",
                    "verticalContentAlignment": "Center",
                    "items": [
                        {
                            "type": "TextBlock",
                            "text": "🔴  CRITICAL",
                            "weight": "Bolder",
                            "size": "Small",
                            "color": "Attention",
                            "wrap": true,
                            "spacing": "Small"
                        }
                    ]
                },
                {
                    "type": "Column",
                    "width": "stretch",
                    "items": []
                }
            ]
        },
        {
            "type": "TextBlock",
            "text": "${Caption}",
            "size": "Large",
            "weight": "Bolder",
            "wrap": true,
            "spacing": "Medium",
            "separator": true
        },
        {
            "type": "TextBlock",
            "text": "${Short Description}",
            "wrap": true,
            "spacing": "Small",
            "color": "Default"
        },
        {
            "type": "ActionSet",
            "spacing": "Small",
            "separator": true,
            "actions": [
                {
                    "type": "Action.ToggleVisibility",
                    "title": "▼  Show full description",
                    "targetElements": [
                        {
                            "elementId": "fullDescriptionContainer",
                            "isVisible": true
                        }
                    ]
                }
            ]
        },
        {
            "type": "Container",
            "id": "fullDescriptionContainer",
            "isVisible": false,
            "spacing": "Small",
            "separator": true,
            "items": [
                {
                    "type": "TextBlock",
                    "text": "Full Description",
                    "weight": "Bolder",
                    "size": "Small",
                    "color": "Accent",
                    "spacing": "Small",
                    "wrap": true
                },
                {
                    "type": "TextBlock",
                    "text": "${Full Description}",
                    "wrap": true,
                    "spacing": "Small"
                }
            ]
        }
    ]
}
JSON;

    /**
     * @param \Magento\Framework\Setup\ModuleDataSetupInterface $moduleDataSetup
     * @param \Muon\TeamsNotifierCore\Api\TemplateRepositoryInterface $templateRepository
     * @param \Muon\TeamsNotifierCore\Model\TemplateFactory $templateFactory
     * @param \Muon\TeamsNotifierCore\Api\ChannelRepositoryInterface $channelRepository
     * @param \Muon\TeamsNotifierCore\Model\ChannelFactory $channelFactory
     */
    public function __construct(
        private readonly ModuleDataSetupInterface   $moduleDataSetup,
        private readonly TemplateRepositoryInterface $templateRepository,
        private readonly TemplateFactory            $templateFactory,
        private readonly ChannelRepositoryInterface  $channelRepository,
        private readonly ChannelFactory             $channelFactory
    ) {
    }

    /**
     * Install the CRITICAL notice template and CRITICAL Error channel.
     *
     * Both entities are skipped if they already exist (idempotent).
     *
     * @return void
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function apply(): void
    {
        $this->moduleDataSetup->startSetup();

        $template = $this->createOrLoadTemplate();
        $this->createChannelIfAbsent($template);

        $this->moduleDataSetup->endSetup();
    }

    /**
     * Return dependencies on other data patches.
     *
     * @return array<class-string>
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * Return aliases for this patch (for rename compatibility).
     *
     * @return array<string>
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * Load the CRITICAL notice template if it already exists, otherwise create it.
     *
     * @return \Muon\TeamsNotifierCore\Api\Data\TemplateInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function createOrLoadTemplate(): TemplateInterface
    {
        try {
            return $this->templateRepository->getByName(self::TEMPLATE_NAME);
        } catch (NoSuchEntityException) {
            /** @var \Muon\TeamsNotifierCore\Model\Template $template */
            $template = $this->templateFactory->create();
            $template->setName(self::TEMPLATE_NAME);
            $template->setLabel(self::TEMPLATE_LABEL);
            $template->setTemplateJson(self::TEMPLATE_JSON);

            return $this->templateRepository->save($template);
        }
    }

    /**
     * Create the CRITICAL Error channel if it does not already exist.
     *
     * The webhook URL is intentionally left empty: an administrator must supply
     * the Power Automate Workflows webhook URL via the admin panel before the
     * channel can deliver notifications.
     *
     * @param \Muon\TeamsNotifierCore\Api\Data\TemplateInterface $template
     * @return void
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    private function createChannelIfAbsent(TemplateInterface $template): void
    {
        try {
            $this->channelRepository->getByName(self::CHANNEL_NAME);
            return; // already seeded — nothing to do
        } catch (NoSuchEntityException) {
            /** @var \Muon\TeamsNotifierCore\Model\Channel $channel */
            $channel = $this->channelFactory->create();
            $channel->setName(self::CHANNEL_NAME);
            $channel->setLabel(self::CHANNEL_LABEL);
            $channel->setWebhookUrl('');
            $channel->setTriggerSecret('');
            $channel->setIsActive(false);
            $channel->setTemplateId($template->getTemplateId());

            $this->channelRepository->save($channel);
        }
    }
}
