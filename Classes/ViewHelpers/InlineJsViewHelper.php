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

use TYPO3\CMS\Core\Resource\Exception\InvalidFileException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;
use TYPO3\CMS\Core\Resource\Folder;


/**
 *
 *
 * @package products
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 *
 */
class InlineJsViewHelper extends AbstractTagBasedViewHelper
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
        $this->registerArgument('thumbfolder', Folder::class, 'Thumbnails folder');

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

        if ((int)$this->settings['fullbook'] === 1) {
            $content .= 'lightBox: false,';
        } else {
            $content .= 'lightBox: true,';
        }

        $content .= $this->createAssets();


        $request = $this->renderingContext->getRequest();
        $normalizedParams = $request->getAttribute('normalizedParams');


        switch ($this->settings['mode']) {
            case 'pdf':
                $content .= 'pdfUrl: "' . rtrim($normalizedParams->getSiteUrl(),'/') .'/'. ltrim($this->settings['pdfUrl'],'/') . '",';
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

        if ($this->settings['pageShadow'] === 'pageShadow') {
            $content .= 'pageShadow:true,';
        } else {
            $content .= 'pageShadow:false,';
        }

        if ($this->settings['pointLight'] === 'pointLight') {
            $content .= 'pointLight:true,';
        } else {
            $content .= 'pointLight:false,';
        }

        if ($this->settings['directionalLight'] === 'directionalLight') {
            $content .= 'directionalLight:true,';
        } else {
            $content .= 'directionalLight:false,';
        }

        if ($this->settings['ambientLight'] === 'ambientLight') {
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
    public function uriPage(int $pageUid, array $additionalParams = array(), $pageType = 0, $noCache = FALSE, $noCacheHash = FALSE, $section = '', $linkAccessRestrictedPages = FALSE, $absolute = FALSE, $addQueryString = FALSE, array $argumentsToBeExcludedFromQueryString = array(), $addQueryStringMethod = NULL)
    {

        /** @var UriBuilder $uriBuilder */
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $uri = $uriBuilder->setTargetPageUid($pageUid)->setTargetPageType($pageType)->setNoCache($noCache)->setUseCacheHash(!$noCacheHash)->setSection($section)->setLinkAccessRestrictedPages($linkAccessRestrictedPages)->setArguments($additionalParams)->setCreateAbsoluteUri($absolute)->setAddQueryString($addQueryString)->setArgumentsToBeExcludedFromQueryString($argumentsToBeExcludedFromQueryString)->setAddQueryStringMethod($addQueryStringMethod)->build();
        return $uri;
    }


    /**
     */
    public function uriToResource($path): string
    {
        $uri = '';
        try {
            $uri = PathUtility::getPublicResourceWebPath($path);
        } catch (InvalidFileException $e) {
        }
        return GeneralUtility::locationHeaderUrl($uri);
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
            'currentPage' => ['title' => LocalizationUtility::translate('currentPage', 'flipbook')],
            'btnNext' => ['icon' => 'flipbook-icon-chevron-right', 'title' => LocalizationUtility::translate('btnNext', 'flipbook')],
            'btnLast' => ['icon' => 'flipbook-icon-backward-step', 'title' => LocalizationUtility::translate('btnLast', 'flipbook')],
            'btnPrev' => ['icon' => 'flipbook-icon-chevron-left', 'title' => LocalizationUtility::translate('btnPrev', 'flipbook')],
            'btnFirst' => ['icon' => 'flipbook-icon-backward-step', 'title' => LocalizationUtility::translate('btnFirst', 'flipbook')],
            'btnZoomIn' => ['icon' => 'flipbook-icon-plus', 'title' => LocalizationUtility::translate('btnZoomIn', 'flipbook')],
            'btnZoomOut' => ['icon' => 'flipbook-icon-minus', 'title' => LocalizationUtility::translate('btnZoomOut', 'flipbook')],
            'btnToc' => ['icon' => 'flipbook-icon-list', 'title' => LocalizationUtility::translate('btnToc', 'flipbook')],
            'btnThumbs' => ['icon' => 'flipbook-icon-table-cells-large', 'title' => LocalizationUtility::translate('btnThumbs', 'flipbook')],
            'btnShare' => ['icon' => 'flipbook-icon-link', 'title' => LocalizationUtility::translate('btnShare', 'flipbook')],
            'btnDownloadPages' => ['icon' => 'flipbook-icon-download', 'title' => LocalizationUtility::translate('btnDownloadPages', 'flipbook'), 'url' => $this->settings['zipUrl']],
            'btnDownloadPdf' => ['icon' => 'flipbook-icon-file-pdf', 'title' => LocalizationUtility::translate('btnDownloadPdf', 'flipbook'), 'url' => $this->settings['download']],
            'btnSound' => ['icon' => 'flipbook-icon-volume-xmark', 'iconAlt' => 'flipbook-icon-volume-high', 'title' => LocalizationUtility::translate('btnSound', 'flipbook')],
            'btnExpand' => ['icon' => 'flipbook-icon-expand', 'title' => LocalizationUtility::translate('btnExpand', 'flipbook')],
            'btnExpandLightbox' => ['icon' => 'flipbook-icon-expand', 'title' => LocalizationUtility::translate('btnExpandLightbox', 'flipbook')],
            'btnPrint' => ['icon' => 'flipbook-icon-print', 'title' => LocalizationUtility::translate('btnPrint', 'flipbook')],
            'btnClose' => ['icon' => 'flipbook-icon-xmark', 'title' => LocalizationUtility::translate('btnClose', 'flipbook')],
            'btnSelect' => ['icon' => 'flipbook-icon-i-cursor', 'title' => LocalizationUtility::translate('btnSelect', 'flipbook')],
            'btnAutoplay' => ['icon' => 'flipbook-icon-play', 'iconAlt' => 'flipbook-icon-pause', 'title' => LocalizationUtility::translate('btnAutoplay', 'flipbook')],
            'btnBookmark' => ['icon' => 'flipbook-icon-bookmark', 'title' => LocalizationUtility::translate('btnBookmark', 'flipbook')],
            'google_plus' => [],
            'twitter' => [],
            'facebook' => [],
            'pinterest' => [],
            'email' => []
        ];

        foreach ($buttons as $button => $value) {
            if ($this->settings[$button] === 'enabled') {
                $content .= $button . ': {';

                $content .= 'enabled: true,';

                if ($value['title']) {
                    $content .= 'title: "' . $value['title'] . '",';
                }

                if ($value['icon']) {
                    $content .= 'icon: "' . $value['icon'] . '",';
                }
                if ($value['iconAlt']) {
                    $content .= 'iconAlt: "' . $value['iconAlt'] . '",';
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
    private function createAssets(): string
    {

        $content = 'assets: {';

        if (($this->settings['assets']['preloader'] ?? '') !== '') {
            $content .= 'preloader: "' . $this->uriToResource($this->settings['assets']['preloader']) . '",';
        }
        if (($this->settings['assets']['left'] ?? '') !== '') {
            $content .= 'left: "' . $this->uriToResource($this->settings['assets']['left']) . '",';
        }
        if (($this->settings['assets']['right'] ?? '') !== '') {
            $content .= 'right: "' . $this->uriToResource($this->settings['assets']['right']) . '",';
        }
        if (($this->settings['assets']['overlay'] ?? '') !== '') {
            $content .= 'overlay: "' . $this->uriToResource($this->settings['assets']['overlay']) . '",';
        }
        if (($this->settings['assets']['flipMp3'] ?? '') !== '') {
            $content .= 'flipMp3: "' . $this->uriToResource($this->settings['assets']['flipMp3']) . '",';
        }

        $content = rtrim($content, ",");
        $content .= '},';
        return $content;
    }

    /**
     * @return string
     */
    private function renderNotEmptyOptions(): string
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
            if ($this->settings[$option] === 'enabled') {
                $content .= $option . ": true,";
            } else {
                $content .= $option . ": false,";
            }
        }
        return $content;
    }
}
