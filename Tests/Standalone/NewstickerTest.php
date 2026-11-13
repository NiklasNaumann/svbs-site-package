<?php
/**
 * Run separately: php Tests/Standalone/NewstickerTest.php
 * Controlled API doubles exercise application boundaries without a TYPO3/database installation.
 * This does not replace a TYPO3 integration test. Do not bootstrap TYPO3 in this process.
 */
declare(strict_types=1);

namespace Psr\Http\Message { interface ResponseInterface {} interface ServerRequestInterface {} }
namespace TYPO3\CMS\Backend\Routing {
    class UriBuilder { public function buildUriFromRoute($route, $parameters = []) { return '/module?token=route&' . http_build_query($parameters); } }
}
namespace TYPO3\CMS\Backend\Template {
    class ModuleTemplateFactory { public function create($request) { return new View(); } }
    class View implements \Psr\Http\Message\ResponseInterface {
        public array $data = [];
        public function setTitle($title) {}
        public function assignMultiple($data) { $this->data = $data; }
        public function renderResponse($template) { return $this; }
    }
}
namespace TYPO3\CMS\Backend\Utility {
    class BackendUtility {
        public static array $denied = [];
        public static function readPageAccess($pid, $clause) { return in_array($pid, self::$denied, true) ? false : ['title' => 'Folder ' . $pid]; }
    }
}
namespace TYPO3\CMS\Core\Authentication {
    class BackendUserAuthentication {
        public int $workspace = 0;
        public bool $tableAllowed = true;
        public bool $recordAllowed = true;
        public function check($name, $value) { return $this->tableAllowed; }
        public function getPagePermsClause($permission) { return 'permissions'; }
        public function recordEditAccessInternals($table, $record, $new = false) { return $this->recordAllowed; }
    }
}
namespace TYPO3\CMS\Core\DataHandling {
    class DataHandler {
        public static array $writes = [];
        public static bool $fail = false;
        public array $errorLog = [];
        public function start($data, $commands) { self::$writes[] = [$data, $commands]; }
        public function process_datamap() { if (self::$fail) { $this->errorLog[] = 'denied'; } }
        public function process_cmdmap() { $this->process_datamap(); }
    }
}
namespace TYPO3\CMS\Core\Database\Query\Restriction {
    class DeletedRestriction {}
    class HiddenRestriction {}
}
namespace TYPO3\CMS\Core\Database {
    class Connection { public const PARAM_INT = 1; }
    class ConnectionPool {
        public static array $records = [];
        public function getQueryBuilderForTable($table) { return new Query(); }
    }
    class Query {
        private array $restrictions = [];
        private array $conditions = [];
        private array $ordering = [];
        public function getRestrictions() { return $this; }
        public function removeAll() { $this->restrictions = []; return $this; }
        public function add($restriction) { $this->restrictions[] = $restriction; return $this; }
        public function select(...$fields) { return $this; }
        public function from($table) { return $this; }
        public function expr() { return $this; }
        public function eq($field, $value) { return [$field, $value]; }
        public function createNamedParameter($value, $type = null) { return $value; }
        public function where(...$conditions) { $this->conditions = $conditions; return $this; }
        public function orderBy($field, $direction = 'ASC') { $this->ordering = [[$field, $direction]]; return $this; }
        public function addOrderBy($field, $direction = 'ASC') { $this->ordering[] = [$field, $direction]; return $this; }
        public function executeQuery() { return $this; }
        public function fetchAllAssociative() {
            $rows = array_filter(ConnectionPool::$records, function ($row) {
                foreach ($this->conditions as [$field, $value]) { if ($row[$field] !== $value) { return false; } }
                foreach ($this->restrictions as $restriction) {
                    if ($restriction instanceof \TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction && $row['deleted']) { return false; }
                    if ($restriction instanceof \TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction && $row['hidden']) { return false; }
                }
                return true;
            });
            usort($rows, function ($a, $b) {
                foreach ($this->ordering as [$field, $direction]) {
                    $comparison = $a[$field] <=> $b[$field];
                    if ($comparison !== 0) { return $direction === 'DESC' ? -$comparison : $comparison; }
                }
                return 0;
            });
            return $rows;
        }
    }
}
namespace TYPO3\CMS\Core\FormProtection {
    class FormProtectionFactory { public function createFromRequest($request) { return new Protection(); } }
    class Protection {
        public function generateToken($name, $action, $instance) { return hash('sha256', $name . '|' . $action . '|' . $instance); }
        public function validateToken($token, $name, $action, $instance) { return hash_equals($this->generateToken($name, $action, $instance), $token); }
    }
}
namespace TYPO3\CMS\Core\Http {
    class RedirectResponse implements \Psr\Http\Message\ResponseInterface { public function __construct(public string $uri, public int $status) {} }
}
namespace TYPO3\CMS\Core\Type\Bitmask { class Permission { public const CONTENT_EDIT = 16; } }
namespace TYPO3\CMS\Core\Utility { class GeneralUtility { public static function makeInstance($class) { return new $class(); } } }
namespace TYPO3\CMS\Core\Site\Entity {
    class Site {
        public function __construct(private string $identifier, private int $pid) {}
        public function getIdentifier() { return $this->identifier; }
        public function getConfiguration() { return ['websiteTitle' => $this->identifier]; }
        public function getSettings() { return new Settings($this->pid); }
    }
    class Settings { public function __construct(private int $pid) {} public function get($name, $default = null) { return $this->pid; } }
}
namespace TYPO3\CMS\Core\Site { class SiteFinder { public static array $sites = []; public function getAllSites() { return self::$sites; } } }
namespace TYPO3\CMS\Core\Cache {
    class CacheManager {
        public static array $flushes = [];
        public function flushCachesInGroupByTag($group, $tag) { self::$flushes[] = [$group, $tag]; }
    }
    class CacheTag { public function __construct(public string $name) {} }
    class CacheDataCollector {
        public array $tags = [];
        public function addCacheTags(...$tags) { foreach ($tags as $tag) { $this->tags[] = $tag->name; } }
    }
}
namespace TYPO3\CMS\Frontend\ContentObject {
    interface DataProcessorInterface { public function process(ContentObjectRenderer $cObj, array $contentObjectConfiguration, array $processorConfiguration, array $processedData): array; }
    class ContentObjectRenderer { public function __construct(private $request) {} public function getRequest() { return $this->request; } }
}
namespace {
    use SvBadSchwartau\SvBadSchwartau\Controller\NewstickerController;
    use SvBadSchwartau\SvBadSchwartau\Domain\Repository\NewstickerRepository;
    use SvBadSchwartau\SvBadSchwartau\DataProcessing\NewstickerProcessor;
    use TYPO3\CMS\Core\DataHandling\DataHandler;
    use TYPO3\CMS\Core\Database\ConnectionPool;
    use TYPO3\CMS\Core\Cache\CacheManager;
    use TYPO3\CMS\Core\Site\Entity\Site;

    $root = dirname(__DIR__, 2);
    require $root . '/Classes/Controller/NewstickerController.php';
    require $root . '/Classes/Domain/Repository/NewstickerRepository.php';
    require $root . '/Classes/DataProcessing/NewstickerProcessor.php';
    class Request implements \Psr\Http\Message\ServerRequestInterface {
        public function __construct(private string $method = 'GET', private array $body = [], private array $query = [], private array $attributes = []) {}
        public function getMethod() { return $this->method; }
        public function getParsedBody() { return $this->body; }
        public function getQueryParams() { return $this->query; }
        public function getAttribute($name) { return $this->attributes[$name] ?? null; }
    }
    $checks = 0;
    function check($condition, string $message): void { global $checks; ++$checks; if (!$condition) { throw new \RuntimeException($message); } }
    function resetState(): void {
        $GLOBALS['BE_USER'] = new \TYPO3\CMS\Core\Authentication\BackendUserAuthentication();
        \TYPO3\CMS\Backend\Utility\BackendUtility::$denied = [];
        \TYPO3\CMS\Core\Site\SiteFinder::$sites = [new Site('home', 10), new Site('other', 20), new Site('disabled', 0)];
        DataHandler::$writes = []; DataHandler::$fail = false; CacheManager::$flushes = [];
        ConnectionPool::$records = [];
        foreach ([[1,10,0,0,100], [2,10,1,0,200], [3,20,0,0,300], [4,10,0,1,400], [5,10,0,0,500]] as [$uid,$pid,$hidden,$deleted,$crdate]) {
            ConnectionPool::$records[] = compact('uid','pid','hidden','deleted','crdate') + ['message' => 'Message ' . $uid, 'tstamp' => 1];
        }
    }
    $repository = new NewstickerRepository(new ConnectionPool());
    $controller = new NewstickerController(new \TYPO3\CMS\Backend\Template\ModuleTemplateFactory(), new \TYPO3\CMS\Backend\Routing\UriBuilder(), $repository, new \TYPO3\CMS\Core\FormProtection\FormProtectionFactory(), new \TYPO3\CMS\Core\Site\SiteFinder(), new CacheManager());
    function form($controller, string $operation, int $uid = 1): array {
        $view = $controller->handleRequest(new Request())->data;
        return ['site' => 'home', 'operation' => $operation, 'uid' => $uid,
            'formToken' => $operation === 'create' ? $view['createToken'] : $view['messages'][$uid]['tokens'][$operation],
            'revision' => $operation === 'create' ? '' : $view['messages'][$uid]['revision'], 'message' => ' New message '];
    }

    resetState();
    $view = $controller->handleRequest(new Request('GET', [], ['id' => 999]))->data;
    check($view['selectedSite'] === 'home' && count($view['sites']) === 2, 'module depends on page selection');
    check(array_keys($view['messages']) === [5,2,1], 'backend order or hidden/deleted/folder filtering incorrect');
    check(array_column($repository->findByStoragePid(10), 'uid') === [5,1], 'frontend order or hidden/deleted/folder filtering incorrect');
    check(array_keys($controller->handleRequest(new Request('GET', [], ['site'=>'other']))->data['messages']) === [3], 'site selection uses wrong folder');
    foreach (['workspace','table','folder','record'] as $denial) {
        resetState();
        switch ($denial) {
            case 'workspace': $GLOBALS['BE_USER']->workspace = 1; break;
            case 'table': $GLOBALS['BE_USER']->tableAllowed = false; break;
            case 'folder': \TYPO3\CMS\Backend\Utility\BackendUtility::$denied = [10,20]; break;
            case 'record': $GLOBALS['BE_USER']->recordAllowed = false; break;
        }
        check($controller->handleRequest(new Request())->data['messages'] === [], 'unauthorized read: ' . $denial);
    }
    foreach (['create','update','toggle','delete'] as $operation) {
        resetState(); $body = form($controller, $operation); $body['pid'] = 999; $body['hidden'] = 1;
        $body['message'] = " New\n message ";
        $response = $controller->handleRequest(new Request('POST', $body));
        check($response->status === 303 && str_contains($response->uri, 'site=home'), 'CRUD redirect missing: ' . $operation);
        [$data,$commands] = DataHandler::$writes[0];
        $expected = match ($operation) {
            'create' => [[NewstickerRepository::TABLE => ['NEWsvbsTicker' => ['pid'=>10,'message'=>'New message','hidden'=>0]]], []],
            'update' => [[NewstickerRepository::TABLE => [1 => ['message'=>'New message']]], []],
            'toggle' => [[NewstickerRepository::TABLE => [1 => ['hidden'=>1]]], []],
            'delete' => [[], [NewstickerRepository::TABLE => [1 => ['delete'=>1]]]],
        };
        check([$data,$commands] === $expected, 'CRUD write escaped whitelist: ' . $operation);
        check(CacheManager::$flushes === [['pages','svbs_newsticker_pid_10']], 'frontend cache not cleared: ' . $operation);
    }
    foreach (['csrf','foreign','deleted','site','missing-site','action-token','revision','empty','long','array','permission','unknown-action'] as $invalid) {
        resetState(); $body = form($controller, 'update');
        switch ($invalid) {
            case 'csrf': $body['formToken'] = 'invalid'; break;
            case 'foreign':
                $body['uid'] = 3;
                $body['formToken'] = (new \TYPO3\CMS\Core\FormProtection\Protection())->generateToken('svbs_newsticker','update','home:10:3');
                break;
            case 'deleted':
                $body['uid'] = 4;
                $body['formToken'] = (new \TYPO3\CMS\Core\FormProtection\Protection())->generateToken('svbs_newsticker','update','home:10:4');
                break;
            case 'site': $body['site'] = 'missing'; break;
            case 'missing-site': unset($body['site']); break;
            case 'action-token': $body['operation'] = 'delete'; break;
            case 'revision': $body['revision'] = 'stale'; break;
            case 'empty': $body['message'] = " \n "; break;
            case 'long': $body['message'] = str_repeat('ü',1001); break;
            case 'array': $body['message'] = []; break;
            case 'permission': $GLOBALS['BE_USER']->tableAllowed = false; break;
            case 'unknown-action': $body['operation'] = 'move'; break;
        }
        $response = $controller->handleRequest(new Request('POST', $body));
        check(DataHandler::$writes === [] && CacheManager::$flushes === [], 'invalid request wrote data: ' . $invalid);
        check($response->data['error'] !== '', 'invalid request missing error: ' . $invalid);
    }
    resetState(); $body = form($controller, 'create'); DataHandler::$fail = true;
    $response = $controller->handleRequest(new Request('POST', $body));
    check($response->data['error'] !== '' && $response->data['newMessage'] === $body['message'], 'failed save lost draft');
    check(CacheManager::$flushes === [], 'failed save reported success');

    resetState(); $processor = new NewstickerProcessor($repository);
    $collector = new \TYPO3\CMS\Core\Cache\CacheDataCollector();
    $request = new Request(attributes: ['site'=>new Site('home',10),'frontend.cache.collector'=>$collector]);
    $processed = $processor->process(new \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer($request),[],[],['data'=>['bodytext'=>'Old text']]);
    check($processed['tickerMessages'] === ['Message 5','Message 1'], 'processor order or legacy/hidden/folder filtering incorrect');
    check(in_array('svbs_newsticker_pid_10',$collector->tags,true), 'processor cache tag missing');
    ConnectionPool::$records = []; $collector->tags = [];
    $processed = $processor->process(new \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer($request),[],[],[]);
    check($processed['tickerMessages'] === [] && in_array('svbs_newsticker_pid_10',$collector->tags,true), 'empty ticker not cache-tagged');
    $request = new Request(attributes: ['site'=>new Site('disabled',0)]);
    $processed = $processor->process(new \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer($request),[],[],['data'=>['bodytext'=>'Old text']]);
    check($processed['tickerMessages'] === [], 'unconfigured ticker used legacy text');
    echo $checks . " standalone checks passed (controlled API doubles; TYPO3 not bootstrapped)\n";
}
