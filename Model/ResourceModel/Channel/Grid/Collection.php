<?php

declare(strict_types=1);

namespace Muon\TeamsNotifierCore\Model\ResourceModel\Channel\Grid;

use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;

/**
 * Grid collection for the Teams notification channel listing UI component.
 *
 * Extends SearchResult (which implements SearchResultInterface) so the
 * UiComponent DataProvider can call searchResultToOutput() without a type error.
 * The mainTable and resourceModel are injected via di.xml.
 */
class Collection extends SearchResult
{
}
