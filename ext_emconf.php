<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'SV Bad Schwartau',
    'description' => '',
    'category' => 'templates',
    'constraints' => [
        'depends' => [
            'bootstrap_package' => '16.0.0-16.99.99',
        ],
        'conflicts' => [
        ],
    ],
    'autoload' => [
        'psr-4' => [
            'SvBadSchwartau\\SvBadSchwartau\\' => 'Classes',
        ],
    ],
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 1,
    'author' => 'Niklas Naumann',
    'author_email' => 'niklas.naumann@schachinschwartau.de',
    'author_company' => 'SV Bad Schwartau',
    'version' => '1.0.0',
];
