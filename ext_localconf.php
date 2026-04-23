<?php


use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use WapplerSystems\Flipbook\Controller\FlipbookController;

ExtensionUtility::configurePlugin(
    'Flipbook',
    'Show',
    [
        FlipbookController::class => 'show',
    ],
    [],
    ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
);
