<?php
declare(strict_types=1);

function nav_items(string $perfil): array
{
    if ($perfil === 'admin') {
        return [
            ['/admin/dashboard.php', 'dashboard', 'bi-grid-1x2-fill', 'Dashboard'],
            ['/admin/clientes.php', 'clientes', 'bi-people-fill', 'Clientes'],
            ['/admin/financeiro.php', 'financeiro', 'bi-cash-coin', 'Financeiro'],
        ];
    }
    return [
        ['/cliente/dashboard.php', 'dashboard', 'bi-grid-1x2-fill', 'Dashboard'],
        ['/cliente/dados-pessoais.php', 'dados-pessoais', 'bi-person-vcard-fill', 'Dados pessoais'],
        ['/cliente/rotas.php',     'rotas',     'bi-signpost-split-fill', 'Rotas'],
        ['/cliente/custos.php',    'custos',    'bi-fuel-pump-fill', 'Custos da rota'],
    ];
}

function html_head(string $title): void
{
    ?><!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>DSK - Gestão de Custo | <?= h($title) ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="/assets/css/style.css">
<script>document.documentElement.setAttribute('data-theme', localStorage.getItem('dsk_theme') || 'dark');</script>
</head>
<body>
<?php
}

function theme_switch(bool $inline = false): string
{
    return '<div class="theme-switch"' . ($inline ? ' style="position:static;"' : '') . '>
      <button type="button" data-theme-btn="dark" title="Tema noite"><i class="bi bi-moon-stars-fill"></i></button>
      <button type="button" data-theme-btn="light" title="Tema dia"><i class="bi bi-sun-fill"></i></button>
    </div>';
}

function scripts_base(string $extra = ''): void
{
    ?>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/theme.js"></script>
<script src="/assets/js/app.js"></script>
<?= $extra ?>
</body>
</html>
<?php
}

function app_header(array $user, string $title, string $subtitle, string $active, string $right = ''): void
{
    html_head($title);
    $papel = $user['perfil'] === 'admin' ? 'Administrador' : 'Motorista parceiro';
    ?>
<div class="sidebar-backdrop" id="backdrop"></div>
<div class="app-shell">
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-head">
      <div>
        <p class="brand-title" style="font-size:16px;">DSK</p>
        <div class="brand-sub" style="font-size:10.5px;">Gestão de Custo</div>
      </div>
    </div>
    <nav class="sidebar-nav scrollbar-thin">
      <div class="nav-section-label">Menu</div>
      <?php foreach (nav_items($user['perfil']) as [$href, $key, $icon, $label]): ?>
        <a href="<?= h($href) ?>" class="nav-link <?= $key === $active ? 'active' : '' ?>"><i class="bi <?= h($icon) ?>"></i> <?= h($label) ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="sidebar-foot">
      <div class="user-chip">
        <div class="av"><?= h(mb_strtoupper(mb_substr($user['nome'], 0, 1))) ?></div>
        <div style="min-width:0;">
          <div style="font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= h($user['nome']) ?></div>
          <div style="font-size:11px;color:var(--text-faint);"><?= h($papel) ?></div>
        </div>
        <a href="/logout.php" class="btn btn-sm ml-auto" style="color:var(--text-faint);" title="Sair"><i class="bi bi-box-arrow-right"></i></a>
      </div>
    </div>
  </aside>

  <main class="main-content">
    <div class="topbar">
      <div class="d-flex align-items-center">
        <button type="button" class="btn sidebar-toggle-btn mr-2" id="btnToggleSidebar" aria-label="Menu"><i class="bi bi-list" style="font-size:24px;"></i></button>
        <div>
          <h1 class="page-title"><?= $title ?></h1>
          <div class="page-sub"><?= h($subtitle) ?></div>
        </div>
      </div>
      <div class="d-flex align-items-center" style="gap:10px;">
        <?= $right ?>
        <?= theme_switch(true) ?>
      </div>
    </div>
    <?= flash_render() ?>
    <?php if ($user['perfil'] === 'cliente'): ?>
    <div class="page-shortcuts" aria-label="Navegação da página">
      <a href="/cliente/dashboard.php" class="page-shortcut" title="Início do cliente" aria-label="Início do cliente"><i class="bi bi-house-fill"></i></a>
      <button type="button" class="page-shortcut" data-history-back data-fallback="/cliente/dashboard.php" title="Voltar à página anterior" aria-label="Voltar à página anterior"><i class="bi bi-arrow-left"></i></button>
    </div>
    <?php endif; ?>
<?php
}

function app_footer(string $extraScripts = ''): void
{
    echo "  </main>\n</div>\n";
    scripts_base($extraScripts);
}
