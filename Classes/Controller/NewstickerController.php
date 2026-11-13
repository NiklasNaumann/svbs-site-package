<?php

declare(strict_types=1);

namespace SvBadSchwartau\SvBadSchwartau\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final readonly class NewstickerController
{
    public function __construct(
        private ModuleTemplateFactory $moduleTemplateFactory,
        private UriBuilder $uriBuilder,
        private ConnectionPool $connectionPool,
        private FormProtectionFactory $formProtectionFactory,
    ) {}

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $user = $GLOBALS['BE_USER'];
        $tickers = $this->findEditableTickers($user);
        $formProtection = $this->formProtectionFactory->createFromRequest($request);
        $moduleUri = (string) $this->uriBuilder->buildUriFromRoute('web_svbs_newsticker');
        $error = '';

        if ($request->getMethod() === 'POST') {
            $body = $request->getParsedBody();
            $uid = is_array($body) && is_scalar($body['uid'] ?? null) ? (int) $body['uid'] : 0;
            $ticker = $tickers[$uid] ?? null;
            if (!is_array($body)
                || !is_string($body['formToken'] ?? null)
                || !$formProtection->validateToken($body['formToken'], 'svbs_newsticker', 'save', (string) $uid)
            ) {
                $error = 'Das Formular ist abgelaufen. Bitte erneut speichern.';
            } elseif ($ticker === null || !is_string($body['messages'] ?? null)) {
                $error = 'Dieser Newsticker kann mit deinen Berechtigungen nicht bearbeitet werden.';
            } elseif (!is_string($body['revision'] ?? null)
                || !hash_equals(hash('sha256', (string) $ticker['bodytext']), $body['revision'])
            ) {
                $error = 'Die Kurzmeldungen wurden zwischenzeitlich geändert. Bitte den aktuellen Stand prüfen.';
            } else {
                // DataHandler enforces TYPO3 permissions, records history and clears the page cache.
                $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
                $dataHandler->start(['tt_content' => [$uid => ['bodytext' => $body['messages']]]], []);
                $dataHandler->process_datamap();
                if ($dataHandler->errorLog === []) {
                    return new RedirectResponse(
                        (string) $this->uriBuilder->buildUriFromRoute('web_svbs_newsticker', ['saved' => $uid]),
                        303,
                    );
                }
                $error = 'Die Kurzmeldungen konnten nicht gespeichert werden. Bitte Berechtigungen und Datensatzsperren prüfen.';
            }
        }

        foreach ($tickers as &$ticker) {
            $ticker['formToken'] = $formProtection->generateToken('svbs_newsticker', 'save', (string) $ticker['uid']);
            $ticker['revision'] = hash('sha256', (string) $ticker['bodytext']);
        }
        unset($ticker);

        $view = $this->moduleTemplateFactory->create($request);
        $view->setTitle('Newsticker');
        $saved = $request->getQueryParams()['saved'] ?? null;
        $view->assignMultiple([
            'tickers' => $tickers,
            'moduleUri' => $moduleUri,
            'error' => $error,
            'saved' => is_scalar($saved) && isset($tickers[(int) $saved]),
        ]);
        return $view->renderResponse('Backend/Newsticker');
    }

    private function findEditableTickers(BackendUserAuthentication $user): array
    {
        // The module is intentionally limited to live records, matching its registration.
        if ($user->workspace !== 0
            || !$user->check('tables_modify', 'tt_content')
            || (!$user->isAdmin()
                && !empty($GLOBALS['TCA']['tt_content']['columns']['bodytext']['exclude'])
                && !$user->check('non_exclude_fields', 'tt_content:bodytext'))
        ) {
            return [];
        }

        $query = $this->connectionPool->getQueryBuilderForTable('tt_content');
        // Hidden/scheduled tickers must remain editable; deleted and workspace records must not.
        $query->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $records = $query->select('*')->from('tt_content')->where(
            $query->expr()->eq('CType', $query->createNamedParameter('svbs_newsticker')),
            $query->expr()->eq('t3ver_wsid', $query->createNamedParameter(0, Connection::PARAM_INT)),
        )->orderBy('pid')->addOrderBy('sorting')->executeQuery()->fetchAllAssociative();

        $tickers = [];
        foreach ($records as $record) {
            $page = BackendUtility::readPageAccess(
                (int) $record['pid'],
                $user->getPagePermsClause(Permission::CONTENT_EDIT),
            );
            if (!$page || !$user->recordEditAccessInternals('tt_content', $record)) {
                continue;
            }
            $record['pageTitle'] = $page['title'] ?? '';
            $record['pageId'] = (int) $record['pid'];
            $tickers[(int) $record['uid']] = $record;
        }
        return $tickers;
    }
}
