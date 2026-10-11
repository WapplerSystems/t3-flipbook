<?php

namespace WapplerSystems\Flipbook\Controller;


use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;


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
        /** @var ResourceFactory $factory */
        $factory = GeneralUtility::makeInstance(ResourceFactory::class);
        $files = [];
        $pdfUrl = '';

        if (($this->settings['mode'] ?? '') === 'pdf') {
            $value = trim((string)($this->settings['pdfUrl'] ?? ''));
            if ($value !== '') {
                $file = null;
                try {
                    if (str_starts_with($value, 't3://')) {
                        // stored by the link field: t3://file?uid=123
                        $file = GeneralUtility::makeInstance(LinkService::class)->resolve($value)['file'] ?? null;
                    } elseif (!preg_match('#^https?://#i', $value)) {
                        $file = $factory->getFileObjectFromCombinedIdentifier($value);
                    }
                } catch (\Throwable) {
                    $file = null;
                }
                if ($file instanceof FileInterface) {
                    $files = [$file];
                    $pdfUrl = (string)$file->getPublicUrl();
                } elseif (preg_match('#^https?://#i', $value)) {
                    $pdfUrl = $value;
                }
            }
        } elseif (($this->settings['folder'] ?? '') !== '') {
            $folder = $factory->getFolderObjectFromCombinedIdentifier($this->settings['folder']);
            $files = array_values(array_filter($folder->getFiles(), static function ($file) {
                return in_array(strtolower($file->getExtension()), ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
            }));
        }
        $this->view->assign('files', $files);
        $this->view->assign('pdfUrl', $pdfUrl);

        if (!empty($this->settings['thumbs'])) {
            /** @var Folder $thumbFolder */
            $thumbFolder = $factory->getFolderObjectFromCombinedIdentifier($this->settings['thumbs']);
            $this->view->assign('thumbfolder', $thumbFolder);
        }
        /** preview image */
        if (($this->settings['preview'] ?? '') === '1') {
            /** @var FileRepository $fileRepository */
            $fileRepository = GeneralUtility::makeInstance(FileRepository::class);
            $preview = $fileRepository->findByRelation('tt_content', 'settings.preview', (int)($this->configurationManager->getContentObject()?->data['uid'] ?? 0));
            $this->view->assign('preview', $preview[0] ?? null);
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
