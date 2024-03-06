<?php


use TYPO3\CMS\Core\Utility\VersionNumberUtility;

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'WapplerSystems.' . $_EXTKEY,
    'Pi1',
    [
        'Flipbook' => 'show',
    ],
    []
);



    /** @var \TYPO3\CMS\Core\Imaging\IconRegistry $iconRegistry */
    $iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
    $iconRegistry->registerIcon(
        'tx-rflipbook-plugin-pi1',
        \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
        ['source' => 'EXT:rflipbook/ext_icon-7.png']
    );


    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
        '
		mod.wizards.newContentElement.wizardItems {
			plugins {
				elements {
					rflipbook_pi1 {
						title = Flipbook
						description =  Show flipbook from PDF
						iconIdentifier = tx-rflipbook-plugin-pi1
						tt_content_defValues {
							CType = list
							list_type = rflipbook_pi1
						}
					}
				}
			}
		}

	');


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
