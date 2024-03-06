<?php


use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

ExtensionUtility::configurePlugin(
    'flipbook',
    'Pi1',
    [
        'Flipbook' => 'show',
    ],
    []
);


ExtensionManagementUtility::addPageTSConfig(trim(
    '
		mod.wizards.newContentElement.wizardItems {
			plugins {
				elements {
					flipbook_pi1 {
						title = Flipbook
						description =  Show flipbook from PDF
						iconIdentifier = tx-flipbook-plugin-pi1
						tt_content_defValues {
							CType = list
							list_type = flipbook_pi1
						}
					}
				}
			}
		}

	'));


/*
if ((float)TYPO3_version >= 7.0) { // bugfix for inline fal images in flexforms (only after record with flexform has been saved)
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['inlineParentRecord'][\TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseEditRow::class] = array();
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['inlineParentRecord'][\TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca::class] = array(
        'depends' => array(
            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseEditRow::class,
        )
    );
}
*/
