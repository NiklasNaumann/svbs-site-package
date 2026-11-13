<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

ExtensionManagementUtility::addTcaSelectItem('tt_content', 'CType', [
    'label' => 'Newsticker',
    'value' => 'svbs_newsticker',
    'icon' => 'content-text',
    'group' => 'default',
    'description' => 'Eigene Kurzmeldungen als Laufband anzeigen. Eine Meldung pro Zeile.',
]);

$GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes']['svbs_newsticker'] = 'content-text';
$GLOBALS['TCA']['tt_content']['types']['svbs_newsticker'] = [
    'showitem' => 'header, bodytext',
    'columnsOverrides' => [
        'header' => [
            'label' => 'Bezeichnung des Newstickers',
            'description' => 'Zum Beispiel „Kurzmeldungen“. Ohne Angabe wird „Kurzmeldungen“ verwendet.',
        ],
        'bodytext' => [
            'label' => 'Kurzmeldungen',
            'description' => 'Eine Meldung pro Zeile. Leerzeilen werden ignoriert. Bitte nur Text eingeben.',
            'config' => [
                'enableRichtext' => false,
                'rows' => 8,
            ],
        ],
    ],
];
