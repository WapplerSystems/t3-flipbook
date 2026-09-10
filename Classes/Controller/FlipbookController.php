<?php

namespace WapplerSystems\Flipbook\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class FlipbookController extends ActionController
{
    /**
     * Injects the Configuration Manager and is initializing the framework settings
     */
    public function injectConfigurationManager(ConfigurationManagerInterface $configurationManager): void
    {
        parent::injectConfigurationManager($configurationManager);

        $tsSettings = $this->settings;
        $originalSettings = $this->configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS
        );

        if (isset($tsSettings['overrideFlexformSettingsIfEmpty']) && $tsSettings['overrideFlexformSettingsIfEmpty'] == 1) {
            // if flexform setting is empty and value is available in TS
            foreach ($tsSettings as $key => $value) {
                if ($key === 'img.') {
                    continue;
                }
                if ($key === 'assets.') {
                    continue;
                }
                if ($key === 'deepLinking.') {
                    continue;
                }

                if (!$originalSettings[$key] && isset($value)) {
                    $originalSettings[$key] = $value;
                }
            }
            if (isset($tsSettings['img.'])) {
                foreach ($tsSettings['img.'] as $key => $value) {
                    if (!$originalSettings['img'][$key] && isset($value)) {
                        $originalSettings['img'][$key] = $value;
                    }
                }
            }
            if (isset($tsSettings['assets.'])) {
                foreach ($tsSettings['assets.'] as $key => $value) {
                    if (!$originalSettings['assets'][$key] && isset($value)) {
                        $originalSettings['assets'][$key] = $value;
                    }
                }
            }
            if (isset($tsSettings['deepLinking.'])) {
                foreach ($tsSettings['deepLinking.'] as $key => $value) {
                    if (!$originalSettings['deepLinking'][$key] && isset($value)) {
                        $originalSettings['deepLinking'][$key] = $value;
                    }
                }
            }
        }

        $this->settings = $originalSettings;
    }


    /**
     *
     */
    public function showAction(): ResponseInterface
    {
        if (($this->settings['mode'] ?? '') === 'pdf') {

            if (($this->settings['pdfUrl'] ?? '') !== '') {

                $pdfValue = trim((string)$this->settings['pdfUrl']);
                $factory = GeneralUtility::makeInstance(ResourceFactory::class);
                $file = null;

                if (str_starts_with($pdfValue, 't3://')) {
                    // Legacy format stored via type=link: t3://file?uid=925
                    $linkService = GeneralUtility::makeInstance(LinkService::class);
                    $linkData = $linkService->resolve($pdfValue);
                    $file = $linkData['file'] ?? null;
                } elseif (is_numeric($pdfValue)) {
                    // Current format stored via type=group: plain sys_file UID
                    $file = $factory->getFileObject((int)$pdfValue);
                }

                if ($file !== null) {
                    $this->view->assign('pdfUrl', $file->getPublicUrl());
                    $this->view->assign('files', [$file]);
                }
            }

        } else {

            /** @var string $bigImageFolder */
            $bigImageFolder = $this->settings['folder'] ?? '';
            /** @var ResourceFactory $factory */
            $factory = GeneralUtility::makeInstance(ResourceFactory::class);

            if ($bigImageFolder !== '') {
                $folder = $factory->getFolderObjectFromCombinedIdentifier($bigImageFolder);

                $files = $folder->getFiles();
                $imageFiles = array_filter($files, function($file) {
                    return in_array($file->getExtension(), ['jpg','jpeg','png','gif','webp']);
                });

                $this->view->assign('files', $imageFiles);
            }

        }

        if (!empty($this->settings['thumbs'])) {
            /** @var Folder $thumbFolder */
            $thumbFolder = $factory->getFolderObjectFromCombinedIdentifier($this->settings['thumbs']);
            $this->view->assign('thumbfolder', $thumbFolder);
        }
        /** preview image */
        if (($this->settings['preview'] ?? '') === '1') {
            /** @var FileRepository $fileRepository */
            $fileRepository = GeneralUtility::makeInstance(FileRepository::class);
            /** @var FileReference $preview */
            $preview = $fileRepository->findByRelation('tt_content', 'settings.preview', $this->request->getAttribute('currentContentObject')->data['uid']);
            $this->view->assign('preview', $preview[0]);
        }
        if (!is_array($this->settings['toc'] ?? false)) {
            unset($this->settings['toc']);
        }
        $this->view->assign('uid', uniqid());

        $code = $this->view->render();

        return $this->htmlResponse($this->sanitize_output($code));
    }

    // TODO remove or finalize this function
    protected function sanitize_output(string $buffer): string
    {
        return $buffer;

        $search = [
            '/\>[^\S ]+/s',  // strip whitespaces after tags, except space
            '/[^\S ]+\</s',  // strip whitespaces before tags, except space
            '/(\s)+/s'       // shorten multiple whitespace sequences
        ];

        $replace = [
            '>',
            '<',
            '\\1'
        ];

        return preg_replace($search, $replace, $buffer);
    }
}
