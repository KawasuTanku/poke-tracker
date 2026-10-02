<?php
declare(strict_types=1);

date_default_timezone_set('America/Chicago');

use PokeTracker\App;
use PokeTracker\Auth;
use PokeTracker\Pokedex;
use PokeTracker\Tracker;
use function PokeTracker\e;
use function PokeTracker\csrfToken;
use function PokeTracker\csrfValid;
use function PokeTracker\setFlash;
use function PokeTracker\takeFlash;
use function PokeTracker\securityHeaders;

if (session_status() === PHP_SESSION_NONE) {
    $sessDir = __DIR__ . '/../data/sessions';
    if (!is_dir($sessDir)) {
        @mkdir($sessDir, 0o750, true);
    }
    if (is_dir($sessDir) && is_writable($sessDir)) {
        session_save_path($sessDir);
        ini_set('session.save_handler', 'files');
    }
}

require __DIR__ . '/../vendor/autoload.php';

$app = new App(__DIR__ . '/..');
$GLOBALS['app'] = $app;

$app->seedIfNeeded();

function handle_request(App $app): void
{
    $apiUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    if (str_starts_with($apiUri, '/api/')) {
        $apiMethod = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        handle_api_request($app, $apiUri, $apiMethod);
        return;
    }

    if (session_status() === PHP_SESSION_NONE) {
        $secure = (!empty($_SERVER['HTTPS']) || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    securityHeaders();

    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

    // Public routes
    if ($uri === '/setup' && !$app->auth->isConfigured()) {
        if ($method === 'POST') {
            $user = trim($_POST['user'] ?? '');
            $pass = $_POST['pass'] ?? '';
            if ($user === '' || strlen($pass) < 8) {
                view('login', ['title' => 'Set up', 'error' => 'Username required and password must be at least 8 characters.', 'setup' => true]);
                return;
            }
            $app->auth->createUser($user, $pass, 'admin');
            $app->auth->login($user, $pass);
            $_SESSION['csrf'] = bin2hex(random_bytes(24));
            header('Location: /');
            return;
        }
        view('login', ['title' => 'Set up', 'setup' => true]);
        return;
    }

    if ($uri === '/login') {
        if ($app->auth->check()) { header('Location: /'); return; }
        if ($method === 'POST') {
            $u = trim($_POST['user'] ?? '');
            if ($app->auth->login($u, $_POST['pass'] ?? '')) {
                $_SESSION['csrf'] = bin2hex(random_bytes(24));
                header('Location: /');
                return;
            }
            view('login', ['title' => 'Sign in', 'error' => 'Invalid credentials.', 'setup' => false]);
            return;
        }
        view('login', ['title' => 'Sign in', 'setup' => false]);
        return;
    }

    if ($uri === '/logout' && $method === 'POST') {
        if (csrfValid($_POST['csrf'] ?? null)) {
            $app->auth->logout();
        }
        header('Location: /login');
        return;
    }

    // Auth-required routes
    if (!$app->auth->check()) {
        if ($uri === '/') { header('Location: /login'); return; }
        http_response_code(401);
        view('login', ['title' => 'Sign in', 'setup' => $app->auth->isConfigured() === false]);
        return;
    }

    $user = $app->auth->user();
    $isAdmin = $app->auth->isAdmin($user);

    if ($uri === '/' || $uri === '/dex') {
        $search = trim($_GET['q'] ?? '');
        $gen = $_GET['gen'] ?? '';
        $status = $_GET['status'] ?? '';

        if ($search !== '') {
            $pokemon = $app->pokedex->search($search);
        } elseif ($gen !== '') {
            $pokemon = $app->pokedex->byGeneration($gen);
        } else {
            $pokemon = $app->pokedex->all();
        }

        $trackerData = [];
        foreach ($app->tracker->all() as $t) {
            $trackerData[$t['pokemon_id']] = $t;
        }

        if ($status !== '') {
            $pokemon = array_filter($pokemon, function($p) use ($trackerData, $status) {
                $s = $trackerData[$p['id']]['status'] ?? 'not-found';
                return $s === $status;
            });
        }

        view('dex', [
            'title' => 'Pokédex',
            'user' => $user,
            'isAdmin' => $isAdmin,
            'stats' => $app->tracker->stats(),
            'genStats' => $app->tracker->statsByGeneration(),
            'pokemon' => $pokemon,
            'trackerData' => $trackerData,
            'currentGen' => $gen,
            'search' => $search,
        ]);
        return;
    }

    if (preg_match('#^/pokemon/(\d+)$#', $uri, $m) && $method === 'GET') {
        $pokemon = $app->pokedex->find((int)$m[1]);
        if ($pokemon === null) {
            http_response_code(404);
            view('login', ['title' => 'Not found', 'user' => $user, 'isAdmin' => $isAdmin]);
            return;
        }
        $trackerEntry = $app->tracker->get((int)$m[1]);
        view('detail', [
            'title' => $pokemon['name_display'],
            'user' => $user,
            'isAdmin' => $isAdmin,
            'pokemon' => $pokemon,
            'trackerEntry' => $trackerEntry,
        ]);
        return;
    }

    if (preg_match('#^/pokemon/(\d+)/update$#', $uri, $m) && $method === 'POST') {
        if (csrfValid($_POST['csrf'] ?? null)) {
            $pid = (int)$m[1];
            $status = $_POST['status'] ?? '';
            $notes = trim($_POST['notes'] ?? '') ?: null;
            if (in_array($status, ['caught', 'seen', 'not-found'], true)) {
                $app->tracker->setStatus($pid, $status, $notes);
                setFlash('Updated.');
            }
        }
        header('Location: /pokemon/' . $m[1]);
        return;
    }

    http_response_code(404);
    view('login', ['title' => 'Not found', 'user' => $user, 'isAdmin' => $isAdmin]);
    return;
}

if (function_exists('frankenphp_handle_request')) {
    frankenphp_handle_request(static function () use ($app): void {
        handle_request($app);
    });
} else {
    handle_request($app);
}

function handle_api_request(App $app, string $uri, string $method): void
{
    if ($method !== 'GET') {
        echo json_encode(['error' => 'Method not allowed'], 405);
        return;
    }

    if ($uri === '/api/stats') {
        echo json_encode($app->tracker->stats());
        return;
    }

    if ($uri === '/api/pokemon') {
        echo json_encode($app->pokedex->all());
        return;
    }

    if (preg_match('#^/api/pokemon/(\d+)$#', $uri, $m)) {
        $p = $app->pokedex->find((int)$m[1]);
        if ($p === null) {
            echo json_encode(['error' => 'Not found'], 404);
            return;
        }
        $t = $app->tracker->get((int)$m[1]);
        echo json_encode(['pokemon' => $p, 'tracker' => $t]);
        return;
    }

    echo json_encode(['error' => 'Not found'], 404);
}

function view(string $name, array $vars): void
{
    $vars['user'] ??= null;
    if (!array_key_exists('isAdmin', $vars) && isset($GLOBALS['app']) && $vars['user'] !== null) {
        $vars['isAdmin'] = $GLOBALS['app']->auth->isAdmin($vars['user']);
    }
    $vars['isAdmin'] ??= false;

    ob_start();
    extract($vars, EXTR_SKIP);
    require __DIR__ . '/../src/views/' . $name . '.php';
    $body = ob_get_clean();

    ob_start();
    extract($vars + ['body' => $body], EXTR_SKIP);
    require __DIR__ . '/../src/views/layout.php';
    echo ob_get_clean();
}
