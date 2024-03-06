<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

ExtensionUtility::registerPlugin(
    'Flipbook',
    'Show',
    'LLL:EXT:flipbook/Resources/Private/Language/locallang_db.xlf:plugin.flipbook.title',
    null,
    'flipbook'
);


ExtensionManagementUtility::addPiFlexFormValue('*', 'FILE:EXT:flipbook/Configuration/FlexForms/tx_flipbook.xml', 'flipbook_show');


ExtensionManagementUtility::addToAllTCAtypes('tt_content', 'pi_flexform', 'flipbook_show', 'after:header');
