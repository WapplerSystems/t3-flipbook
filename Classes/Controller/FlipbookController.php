<?php

namespace WapplerSystems\Flipbook\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Domain\Model\Folder;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;


/**
 *
 */
class FlipbookController extends ActionController
{

    /**
     * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
     */
    protected $configurationManager;

    /**
     * Injects the Configuration Manager and is initializing the framework settings
     *
     * @param \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager Instance of the Configuration Manager
     * @return void
     */
    public function injectConfigurationManager(ConfigurationManagerInterface $configurationManager)
    {
        parent::injectConfigurationManager($configurationManager);

        $tsSettings = $this->settings['plugin.']['flipbook.']['settings.'];
        $originalSettings = $this->configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS
        );
        if (isset($tsSettings['overrideFlexformSettingsIfEmpty']) && $tsSettings['overrideFlexformSettingsIfEmpty'] == 1) {
            // if flexform setting is empty and value is available in TS
            foreach ($tsSettings as $key => $value) {
                if ($key === 'img.') continue;
                if ($key === 'assets.') continue;
                if ($key === 'deepLinking.') continue;

                if (!$originalSettings[$key] && isset($value)) {
                    $originalSettings[$key] = $value;
                }
            }
            if (isset($tsSettings['img.'])) {
                foreach ($tsSettings['img.'] as $key => $value) {
                    if (!$originalSettings['img'][$key] && isset($value)) $originalSettings['img'][$key] = $value;
                }
            }
            if (isset($tsSettings['assets.'])) {
                foreach ($tsSettings['assets.'] as $key => $value) {
                    if (!$originalSettings['assets'][$key] && isset($value)) $originalSettings['assets'][$key] = $value;
                }
            }
            if (isset($tsSettings['deepLinking.'])) {
                foreach ($tsSettings['deepLinking.'] as $key => $value) {
                    if (!$originalSettings['deepLinking'][$key] && isset($value)) $originalSettings['deepLinking'][$key] = $value;
                }
            }
        }
        $this->settings = $originalSettings;
    }


    /**
     *
     */
    public function showAction() : ResponseInterface
    {

        /** @var string $bigImageFolder */
        $bigImageFolder = $this->settings['folder'];
        /** @var ResourceFactory $factory */
        $factory = GeneralUtility::makeInstance(ResourceFactory::class);
        /** @var Folder $folder */
        $folder = $factory->getFolderObjectFromCombinedIdentifier($bigImageFolder);

        if (!empty($this->settings['thumbs'])) {
            /** @var Folder $thumbFolder */
            $thumbFolder = $factory->getFolderObjectFromCombinedIdentifier($this->settings['thumbs']);
            $this->view->assign('thumbfolder', $thumbFolder);
        }
        /** preview image */
        if ($this->settings['preview']) {
            /** @var FileRepository $fileRepository */
            $fileRepository = GeneralUtility::makeInstance(FileRepository::class);
            /** @var FileReference $preview */
            $preview = $fileRepository->findByRelation('tt_content', 'settings.preview', $this->configurationManager->getContentObject()->data['uid']);
            $this->view->assign('preview', $preview[0]);
        }
        if (!is_array($this->settings['toc'])) {
            unset($this->settings['toc']);
        }
        $this->view
            ->assign('uid', uniqid())
            ->assign('files', $folder->getFiles())
            ->assign('settings', $this->settings);

        $code = $this->view->render();

        return $this->htmlResponse($this->sanitize_output($code));
    }


    /**
     * @param $buffer
     * @return mixed
     */
    protected function sanitize_output($buffer)
    {

        return $buffer;

        $search = array(
            '/\>[^\S ]+/s',  // strip whitespaces after tags, except space
            '/[^\S ]+\</s',  // strip whitespaces before tags, except space
            '/(\s)+/s'       // shorten multiple whitespace sequences
        );

        $replace = array(
            '>',
            '<',
            '\\1'
        );

        $buffer = preg_replace($search, $replace, $buffer);

        return $buffer;
    }

}
