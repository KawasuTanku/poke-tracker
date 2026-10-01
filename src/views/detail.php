<?php
declare(strict_types=1);
use function PokeTracker\takeFlash;
use function PokeTracker\e;
use function PokeTracker\csrfToken;
/** @var array $pokemon */
/** @var array|null $trackerEntry */
/** @var array $user */
/** @var bool $isAdmin */
/** @var string|null $flash */

$flash = takeFlash();
$isLoggedIn = $user !== null;
$status = $trackerEntry['status'] ?? 'not-found';
$notes = $trackerEntry['notes'] ?? '';
?>
<div class="container">
    <a href="/" class="back-link">&larr; Back to Dex</a>
    <div class="detail-card">
        <div class="dex-num">#<?= $pokemon['id'] ?> &middot; Gen <?= e($pokemon['generation']) ?></div>
        <img src="<?= e($pokemon['sprite']) ?>" alt="<?= e($pokemon['name_display']) ?>">
        <h2><?= e($pokemon['name_display']) ?></h2>

        <?php if ($isLoggedIn): ?>
            <form method="POST" action="/pokemon/<?= $pokemon['id'] ?>/update">
                <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
                <div class="status-buttons">
                    <button type="submit" name="status" value="caught"
                            class="status-btn <?= $status === 'caught' ? 'active-caught' : '' ?>">
                        Caught
                    </button>
                    <button type="submit" name="status" value="seen"
                            class="status-btn <?= $status === 'seen' ? 'active-seen' : '' ?>">
                        Seen
                    </button>
                    <button type="submit" name="status" value="not-found"
                            class="status-btn <?= $status === 'not-found' ? 'active-not-found' : '' ?>">
                        Not Found
                    </button>
                </div>
                <textarea name="notes" class="notes-area" placeholder="Notes (optional)..."><?= e($notes) ?></textarea>
                <button type="submit" class="save-btn">Save</button>
            </form>
        <?php else: ?>
            <div class="view-only-banner" style="margin-top: 16px;">
                <a href="/login">Login</a> to track this Pokémon.
            </div>
        <?php endif; ?>
    </div>
</div>
