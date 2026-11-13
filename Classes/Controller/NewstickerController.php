<?php

declare(strict_types=1);

namespace SvBadSchwartau\SvBadSchwartau\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use SvBadSchwartau\SvBadSchwartau\Domain\Repository\NewstickerRepository;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final readonly class NewstickerController
{
    public function __construct(
        private ModuleTemplateFactory $moduleTemplateFactory,
        private UriBuilder $uriBuilder,
        private NewstickerRepository $repository,
        private FormProtectionFactory $formProtectionFactory,
        private SiteFinder $siteFinder,
        private CacheManager $cacheManager,
    ) {}

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $user = $GLOBALS['BE_USER'];
        $sites = $this->findAccessibleSites($user);
        $body = $request->getParsedBody();
        $body = is_array($body) ? $body : [];
        $requestedSite = $request->getMethod() === 'POST'
            ? ($body['site'] ?? null)
            : ($request->getQueryParams()['site'] ?? null);
        $identifier = is_string($requestedSite)
            ? $requestedSite
            : ($request->getMethod() === 'POST' ? '' : (array_key_first($sites) ?? ''));
        // Never fall back to a different site's folder for an invalid submitted identifier.
        $site = $sites[$identifier] ?? null;
        $pid = $site['pid'] ?? 0;
        $messages = [];
        if ($site !== null) {
            foreach ($this->repository->findByStoragePid($pid, true) as $record) {
                if ($user->recordEditAccessInternals(NewstickerRepository::TABLE, $record)) {
                    $messages[(int) $record['uid']] = $record;
                }
            }
        }
        $canCreate = $site !== null && $user->recordEditAccessInternals(
            NewstickerRepository::TABLE,
            ['pid' => $pid],
            true,
        );
        $formProtection = $this->formProtectionFactory->createFromRequest($request);
        $error = '';
        $newMessage = '';
        $editDraft = null;
        $editUid = 0;

        if ($request->getMethod() === 'POST') {
            $action = is_string($body['operation'] ?? null) ? $body['operation'] : '';
            $uid = is_scalar($body['uid'] ?? null) ? (int) $body['uid'] : 0;
            $record = $messages[$uid] ?? null;
            $instance = $identifier . ':' . $pid . ':' . ($action === 'create' ? 0 : $uid);
            $text = is_string($body['message'] ?? null) ? $body['message'] : null;
            if ($action === 'create') {
                $newMessage = $text ?? '';
            } elseif ($action === 'update') {
                $editDraft = $text;
                $editUid = $uid;
            }

            if ($site === null || !in_array($action, ['create', 'update', 'toggle', 'delete'], true)) {
                $error = 'Kein zugänglicher Speicherordner oder ungültige Aktion.';
            } elseif (!is_string($body['formToken'] ?? null)
                || !$formProtection->validateToken($body['formToken'], 'svbs_newsticker', $action, $instance)
            ) {
                $error = 'Das Formular ist abgelaufen. Bitte die Eingabe prüfen und erneut speichern.';
            } elseif (($action === 'create' && !$canCreate) || ($action !== 'create' && $record === null)) {
                $error = 'Für diese Kurzmeldung fehlen die Bearbeitungsrechte.';
            } elseif ($action !== 'create' && (!is_string($body['revision'] ?? null)
                || !hash_equals($this->revision($record), $body['revision']))
            ) {
                $error = 'Die Meldung wurde zwischenzeitlich geändert. Bitte den aktuellen Text mit deiner Eingabe vergleichen.';
            } else {
                $normalized = $text === null ? '' : (preg_replace('/\s+/u', ' ', trim($text)) ?? '');
                if (in_array($action, ['create', 'update'], true)
                    && ($normalized === '' || mb_strlen($normalized, 'UTF-8') > 1000)
                ) {
                    $error = 'Bitte eine Kurzmeldung mit 1 bis 1000 Zeichen eingeben.';
                } else {
                    $this->writeMessage($action, $pid, $uid, $normalized, $record, $error);
                    if ($error === '') {
                        $this->cacheManager->flushCachesInGroupByTag('pages', 'svbs_newsticker_pid_' . $pid);
                        return new RedirectResponse((string) $this->uriBuilder->buildUriFromRoute(
                            'web_svbs_newsticker',
                            ['site' => $identifier, 'saved' => 1],
                        ), 303);
                    }
                }
            }
        }

        foreach ($messages as &$message) {
            $instance = $identifier . ':' . $pid . ':' . $message['uid'];
            foreach (['update', 'toggle', 'delete'] as $action) {
                $message['tokens'][$action] = $formProtection->generateToken('svbs_newsticker', $action, $instance);
            }
            $message['revision'] = $this->revision($message);
            $message['editMessage'] = $editUid === (int) $message['uid'] && $editDraft !== null
                ? $editDraft
                : $message['message'];
        }
        unset($message);

        foreach ($sites as &$website) {
            $website['uri'] = (string) $this->uriBuilder->buildUriFromRoute('web_svbs_newsticker', ['site' => $website['identifier']]);
            $website['selected'] = $website['identifier'] === $identifier;
        }
        unset($website);

        $view = $this->moduleTemplateFactory->create($request);
        $view->setTitle('Newsticker');
        $view->assignMultiple([
            'sites' => $sites,
            'selectedSite' => $identifier,
            'storage' => $site,
            'messages' => $messages,
            'canCreate' => $canCreate,
            'newMessage' => $newMessage,
            'moduleUri' => (string) $this->uriBuilder->buildUriFromRoute('web_svbs_newsticker'),
            'createToken' => $formProtection->generateToken('svbs_newsticker', 'create', $identifier . ':' . $pid . ':0'),
            'error' => $error,
            'saved' => $site !== null && ($request->getQueryParams()['saved'] ?? '') === '1',
        ]);
        return $view->renderResponse('Backend/Newsticker');
    }

    private function findAccessibleSites(BackendUserAuthentication $user): array
    {
        if ($user->workspace !== 0 || !$user->check('tables_modify', NewstickerRepository::TABLE)) {
            return [];
        }
        $sites = [];
        foreach ($this->siteFinder->getAllSites() as $site) {
            $pid = $this->repository->getStoragePid($site);
            if ($pid <= 0) {
                continue;
            }
            $folder = BackendUtility::readPageAccess($pid, $user->getPagePermsClause(Permission::CONTENT_EDIT));
            if (!$folder) {
                continue;
            }
            $identifier = $site->getIdentifier();
            $sites[$identifier] = [
                'identifier' => $identifier,
                'title' => ($site->getConfiguration()['websiteTitle'] ?? '') ?: $identifier,
                'pid' => $pid,
                'folderTitle' => $folder['title'] ?? '',
            ];
        }
        return $sites;
    }

    private function revision(array $record): string
    {
        return hash('sha256', serialize([$record['message'], (int) $record['hidden'], (int) $record['tstamp']]));
    }

    private function writeMessage(string $action, int $pid, int $uid, string $text, ?array $record, string &$error): void
    {
        $data = [];
        $commands = [];
        if ($action === 'delete') {
            $commands = [NewstickerRepository::TABLE => [$uid => ['delete' => 1]]];
        } elseif ($action === 'create') {
            $data = [NewstickerRepository::TABLE => ['NEWsvbsTicker' => ['pid' => $pid, 'message' => $text, 'hidden' => 0]]];
        } else {
            $fields = $action === 'toggle' ? ['hidden' => (int) !$record['hidden']] : ['message' => $text];
            $data = [NewstickerRepository::TABLE => [$uid => $fields]];
        }
        // TYPO3 handles permissions, history, soft deletion and the normal record cache tags.
        $handler = GeneralUtility::makeInstance(DataHandler::class);
        $handler->start($data, $commands);
        if ($commands !== []) {
            $handler->process_cmdmap();
        } else {
            $handler->process_datamap();
        }
        if ($handler->errorLog !== []) {
            $error = 'Die Änderung konnte nicht gespeichert werden. Bitte Berechtigungen und Datensatzsperren prüfen.';
        }
    }
}
