<?php

defined('TYPO3') or die();

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

$newColumns = [
    'period' => [
        'exclude' => true,
        'label' => 'Zeitraum',
        'config' => [
            'type' => 'input',
            'size' => 30,
            'eval' => 'trim',
        ],
    ],
];

ExtensionManagementUtility::addTCAcolumns(
    'tx_bootstrappackage_timeline_item',
    $newColumns
);

ExtensionManagementUtility::addToAllTCAtypes(
    'tx_bootstrappackage_timeline_item',
    'period',
    '',
    'after:date'
);
