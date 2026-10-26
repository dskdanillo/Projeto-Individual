<?php
require __DIR__ . '/../../app/bootstrap.php';
require_once APP_PATH . '/lib/admin.php';
$u    = require_login('admin');
$pdo  = db();

/** Atalho: executa uma consulta e devolve o primeiro valor. */
$val = function (string $sql, array $p = []) use ($pdo) {
    $st = $pdo->prepare($sql);
    $st->execute($p);
    return $st->fetchColumn();
};

$total    = (int)$val("SELECT COUNT(*) FROM usuarios WHERE perfil = 'cliente'");
$ativos   = (int)$val("SELECT COUNT(*) FROM usuarios WHERE perfil = 'cliente' AND status = 'ativo'");
$inadimp  = (int)$val("SELECT COUNT(*) FROM usuarios WHERE perfil = 'cliente' AND status = 'inadimplente'");
$inativos = (int)$val("SELECT COUNT(*) FROM usuarios WHERE perfil = 'cliente' AND status = 'inativo'");
$novosMes = (int)$val("SELECT COUNT(*) FROM usuarios WHERE perfil = 'cliente' AND criado_em >= ?", [date('Y-m-01') . ' 00:00:00']);
$rotas    = (int)$val('SELECT COUNT(*) FROM rotas');

$ultimos = $pdo->query("SELECT u.id, u.nome, u.status, u.criado_em, c.cidade
                          FROM usuarios u LEFT JOIN clientes c ON c.usuario_id = u.id
                         WHERE u.perfil = 'cliente' ORDER BY u.criado_em DESC, u.id DESC LIMIT 6")->fetchAll();

app_header($u, 'Painel administrativo', 'Visão geral dos clientes da plataforma', 'dashboard');
?>
<div class="row">
  <?php foreach ([
      ['',             'bi-people-fill',                'Clientes cadastrados',     $total,    ''],
      ['ativo',        'bi-person-check-fill',          'Contas ativas',            $ativos,   'color:var(--success);background:rgba(95,214,153,.14);'],
      ['inadimplente', 'bi-exclamation-triangle-fill',  'Inadimplentes (offline)',  $inadimp,  'color:var(--warning);background:rgba(232,184,95,.16);'],
      ['inativo',      'bi-person-x-fill',              'Contas inativas',          $inativos, 'color:var(--danger);background:rgba(239,117,117,.14);'],
  ] as [$filtro, $ic, $lb, $vl, $estilo]): ?>
  <div class="col-6 col-lg-3 mb-3">
    <a class="kpi-link" href="/admin/clientes.php<?= $filtro ? '?status=' . h($filtro) : '' ?>">
      <div class="dsk-card kpi-card">
        <div class="kpi-icon" <?= $estilo ? 'style="' . $estilo . '"' : '' ?>><i class="bi <?= $ic ?>"></i></div>
        <p class="kpi-value"><?= $vl ?></p>
        <div class="kpi-label"><?= h($lb) ?></div>
      </div>
    </a>
  </div>
  <?php endforeach; ?>
</div>

<div class="row">
  <div class="col-6 col-md-6 mb-3"><div class="dsk-card kpi-card">
    <div class="kpi-icon" style="color:var(--info);background:rgba(111,179,232,.14);"><i class="bi bi-person-plus-fill"></i></div>
    <p class="kpi-value"><?= $novosMes ?></p><div class="kpi-label">Novos clientes em <?= h(date('m/Y')) ?></div>
  </div></div>
  <div class="col-6 col-md-6 mb-3"><div class="dsk-card kpi-card">
    <div class="kpi-icon"><i class="bi bi-signpost-split-fill"></i></div>
    <p class="kpi-value"><?= $rotas ?></p><div class="kpi-label">Rotas cadastradas na plataforma</div>
  </div></div>
</div>

<div class="dsk-card">
  <div class="section-title"><i class="bi bi-clock-history"></i> Últimos cadastros</div>
  <div class="table-responsive">
    <table class="table-dsk compact">
      <thead><tr><th>Cliente</th><th>Cidade</th><th>Cadastro</th><th>Conta</th></tr></thead>
      <tbody>
      <?php foreach ($ultimos as $c): ?>
        <tr data-href="/admin/clientes.php?editar=<?= (int)$c['id'] ?>">
          <td><a class="name-link" href="/admin/clientes.php?editar=<?= (int)$c['id'] ?>"><?= h($c['nome']) ?></a></td>
          <td class="text-dim"><?= h($c['cidade']) ?></td>
          <td class="font-mono"><?= h(date_br($c['criado_em'])) ?></td>
          <td><?= badge_conta($c['status']) ?></td>
        </tr>
      <?php endforeach; if (!$ultimos): ?>
        <tr><td colspan="4"><div class="empty-state"><i class="bi bi-people"></i>Nenhum cliente cadastrado.</div></td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php app_footer();
