<?php

namespace WapplerSystems\Flipbook\ViewHelpers;

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

use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;


/**
 *
 *
 * @package products
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 *
 */
class InlinejsViewHelper extends AbstractTagBasedViewHelper
{

    /**
     * @var string
     */
    protected $tagName = 'inlinejs';
    /**
     * @var array $settings
     */
    protected $settings;

    public function initializeArguments()
    {
        $this->registerArgument('uid', 'int', 'Uid of the Content Element', true);
        $this->registerArgument('files', 'array', 'Files');
        $this->registerArgument('settings', 'array', 'Settings', true);
        $this->registerArgument('thumbfolder', '\TYPO3\CMS\Core\Resource\Folder', 'Thumbnails folder');

    }

    /**
     * @param int $uid
     * @param array $settings
     * @param mixed $files
     * @param \TYPO3\CMS\Core\Resource\Folder $thumbfolder
     * @return mixed
     */
    public function render()
    {
        $this->settings = $this->arguments['settings'];
        $settings = $this->settings;
        $uid = $this->arguments['uid'];
        $files = $this->arguments['files'];
        $thumbfolder = $this->arguments['thumbfolder'];
        $content = '';

        $content .= "
            if(typeof flipbookOptions == 'undefined'){ var flipbookOptions = new Object();}
            flipbookOptions['" . $uid . "'] = new Object();
            flipbookOptions['" . $uid . "'] = {
                " . $this->renderTrueFalseOptions() . "
            ";

        if ($this->settings['fullbook'] == 1) {
            $content .= 'lightBox: false,';
        } else {
            $content .= 'lightBox: true,';
        }

        $content .= $this->createAssets();

        switch ($this->settings['mode']) {
            case 'pdf':
                $content .= 'pdfUrl: "' . $this->uriPage($this->settings['pdfUrl']) . '",';
                if (!empty($this->settings['pdfPageScale']))
                    $content .= 'pdfPageScale: ' . (float)$this->settings['pdfPageScale'] . ',';

                break;

            case 'folders':

                $content .= 'pages: [';

                if (count($files) > 0) {
                    /** @var File $file */
                    foreach ($files as $file) {
                        $content .= '{src:"' . $file->getPublicUrl() . '",';
                        if ($thumbfolder !== NULL) {
                            $content .= 'thumb:"' . $thumbfolder->getPublicUrl() . $file->getName() . '",';
                            if ($file->getProperty('title')) {
                                $content .= 'title: "' . $file->getProperty('title') . '"';
                            }
                        } else {
                            $content .= '';
                        }
                        $content .= '},';
                    }
                    $content = rtrim($content, ',');
                }

                $content .= '],';

                break;
        }

        $content .= $this->renderNotEmptyOptions();

        $content .= $this->renderButtons();

        $content .= $this->renderToc();

        if ($this->settings['pageShadow'] == 'pageShadow') {
            $content .= 'pageShadow:true,';
        } else {
            $content .= 'pageShadow:false,';
        }

        if ($this->settings['pointLight'] == 'pointLight') {
            $content .= 'pointLight:true,';
        } else {
            $content .= 'pointLight:false,';
        }

        if ($this->settings['directionalLight'] == 'directionalLight') {
            $content .= 'directionalLight:true,';
        } else {
            $content .= 'directionalLight:false,';
        }

        if ($this->settings['ambientLight'] == 'ambientLight') {
            $content .= 'ambientLight:true,';
        } else {
            $content .= 'ambientLight:false,';
        }

        $content = rtrim($content, ',');
        $content .= "
            }
        ";

        return "<script>" . $content . "</script>";
    }

    /**
     * @param integer|NULL $pageUid target PID
     * @param array $additionalParams query parameters to be attached to the resulting URI
     * @param integer $pageType type of the target page. See typolink.parameter
     * @param boolean $noCache set this to disable caching for the target page. You should not need this.
     * @param boolean $noCacheHash set this to supress the cHash query parameter created by TypoLink. You should not need this.
     * @param string $section the anchor to be added to the URI
     * @param boolean $linkAccessRestrictedPages If set, links pointing to access restricted pages will still link to the page even though the page cannot be accessed.
     * @param boolean $absolute If set, the URI of the rendered link is absolute
     * @param boolean $addQueryString If set, the current query parameters will be kept in the URI
     * @param array $argumentsToBeExcludedFromQueryString arguments to be removed from the URI. Only active if $addQueryString = TRUE
     * @param string $addQueryStringMethod Set which parameters will be kept. Only active if $addQueryString = TRUE
     * @return string Rendered page URI
     */
    public function uriPage($pageUid = NULL, array $additionalParams = array(), $pageType = 0, $noCache = FALSE, $noCacheHash = FALSE, $section = '', $linkAccessRestrictedPages = FALSE, $absolute = FALSE, $addQueryString = FALSE, array $argumentsToBeExcludedFromQueryString = array(), $addQueryStringMethod = NULL)
    {

        /** @var ObjectManager $objectManager */
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        /** @var UriBuilder $uriBuilder */
        $uriBuilder = $objectManager->get(UriBuilder::class);
        $uri = $uriBuilder->setTargetPageUid($pageUid)->setTargetPageType($pageType)->setNoCache($noCache)->setUseCacheHash(!$noCacheHash)->setSection($section)->setLinkAccessRestrictedPages($linkAccessRestrictedPages)->setArguments($additionalParams)->setCreateAbsoluteUri($absolute)->setAddQueryString($addQueryString)->setArgumentsToBeExcludedFromQueryString($argumentsToBeExcludedFromQueryString)->setAddQueryStringMethod($addQueryStringMethod)->build();
        return $uri;
    }


    private function renderToc()
    {
        $content = '';

        if ($this->settings['toc'] && count($this->settings['toc']) > 0) {
            $content .= 'tableOfContent:[';

            foreach ($this->settings['toc'] as $toc) {
                $content .= '{title:"' . $toc['field_toc_entry']['field_toc_label'] . '", page:' . $toc['field_toc_entry']['field_toc_page'] . '},';
            }

            $content = rtrim($content, ',');
            $content .= '],';

        }

        return $content;
    }

    /**
     * @return string
     */
    private function renderButtons()
    {
        $content = '';

        $buttons = [
            'currentPage' => ['title' => LocalizationUtility::translate('currentPage', 'rflipbook')],
            'btnNext' => ['icon' => 'fa-chevron-right', 'title' => LocalizationUtility::translate('btnNext', 'rflipbook')],
            'btnLast' => ['icon' => 'fa-step-forward', 'title' => LocalizationUtility::translate('btnLast', 'rflipbook')],
            'btnPrev' => ['icon' => 'fa-chevron-left', 'title' => LocalizationUtility::translate('btnPrev', 'rflipbook')],
            'btnFirst' => ['icon' => 'fa-step-backward', 'title' => LocalizationUtility::translate('btnFirst', 'rflipbook')],
            'btnZoomIn' => ['icon' => 'fa-plus', 'title' => LocalizationUtility::translate('btnZoomIn', 'rflipbook')],
            'btnZoomOut' => ['icon' => 'fa-minus', 'title' => LocalizationUtility::translate('btnZoomOut', 'rflipbook')],
            'btnToc' => ['icon' => 'fa-list-ol', 'title' => LocalizationUtility::translate('btnToc', 'rflipbook')],
            'btnThumbs' => ['icon' => 'fa-th-large', 'title' => LocalizationUtility::translate('btnThumbs', 'rflipbook')],
            'btnShare' => ['icon' => 'fa-link', 'title' => LocalizationUtility::translate('btnShare', 'rflipbook')],
            'btnDownloadPages' => ['icon' => 'fa-download', 'title' => LocalizationUtility::translate('btnDownloadPages', 'rflipbook'), 'url' => $this->settings['zipUrl']],
            'btnDownloadPdf' => ['icon' => 'fa-file', 'title' => LocalizationUtility::translate('btnDownloadPdf', 'rflipbook'), 'url' => $this->settings['download']],
            'btnSound' => ['icon' => 'fa-volume-up', 'title' => LocalizationUtility::translate('btnSound', 'rflipbook')],
            'btnExpand' => ['icon' => 'fa-expand', 'title' => LocalizationUtility::translate('btnExpand', 'rflipbook')],
            'btnExpandLightbox' => ['icon' => 'fa-expand', 'title' => LocalizationUtility::translate('btnExpandLightbox', 'rflipbook')],
            'btnPrint' => ['icon' => 'fa-print', 'title' => LocalizationUtility::translate('btnPrint', 'rflipbook')],
            'google_plus' => [],
            'twitter' => [],
            'facebook' => [],
            'pinterest' => [],
            'email' => []
        ];

        foreach ($buttons as $button => $value) {
            if ($this->settings[$button] == 'enabled') {
                $content .= $button . ': {';

                $content .= 'enabled: true,';

                if ($value['title']) {
                    $content .= 'title: "' . $value['title'] . '",';
                }

                if ($value['icon']) {
                    $content .= 'icon: "' . $value['icon'] . '",';
                }

                if ($value['url']) {
                    $content .= 'url: "' . $value['url'] . '",';
                }

                $content = rtrim($content, ',');

                $content .= '},';
            } else {
                $content .= $button . ':{enabled:false},';
            }
        }

        return $content;
    }

    /**
     * @return string
     */
    private function createAssets()
    {

        $content = 'assets: {';

        if (strlen($this->settings['assets']['preloader']) > 0) {
            $content .= 'preloader: "' . $this->uriPage($this->settings['assets']['preloader']) . '",';
        }
        if (strlen($this->settings['assets']['left']) > 0) {
            $content .= 'left: "' . $this->uriPage($this->settings['assets']['left']) . '",';
        }
        if (strlen($this->settings['assets']['right']) > 0) {
            $content .= 'right: "' . $this->uriPage($this->settings['assets']['right']) . '",';
        }
        if (strlen($this->settings['assets']['overlay']) > 0) {
            $content .= 'overlay: "' . $this->uriPage($this->settings['assets']['overlay']) . '",';
        }
        if (strlen($this->settings['assets']['flipMp3']) > 0) {
            $content .= 'flipMp3: "' . $this->uriPage($this->settings['assets']['flipMp3']) . '",';
        }

        $content = rtrim($content, ",");
        $content .= '},';
        return $content;
    }

    /**
     * @return string
     */
    private function renderNotEmptyOptions()
    {
        $content = '';

        $notEmptyOptions = [
            'skin',
            'startPage',
            'pageWidth',
            'pageHeight',
            'thumbnailWidth',
            'thumbnailHeight',
            'pageFlipDuration',
            'cameraDistance',
            'pan',
            'panMax',
            'panMin',
            'tilt',
            'tiltMax',
            'tiltMin',
            'bookX',
            'bookY',
            'bookZ',
            'pageMaterial',
            'pageHardness',
            'coverHardness',
            'pageSegmentsW',
            'pageSegmentsH',
            'pageShininess',
            'pageFlipDuration',
            'pointLightX',
            'pointLightY',
            'pointLightZ',
            'pointLightColor',
            'pointLightIntensity',
            'directionalLightX',
            'directionalLightY',
            'directionalLightZ',
            'directionalLightColor',
            'directionalLightIntensity',
            'ambientLightColor',
            'ambientLightIntensity'
        ];

        foreach ($notEmptyOptions as $option) {
            if (!empty($this->settings[$option])) {
                $content .= $option . ': "' . $this->settings[$option] . '",';
            }
        }
        return $content;
    }

    /**
     * @return string
     */
    private function renderTrueFalseOptions()
    {

        $content = '';

        $enabledOptions = [
            'rightToLeft',
            'sounds',
            'contentOnStart',
            'thumbnailsOnStart',
            'singlePageMode',
            'pageShadow1',
            'pageShadow2',
            'pageShadow3',
            'singlePageModeIfMobile',
            'pdfBrowserViewerIfMobile',
            'btnTocIfMobile',
            'btnThumbsIfMobile',
            'btnShareIfMobile',
            'btnDownloadPagesIfMobile',
            'btnDownloadPdfIfMobile',
            'btnSoundIfMobile',
            'btnExpandIfMobile',
            'btnPrintIfMobile',
            'hideMenu',
            'sideNavigationButtons',
            'webgl',
        ];

        foreach ($enabledOptions as $option) {
            if ($this->settings[$option] == 'enabled') {
                $content .= $option . ": true,";
            } else {
                $content .= $option . ": false,";
            }
        }
        return $content;
    }
}
