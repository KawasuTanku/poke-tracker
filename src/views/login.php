<?php
declare(strict_types=1);
use function PokeTracker\takeFlash;
use function PokeTracker\e;
/** @var string $title */
/** @var string|null $error */
/** @var bool $setup */
/** @var array $user */
/** @var bool $isAdmin */
/** @var string|null $flash */

$flash = takeFlash();
?>
<div class="login-box">
    <h2><?= $setup ? 'Set Up PokeTracker' : 'Login' ?></h2>
    <?php if ($error): ?>
        <div class="error"><?= e($error) ?></div>
    <?php endif; ?>
    <form method="POST" action="<?= $setup ? '/setup' : '/login' ?>">
        <input type="text" name="user" placeholder="Username" required autofocus>
        <input type="password" name="pass" placeholder="Password" required>
        <button type="submit"><?= $setup ? 'Create Account' : 'Login' ?></button>
    </form>
</div>
