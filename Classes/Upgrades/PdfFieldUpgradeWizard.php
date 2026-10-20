<?php

namespace WapplerSystems\Flipbook\Upgrades;

use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\ChattyInterface;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;
use WapplerSystems\Flipbook\Service\PdfFileResolver;

/**
 * The PDF field (settings.pdfUrl) was a link field and is a group field on sys_file now. The group field
 * only accepts the sys_file uid, so the backend shows former values (t3://file?uid=925, fileadmin/book.pdf)
 * as empty and saving the content element removes the PDF. This wizard replaces them with the sys_file uid.
 */
#[UpgradeWizard('flipbook_pdfFieldFileUid')]
class PdfFieldUpgradeWizard implements UpgradeWizardInterface, ChattyInterface
{
    private const FIELD = 'settings.pdfUrl';

    private ?OutputInterface $output = null;

    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly FlexFormTools $flexFormTools,
        private readonly ReferenceIndex $referenceIndex,
        private readonly PdfFileResolver $pdfFileResolver,
    ) {}

    public function getTitle(): string
    {
        return 'Flipbook: store the sys_file uid in the PDF field';
    }

    public function getDescription(): string
    {
        return 'The PDF field of flipbook content elements stored a link (t3://file?uid=… or a path like '
            . 'fileadmin/book.pdf) and is a file selection now. The backend shows these values as empty, and saving '
            . 'the content element removes the PDF. This wizard replaces them with the uid of the file.';
    }

    public function setOutput(OutputInterface $output): void
    {
        $this->output = $output;
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    public function updateNecessary(): bool
    {
        return $this->getRecordsToMigrate() !== [];
    }

    public function executeUpdate(): bool
    {
        $connection = $this->connectionPool->getConnectionForTable('tt_content');

        foreach ($this->getRecordsToMigrate() as $record) {
            $flexForm = $record['flexForm'];
            $file = $this->pdfFileResolver->resolve($record['value']);
            if ($file === null) {
                $this->output?->writeln(sprintf(
                    '<comment>Content element %d: "%s" is not a file, please select the PDF again.</comment>',
                    $record['uid'],
                    $record['value']
                ));
                continue;
            }

            $flexForm['data'][$record['sheet']]['lDEF'][self::FIELD]['vDEF'] = (string)$file->getUid();
            $connection->update(
                'tt_content',
                ['pi_flexform' => $this->flexFormTools->flexArray2Xml($flexForm)],
                ['uid' => $record['uid']]
            );
            $this->referenceIndex->updateRefIndexTable('tt_content', $record['uid'], false, $record['workspace']);
        }

        return true;
    }

    /**
     * @return array<int, array{uid: int, workspace: int, flexForm: array, sheet: string, value: string}>
     */
    private function getRecordsToMigrate(): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $rows = $queryBuilder
            ->select('uid', 't3ver_wsid', 'pi_flexform')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq('CType', $queryBuilder->createNamedParameter('flipbook_show')),
                $queryBuilder->expr()->like(
                    'pi_flexform',
                    $queryBuilder->createNamedParameter('%' . $queryBuilder->escapeLikeWildcards(self::FIELD) . '%')
                )
            )
            ->executeQuery()
            ->fetchAllAssociative();

        $records = [];
        foreach ($rows as $row) {
            $flexForm = GeneralUtility::xml2array((string)$row['pi_flexform']);
            if (!is_array($flexForm)) {
                continue;
            }
            foreach ($flexForm['data'] ?? [] as $sheet => $sheetData) {
                $value = trim((string)($sheetData['lDEF'][self::FIELD]['vDEF'] ?? ''));
                if ($value !== '' && !MathUtility::canBeInterpretedAsInteger($value)) {
                    $records[] = [
                        'uid' => (int)$row['uid'],
                        'workspace' => (int)$row['t3ver_wsid'],
                        'flexForm' => $flexForm,
                        'sheet' => (string)$sheet,
                        'value' => $value,
                    ];
                }
            }
        }

        return $records;
    }
}
