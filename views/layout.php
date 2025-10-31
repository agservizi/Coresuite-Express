<?php
declare(strict_types=1);

/** @var string $content */
$currentUser = $currentUser ?? null;
$pageTitle = $pageTitle ?? 'Gestionale Telefonia';
$userDisplayName = null;
$userInitial = null;
$userRoleLabel = 'Operatore';
$appVersionLabel = 'v. 1.0';
$initialToasts = $initialToasts ?? [];
if (!is_array($initialToasts)) {
    $initialToasts = [];
}

if (is_array($currentUser)) {
    $displayCandidate = (string) ($currentUser['fullname'] ?? '');
    if ($displayCandidate === '') {
        $displayCandidate = (string) ($currentUser['username'] ?? '');
    }
    if ($displayCandidate === '') {
        $displayCandidate = 'Operatore';
    }
    $userDisplayName = $displayCandidate;

    if (function_exists('mb_substr')) {
        $initialChar = (string) mb_substr($userDisplayName, 0, 1, 'UTF-8');
        if (function_exists('mb_strtoupper')) {
            $userInitial = mb_strtoupper($initialChar, 'UTF-8');
        } else {
            $userInitial = strtoupper($initialChar);
        }
    } else {
        $userInitial = strtoupper((string) substr($userDisplayName, 0, 1));
    }

    $roleCandidate = $currentUser['role'] ?? null;
    if (is_string($roleCandidate) && $roleCandidate !== '') {
        $roleLabel = str_replace('_', ' ', strtolower($roleCandidate));
        $userRoleLabel = ucwords($roleLabel);
    }
}

if ($userInitial === null || $userInitial === '') {
    $userInitial = 'C';
}
?>
<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <script defer src="assets/js/app.js"></script>
</head>
<body>
<div class="layout">
    <aside class="sidebar" data-collapsed="false">
        <div class="sidebar__header">
            <span class="sidebar__logo" aria-hidden="true">
                <img src="assets/img/logo-collapsed.svg" alt="">
            </span>
            <span class="sidebar__title">Coresuite Express</span>
        </div>
        <nav class="sidebar__nav">
            <a href="index.php?page=dashboard" class="sidebar__link" data-tooltip="Dashboard">🏠 <span>Dashboard</span></a>
            <a href="index.php?page=sim_stock" class="sidebar__link" data-tooltip="Magazzino SIM">📥 <span>Magazzino SIM</span></a>
            <a href="index.php?page=products" class="sidebar__link" data-tooltip="Prodotti">🛒 <span>Prodotti</span></a>
            <a href="index.php?page=products_list" class="sidebar__link" data-tooltip="Lista Prodotti">📋 <span>Lista prodotti</span></a>
            <a href="index.php?page=customers" class="sidebar__link" data-tooltip="Clienti">👥 <span>Clienti</span></a>
            <a href="index.php?page=offers" class="sidebar__link" data-tooltip="Listini">🗂️ <span>Listini</span></a>
            <a href="index.php?page=sales_create" class="sidebar__link" data-tooltip="Nuova vendita">🧾 <span>Nuova vendita</span></a>
            <a href="index.php?page=sales_list" class="sidebar__link" data-tooltip="Storico vendite">📊 <span>Storico vendite</span></a>
            <a href="index.php?page=product_requests" class="sidebar__link" data-tooltip="Ordini store">📦 <span>Ordini store</span></a>
            <a href="index.php?page=support_requests" class="sidebar__link" data-tooltip="Supporto clienti">💬 <span>Richieste supporto</span></a>
            <a href="index.php?page=reports" class="sidebar__link" data-tooltip="Report vendite">📈 <span>Report</span></a>
            <a href="index.php?page=settings" class="sidebar__link" data-tooltip="Impostazioni">⚙️ <span>Impostazioni</span></a>
        </nav>
    <div class="sidebar__footer">
        <div class="sidebar__user sidebar__user--minimal">
            <span class="sidebar__user-version"><?= htmlspecialchars($appVersionLabel) ?></span>
        </div>
    </div>
    </aside>
    <main class="main">
    <header class="topbar" role="banner">
            <div class="topbar__brand">
                <button class="sidebar__toggle topbar__toggle" type="button" aria-label="Comprimi menu" aria-expanded="true">
                    <span class="sidebar__toggle-icon" aria-hidden="true">
                        <span class="sidebar__chevron sidebar__chevron--primary"></span>
                        <span class="sidebar__chevron sidebar__chevron--secondary"></span>
                    </span>
                </button>
                <div class="topbar__brand-text">
                    <span class="topbar__brand-name">Coresuite Express</span>
                    <span class="topbar__page"><?= htmlspecialchars($pageTitle) ?></span>
                </div>
            </div>
            <div class="topbar__actions">
                <?php if ($currentUser): ?>
                    <a class="topbar__action" href="index.php?page=sim_stock">
                        <span>Magazzino SIM</span>
                    </a>
                    <a class="topbar__action topbar__action--primary" href="index.php?page=sales_create">
                        <span>Nuova vendita</span>
                    </a>
                    <div class="topbar__user">
                        <div class="topbar__user-avatar" aria-hidden="true"><?= htmlspecialchars($userInitial) ?></div>
                        <div class="topbar__user-info">
                                <a class="topbar__user-role" href="index.php?page=profile" title="Apri il profilo utente"><?= htmlspecialchars($userRoleLabel) ?></a>
                            <span class="topbar__user-name"><?= htmlspecialchars($userDisplayName ?? '') ?></span>
                        </div>
                        <a class="topbar__logout" href="index.php?page=logout">
                            Esci
                        </a>
                    </div>
                <?php else: ?>
                    <a class="topbar__action topbar__action--primary" href="index.php?page=login">
                        <span>Accedi</span>
                    </a>
                <?php endif; ?>
            </div>
        </header>
        <div class="main__content">
            <?= $content ?>
        </div>
    </main>
</div>
<?php
$initialToastsPayload = json_encode($initialToasts, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
if ($initialToastsPayload === false) {
    $initialToastsPayload = '[]';
}
?>
<div class="toast-stack" data-toast-stack aria-live="polite" aria-atomic="true"></div>
<script>
    window.AppInitialToasts = <?= $initialToastsPayload ?>;
</script>
</body>
</html>
