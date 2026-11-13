<?php

declare(strict_types=1);

namespace SvBadSchwartau\SvBadSchwartau\DataProcessing;

use SvBadSchwartau\SvBadSchwartau\Domain\Repository\NewstickerRepository;
use TYPO3\CMS\Core\Cache\CacheDataCollector;
use TYPO3\CMS\Core\Cache\CacheTag;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

final class NewstickerProcessor implements DataProcessorInterface
{
    public function __construct(private readonly NewstickerRepository $repository) {}

    public function process(
        ContentObjectRenderer $cObj,
        array $contentObjectConfiguration,
        array $processorConfiguration,
        array $processedData
    ): array {
        $processedData['tickerMessages'] = [];
        $request = $cObj->getRequest();
        $site = $request->getAttribute('site');
        if (!$site instanceof Site) {
            return $processedData;
        }

        $pid = $this->repository->getStoragePid($site);
        if ($pid <= 0) {
            return $processedData;
        }

        // Also tag an empty ticker, so adding its first message invalidates the page.
        $cacheCollector = $request->getAttribute('frontend.cache.collector');
        if ($cacheCollector instanceof CacheDataCollector) {
            $cacheCollector->addCacheTags(
                new CacheTag('svbs_newsticker_pid_' . $pid),
                new CacheTag('pageId_' . $pid),
            );
        }

        $processedData['tickerMessages'] = array_column($this->repository->findByStoragePid($pid), 'message');

        return $processedData;
    }
}
