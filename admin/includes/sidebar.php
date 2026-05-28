<?php
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$company = getCompanySettings();

$navItems = [
    ['icon' => 'bi-grid-1x2', 'label' => 'Dashboard', 'href' => 'pages/dashboard.php', 'page' => 'dashboard'],
    ['icon' => 'bi-people', 'label' => 'Clients', 'href' => 'pages/clients.php', 'page' => 'clients'],
    ['icon' => 'bi-briefcase', 'label' => 'Cases', 'href' => 'pages/cases.php', 'page' => 'cases'],
    ['icon' => 'bi-credit-card', 'label' => 'Payments', 'href' => 'pages/payments.php', 'page' => 'payments'],
    ['icon' => 'bi-calendar3', 'label' => 'Appointments', 'href' => 'pages/appointments.php', 'page' => 'appointments'],
    ['icon' => 'bi-robot', 'label' => 'AI Assistant', 'href' => 'pages/chatbot.php', 'page' => 'chatbot'],
];
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-brand">
            <div class="brand-icon">
                <i class="bi bi-shield-check"></i>
            </div>
            <div class="brand-text">
                <span class="brand-name"><?= e($company['company_name']) ?></span>
                <span class="brand-tag">Admin</span>
            </div>
        </div>
        <button type="button" class="sidebar-collapse-btn d-none d-lg-flex" id="sidebarCollapse" aria-label="Collapse sidebar">
            <i class="bi bi-chevron-left"></i>
        </button>
    </div>

    <nav class="sidebar-nav">
        <ul class="nav-list">
            <?php foreach ($navItems as $item): ?>
                <li class="nav-item">
                    <a href="<?= url($item['href']) ?>"
                       class="nav-link <?= $currentPage === $item['page'] ? 'active' : '' ?>"
                       title="<?= e($item['label']) ?>">
                        <i class="bi <?= e($item['icon']) ?>"></i>
                        <span class="nav-label"><?= e($item['label']) ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>

    <div class="sidebar-footer">
        <a href="<?= url('auth/logout.php') ?>" class="sidebar-logout" title="Sign Out">
            <i class="bi bi-box-arrow-right"></i>
            <span class="nav-label">Sign Out</span>
        </a>
    </div>
</aside>
