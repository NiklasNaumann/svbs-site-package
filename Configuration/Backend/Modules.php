<?php

declare(strict_types=1);

use SvBadSchwartau\SvBadSchwartau\Controller\NewstickerController;

return [
    'web_svbs_newsticker' => [
        'parent' => 'web',
        'access' => 'user',
        'workspaces' => 'live',
        'path' => '/module/web/svbs-newsticker',
        'iconIdentifier' => 'content-text',
        'labels' => 'LLL:EXT:sv_bad_schwartau/Resources/Private/Language/locallang_newsticker.xlf',
        'inheritNavigationComponentFromMainModule' => false,
        'routes' => [
            '_default' => [
                'target' => NewstickerController::class . '::handleRequest',
                'methods' => ['GET', 'POST'],
            ],
        ],
    ],
];
