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


ExtensionManagementUtility::addPageTSConfig(trim(
    '
		mod.wizards.newContentElement.wizardItems {
			plugins {
				elements {
					flipbook {
						title = Flipbook
						description =  Show flipbook from PDF
						iconIdentifier = tx-flipbook
						tt_content_defValues {
							CType = flipbook
						}
					}
				}
			}
		}

	'));
