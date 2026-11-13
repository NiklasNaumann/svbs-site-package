<?php

declare(strict_types=1);

namespace SvBadSchwartau\SvBadSchwartau\Domain\Repository;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final readonly class NewstickerRepository
{
    public const TABLE = 'tx_svbadschwartau_ticker_message';
    public const SETTING = 'svbs.newsticker.storagePid';

    public function __construct(private ConnectionPool $connectionPool) {}

    public function getStoragePid(Site $site): int
    {
        return max(0, (int) $site->getSettings()->get(self::SETTING, 0));
    }

    public function findByStoragePid(int $pid, bool $includeHidden = false): array
    {
        if ($pid <= 0) {
            return [];
        }

        $query = $this->connectionPool->getQueryBuilderForTable(self::TABLE);
        $query->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        if (!$includeHidden) {
            $query->getRestrictions()->add(GeneralUtility::makeInstance(HiddenRestriction::class));
        }

        return $query->select('*')->from(self::TABLE)->where(
            $query->expr()->eq('pid', $query->createNamedParameter($pid, Connection::PARAM_INT)),
        )->orderBy('crdate', 'DESC')->addOrderBy('uid', 'DESC')->executeQuery()->fetchAllAssociative();
    }
}
