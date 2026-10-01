<?php
declare(strict_types=1);
use function PokeTracker\takeFlash;
use function PokeTracker\e;
/** @var array $stats */
/** @var array $genStats */
/** @var array $pokemon */
/** @var array $trackerData */
/** @var string $currentGen */
/** @var string $search */
/** @var array $user */
/** @var bool $isAdmin */
/** @var string|null $flash */

$flash = takeFlash();
$isLoggedIn = $user !== null;
?>
<div class="container">
    <?php if (!$isLoggedIn): ?>
        <div class="view-only-banner">
            View-only mode. <a href="/login">Login</a> to track your dex.
        </div>
    <?php endif; ?>

    <div class="stats-bar">
        <div class="stat-card caught">
            <div class="num"><?= $stats['caught'] ?></div>
            <div class="label">Caught</div>
        </div>
        <div class="stat-card seen">
            <div class="num"><?= $stats['seen'] ?></div>
            <div class="label">Seen</div>
        </div>
        <div class="stat-card total">
            <div class="num"><?= $stats['total'] ?></div>
            <div class="label">Total</div>
        </div>
    </div>

    <div class="progress-bar">
        <div class="progress-fill" style="width: <?= $stats['percent'] ?>%"></div>
    </div>

    <div class="filters">
        <input type="text" id="search" placeholder="Search Pokémon..." value="<?= e($search) ?>" oninput="filterDex()">
        <select id="gen-filter" onchange="filterDex()">
            <option value="">All Gens</option>
            <?php foreach ($genStats as $gs): ?>
                <option value="<?= e($gs['generation']) ?>" <?= $currentGen === $gs['generation'] ? 'selected' : '' ?>>
                    Gen <?= e($gs['generation']) ?> (<?= (int)$gs['caught'] ?>/<?= (int)$gs['total'] ?>)
                </option>
            <?php endforeach; ?>
        </select>
        <select id="status-filter" onchange="filterDex()">
            <option value="">All Status</option>
            <option value="caught">Caught</option>
            <option value="seen">Seen</option>
            <option value="not-found">Not Found</option>
        </select>
    </div>

    <div class="dex-grid" id="dex-grid">
        <?php foreach ($pokemon as $p): ?>
            <?php
            $t = $trackerData[$p['id']] ?? null;
            $status = $t['status'] ?? 'not-found';
            $cellClass = $status === 'caught' ? 'caught' : ($status === 'seen' ? 'seen' : 'not-found');
            ?>
            <div class="dex-cell <?= $cellClass ?>"
                 data-id="<?= $p['id'] ?>"
                 data-name="<?= e(strtolower($p['name'])) ?>"
                 data-gen="<?= e($p['generation']) ?>"
                 data-status="<?= $status ?>"
                 onclick="location.href='/pokemon/<?= $p['id'] ?>'">
                <div class="dex-num">#<?= $p['id'] ?></div>
                <img src="<?= e($p['sprite']) ?>" alt="<?= e($p['name_display']) ?>" loading="lazy">
                <div class="dex-name"><?= e($p['name_display']) ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
function filterDex() {
    const search = document.getElementById('search').value.toLowerCase();
    const gen = document.getElementById('gen-filter').value;
    const status = document.getElementById('status-filter').value;
    const cells = document.querySelectorAll('.dex-cell');
    cells.forEach(cell => {
        const name = cell.dataset.name;
        const cellGen = cell.dataset.gen;
        const cellStatus = cell.dataset.status;
        let show = true;
        if (search && !name.includes(search)) show = false;
        if (gen && cellGen !== gen) show = false;
        if (status && cellStatus !== status) show = false;
        cell.style.display = show ? '' : 'none';
    });
}
</script>
