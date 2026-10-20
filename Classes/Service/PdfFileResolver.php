<?php

namespace WapplerSystems\Flipbook\Service;

use TYPO3\CMS\Core\LinkHandling\Exception\UnknownLinkHandlerException;
use TYPO3\CMS\Core\LinkHandling\Exception\UnknownUrnException;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceFactory;

/**
 * Resolves the value of the PDF field (settings.pdfUrl) to a file.
 *
 * The field stores the sys_file uid. Values of the former link field are a link like t3://file?uid=925
 * or a path like fileadmin/book.pdf.
 */
class PdfFileResolver
{
    public function __construct(
        private readonly LinkService $linkService,
        private readonly ResourceFactory $resourceFactory,
    ) {}

    public function resolve(string $value): ?File
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        try {
            if (str_starts_with($value, 't3://')) {
                $file = $this->linkService->resolve($value)['file'] ?? null;
            } else {
                $file = $this->resourceFactory->retrieveFileOrFolderObject($value);
            }
        } catch (ResourceDoesNotExistException|UnknownLinkHandlerException|UnknownUrnException) {
            return null;
        }

        return $file instanceof File ? $file : null;
    }
}
