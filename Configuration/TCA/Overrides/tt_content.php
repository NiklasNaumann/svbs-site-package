<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

ExtensionManagementUtility::addTcaSelectItem('tt_content', 'CType', [
    'label' => 'Newsticker',
    'value' => 'svbs_newsticker',
    'icon' => 'content-text',
    'group' => 'default',
    'description' => 'Zeigt die zentral im Backend-Modul „Newsticker“ verwalteten Kurzmeldungen an.',
]);

$GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes']['svbs_newsticker'] = 'content-text';
$GLOBALS['TCA']['tt_content']['types']['svbs_newsticker'] = [
    // TYPO3 13.4 adds the placement, language and access fields automatically.
    // Messages are edited only in the dedicated Newsticker backend module.
    'showitem' => '',
];
