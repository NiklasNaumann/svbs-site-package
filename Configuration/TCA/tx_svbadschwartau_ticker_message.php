<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'Newsticker-Kurzmeldung',
        'label' => 'message',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'sortby' => 'sorting',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'iconfile' => 'EXT:sv_bad_schwartau/Resources/Public/Icons/Extension.svg',
    ],
    'columns' => [
        'message' => [
            'label' => 'Kurzmeldung',
            'config' => [
                'type' => 'text',
                'rows' => 3,
                'eval' => 'trim',
                'required' => true,
                'enableRichtext' => false,
            ],
        ],
        'hidden' => [
            'label' => 'Ausblenden',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'default' => 0,
            ],
        ],
    ],
    'types' => [
        '0' => ['showitem' => 'message, hidden'],
    ],
];
