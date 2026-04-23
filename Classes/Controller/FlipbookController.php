<?php

namespace WapplerSystems\Flipbook\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

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
        if ($this->settings['mode'] === 'pdf') {

            if ($this->settings['pdfUrl'] !== '') {

                $instructions = [
                    'parameter' => $this->settings['pdfUrl'],
                ];

                $contentObject = GeneralUtility::makeInstance(ContentObjectRenderer::class);
                $url = $contentObject->createUrl($instructions);
                $this->view->assign('pdfUrl', $url);

                $factory = GeneralUtility::makeInstance(ResourceFactory::class);
                $file = $factory->getFileObjectFromCombinedIdentifier($url);

                $this->view->assign('files', [$file]);
            }

        } else {

            /** @var string $bigImageFolder */
            $bigImageFolder = $this->settings['folder'];
            /** @var ResourceFactory $factory */
            $factory = GeneralUtility::makeInstance(ResourceFactory::class);
            $folder = $factory->getFolderObjectFromCombinedIdentifier($bigImageFolder);

            $files = $folder->getFiles();
            $imageFiles = array_filter($files, function ($file) {
                return in_array($file->getExtension(),['jpg','jpeg','png','gif','webp']);
            });

            $this->view->assign('files', $imageFiles);

        }

        if (!empty($this->settings['thumbs'])) {
            /** @var Folder $thumbFolder */
            $thumbFolder = $factory->getFolderObjectFromCombinedIdentifier($this->settings['thumbs']);
            $this->view->assign('thumbfolder', $thumbFolder);
        }
        /** preview image */
        if ($this->settings['preview'] === '1') {
            /** @var FileRepository $fileRepository */
            $fileRepository = GeneralUtility::makeInstance(FileRepository::class);
            /** @var FileReference $preview */
            $preview = $fileRepository->findByRelation('tt_content', 'settings.preview', $this->configurationManager->getContentObject()->data['uid']);
            $this->view->assign('preview', $preview[0]);
        }
        if (!is_array($this->settings['toc'] ?? false)) {
            unset($this->settings['toc']);
        }
        $this->view->assign('uid', uniqid());

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

        return preg_replace($search, $replace, $buffer);
    }

}
