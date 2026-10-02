<?php
declare(strict_types=1);
use function PokeTracker\e;
/** @var string $title */
/** @var string $body */
/** @var array $user */
/** @var bool $isAdmin */
/** @var string|null $flash */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#1a1a2e">
    <link rel="manifest" href="/manifest.json">
    <title><?= e($title) ?> — PokeTracker</title>
    <style>
        :root {
            --bg: #1a1a2e;
            --bg2: #16213e;
            --card: #0f3460;
            --accent: #e63946;
            --accent2: #f4a261;
            --text: #eee;
            --text2: #aaa;
            --caught: #2ecc71;
            --seen: #f1c40f;
            --not-found: #555;
            --radius: 12px;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            padding-bottom: env(safe-area-inset-bottom, 20px);
        }
        header {
            background: var(--bg2);
            padding: 12px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }
        header h1 { font-size: 1.2rem; color: var(--accent); }
        header .user-info { font-size: 0.85rem; color: var(--text2); }
        header .user-info a { color: var(--accent); text-decoration: none; }
        .flash {
            background: var(--accent);
            color: #fff;
            padding: 10px 16px;
            text-align: center;
            font-size: 0.9rem;
        }
        .container { max-width: 800px; margin: 0 auto; padding: 16px; }
        .stats-bar {
            display: flex;
            gap: 12px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }
        .stat-card {
            background: var(--card);
            border-radius: var(--radius);
            padding: 12px 16px;
            flex: 1;
            min-width: 100px;
            text-align: center;
        }
        .stat-card .num { font-size: 1.5rem; font-weight: bold; }
        .stat-card .label { font-size: 0.75rem; color: var(--text2); text-transform: uppercase; }
        .stat-card.caught .num { color: var(--caught); }
        .stat-card.seen .num { color: var(--seen); }
        .stat-card.total .num { color: var(--accent); }
        .progress-bar {
            background: var(--bg2);
            border-radius: 8px;
            height: 8px;
            margin-bottom: 16px;
            overflow: hidden;
        }
        .progress-fill {
            background: linear-gradient(90deg, var(--accent), var(--accent2));
            height: 100%;
            border-radius: 8px;
            transition: width 0.3s;
        }
        .filters {
            display: flex;
            gap: 8px;
            margin-bottom: 16px;
            flex-wrap: wrap;
            align-items: center;
        }
        .filters input, .filters select {
            background: var(--bg2);
            border: 1px solid #333;
            color: var(--text);
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 0.9rem;
        }
        .filters input { flex: 1; min-width: 150px; }
        .filters select { min-width: 100px; }
        .dex-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
            gap: 8px;
        }
        .dex-cell {
            background: var(--card);
            border-radius: var(--radius);
            padding: 8px 4px;
            text-align: center;
            cursor: pointer;
            transition: transform 0.15s, box-shadow 0.15s;
            border: 2px solid transparent;
        }
        .dex-cell:active { transform: scale(0.95); }
        .dex-cell img { width: 48px; height: 48px; image-rendering: pixelated; }
        .dex-cell .dex-num { font-size: 0.65rem; color: var(--text2); }
        .dex-cell .dex-name { font-size: 0.6rem; color: var(--text2); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .dex-cell.caught { border-color: var(--caught); background: rgba(46, 204, 113, 0.15); }
        .dex-cell.seen { border-color: var(--seen); background: rgba(241, 196, 15, 0.15); }
        .dex-cell.not-found { opacity: 0.5; }
        .gen-tabs {
            display: flex;
            gap: 4px;
            margin-bottom: 12px;
            overflow-x: auto;
            padding-bottom: 4px;
        }
        .gen-tab {
            background: var(--bg2);
            border: 1px solid #333;
            color: var(--text2);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            white-space: nowrap;
            cursor: pointer;
        }
        .gen-tab.active { background: var(--accent); color: #fff; border-color: var(--accent); }
        .gen-tab .gen-count { font-size: 0.7rem; opacity: 0.7; }
        /* Detail view */
        .detail-card {
            background: var(--card);
            border-radius: var(--radius);
            padding: 24px;
            text-align: center;
        }
        .detail-card img { width: 128px; height: 128px; image-rendering: pixelated; }
        .detail-card h2 { font-size: 1.5rem; margin: 8px 0; }
        .detail-card .dex-num { color: var(--text2); font-size: 0.9rem; }
        .status-buttons {
            display: flex;
            gap: 8px;
            margin-top: 16px;
            justify-content: center;
        }
        .status-btn {
            padding: 10px 20px;
            border-radius: 8px;
            border: 2px solid #333;
            background: var(--bg2);
            color: var(--text);
            font-size: 0.9rem;
            cursor: pointer;
        }
        .status-btn.active-caught { background: var(--caught); border-color: var(--caught); color: #fff; }
        .status-btn.active-seen { background: var(--seen); border-color: var(--seen); color: #000; }
        .status-btn.active-not-found { background: var(--not-found); border-color: var(--not-found); color: #fff; }
        .notes-area {
            width: 100%;
            background: var(--bg2);
            border: 1px solid #333;
            color: var(--text);
            padding: 10px;
            border-radius: 8px;
            margin-top: 12px;
            font-size: 0.9rem;
            min-height: 60px;
            resize: vertical;
        }
        .save-btn {
            background: var(--accent);
            color: #fff;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            margin-top: 12px;
            width: 100%;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 12px;
            color: var(--accent);
            text-decoration: none;
            font-size: 0.9rem;
        }
        /* Login */
        .login-box {
            max-width: 360px;
            margin: 60px auto;
            background: var(--card);
            border-radius: var(--radius);
            padding: 32px 24px;
        }
        .login-box h2 { text-align: center; margin-bottom: 20px; color: var(--accent); }
        .login-box input {
            width: 100%;
            background: var(--bg2);
            border: 1px solid #333;
            color: var(--text);
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 12px;
            font-size: 1rem;
        }
        .login-box button {
            width: 100%;
            background: var(--accent);
            color: #fff;
            border: none;
            padding: 12px;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
        }
        .login-box .error { color: var(--accent); font-size: 0.85rem; margin-bottom: 12px; text-align: center; }
        .view-only-banner {
            background: var(--accent2);
            color: #000;
            padding: 8px 16px;
            text-align: center;
            font-size: 0.85rem;
        }
        .view-only-banner a { color: #000; font-weight: bold; }
        @media (max-width: 480px) {
            .dex-grid { grid-template-columns: repeat(auto-fill, minmax(70px, 1fr)); gap: 6px; }
            .dex-cell img { width: 40px; height: 40px; }
        }
    </style>
</head>
<body>
    <?php if ($flash): ?>
        <div class="flash"><?= e($flash) ?></div>
    <?php endif; ?>
    <header>
        <h1>PokeTracker</h1>
        <?php if ($user): ?>
            <div class="user-info">
                <?= e($user['username']) ?>
                <?php if ($isAdmin): ?> (admin)<?php endif; ?>
                | <form method="POST" action="/logout" style="display:inline">
                    <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
                    <button type="submit" style="background:none;border:none;color:var(--accent);cursor:pointer;font-size:inherit;padding:0">Logout</button>
                </form>
            </div>
        <?php else: ?>
            <div class="user-info"><a href="/login">Login</a></div>
        <?php endif; ?>
    </header>
    <?= $body ?>
</body>
</html>
