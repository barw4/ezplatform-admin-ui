<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\EzPlatformAdminUi\Behat\PageElement;

use Behat\Mink\Exception\ElementNotFoundException;
use EzSystems\Behat\Browser\Context\BrowserContext;
use EzSystems\Behat\Browser\Element\Element;
use PHPUnit\Framework\Assert;

class UniversalDiscoveryWidget extends Element
{
    public const ELEMENT_NAME = 'UDW';
    private const LONG_TIMEOUT = 20;
    private const SHORT_TIMEOUT = 2;

    public function __construct(BrowserContext $context)
    {
        parent::__construct($context);
        $this->fields = [
            // general selectors
            'confirmButton' => '.c-selected-locations__confirm-button',
            'categoryTabSelector' => '.c-tab-selector__item',
            'cancelButton' => '.c-top-menu__cancel-btn',
            'mainWindow' => '.m-ud',
            'selectedLocationsTab' => '.c-selected-locations',
            // selectors for path traversal
            'treeLevelFormat' => '.c-finder-branch:nth-child(%d)',
            'treeLevelElementsFormat' => '.c-finder-branch:nth-of-type(%d) .c-finder-leaf',
            'treeLevelSelectedFormat' => '.c-finder-branch:nth-of-type(%d) .c-finder-leaf--marked',
            // selectors for multiitem selection
            'multiSelectAddButon' => '.c-toggle-selection-button',
        ];
        // selectors for multiitem selection
        $this->fields['currentlySelectedItemAddedFormat'] = sprintf('%s %s.c-toggle-selection-button--selected', $this->fields['treeLevelSelectedFormat'], $this->fields['multiSelectAddButon']);
        $this->fields['currentlySelectedAddItemButtonFormat'] = sprintf('%s %s', $this->fields['treeLevelSelectedFormat'], $this->fields['multiSelectAddButon']);
    }

    /**
     * @param string $itemPath
     */
    public function selectContent(string $itemPath): void
    {
        $pathParts = explode('/', $itemPath);
        $level = 1;

        foreach ($pathParts as $itemName) {
            $this->selectTreeBranch($itemName, $level);
            ++$level;
        }

        $itemName = $pathParts[count($pathParts) - 1];

        if ($this->isMultiSelect()) {
            $this->addItemToMultiselection($itemName, count($pathParts));
        }
    }

    public function confirm(): void
    {
        $this->context->findElement($this->fields['confirmButton'])->click();
        $this->context->waitUntil(self::SHORT_TIMEOUT, function () {
            return !$this->isVisible();
        });
    }

    public function cancel(): void
    {
        $this->context->findElement($this->fields['cancelButton'])->click();
        $this->context->waitUntil(self::SHORT_TIMEOUT, function () {
            return !$this->isVisible();
        });
    }

    public function verifyVisibility(): void
    {
        $this->assertExpectedTabsExist();
    }

    protected function assertExpectedTabsExist(): void
    {
        $expectedTabTitles = ['Browse', 'Bookmarks', 'Search'];

        $actualTabTitles = [];
        $tabs = $this->context->findAllElements($this->fields['categoryTabSelector']);
        foreach ($tabs as $tab) {
            $actualTabTitles[] = $tab->getText();
        }

        Assert::assertArraySubset($expectedTabTitles, $actualTabTitles);
    }

    protected function isVisible(): bool
    {
        return $this->context->isElementVisible($this->fields['mainWindow'], self::SHORT_TIMEOUT);
    }

    protected function isMultiSelect(): bool
    {
        try {
            $this->context->findElement($this->fields['multiSelectAddButon'], self::SHORT_TIMEOUT);

            return true;
        } catch (ElementNotFoundException $e) {
            return false;
        }
    }

    protected function addItemToMultiSelection(string $itemName, int $level)
    {
        $currentSelectedItemSelector = sprintf($this->fields['treeLevelSelectedFormat'], $level);
        $currentSelectedItem = $this->context->getElementByText($itemName, $currentSelectedItemSelector);
        Assert::assertEquals($itemName, $currentSelectedItem->getText());

        $currentSelectedItem->mouseOver();
        $this->context->waitUntilElementIsVisible(sprintf($this->fields['currentlySelectedAddItemButtonFormat'], $level));
        $this->context->findElement(sprintf($this->fields['currentlySelectedAddItemButtonFormat'], $level))->click();
        Assert::assertTrue($this->context->findElement(sprintf($this->fields['currentlySelectedItemAddedFormat'], $level))->isVisible());
    }

    protected function selectTreeBranch(string $itemName, int $level)
    {
        $this->context->waitUntilElementIsVisible(sprintf($this->fields['treeLevelFormat'], $level), self::LONG_TIMEOUT);
        $this->context->getElementByText($itemName, sprintf($this->fields['treeLevelElementsFormat'], $level))->click();
        Assert::assertTrue($this->context->getElementByText($itemName, sprintf($this->fields['treeLevelSelectedFormat'], $level))->isVisible());
    }
}
