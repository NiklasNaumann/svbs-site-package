<?php

declare(strict_types=1);

namespace SvBadSchwartau\SvBadSchwartau\DataProcessing;

use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

final class NewstickerProcessor implements DataProcessorInterface
{
    public function process(
        ContentObjectRenderer $cObj,
        array $contentObjectConfiguration,
        array $processorConfiguration,
        array $processedData
    ): array {
        $lines = preg_split('/\R/u', (string) ($processedData['data']['bodytext'] ?? '')) ?: [];
        $processedData['tickerMessages'] = array_values(array_filter(
            array_map('trim', $lines),
            static fn (string $line): bool => $line !== ''
        ));

        return $processedData;
    }
}
