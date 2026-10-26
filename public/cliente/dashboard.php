<?php
require __DIR__ . '/../../app/bootstrap.php';
$u   = require_login('cliente');
$cid = (int)$u['cliente_id'];
$pdo = db();

$p = in_array($_GET['p'] ?? '', ['dia', 'mes', 'ano'], true) ? $_GET['p'] : 'mes';
[$ini, $fim] = periodo_range($p);

// receita, km e nº de rotas
$st = $pdo->prepare('SELECT COALESCE(SUM(valor_receber),0) receita,
                            COALESCE(SUM(km * CASE WHEN ida_volta = 1 THEN 2 ELSE 1 END),0) km, COUNT(*) n
                       FROM rotas WHERE cliente_id = ? AND data_rota BETWEEN ? AND ?');
$st->execute([$cid, $ini, $fim]);
$tot = $st->fetch();

// custos lançados nas rotas do período
$st = $pdo->prepare('SELECT COALESCE(SUM(c.valor * CASE WHEN r.ida_volta = 1 THEN 2 ELSE 1 END),0) FROM custos_rota c
                       JOIN rotas r ON r.id = c.rota_id
                      WHERE r.cliente_id = ? AND r.data_rota BETWEEN ? AND ?');
$st->execute([$cid, $ini, $fim]);
$custo = (float)$st->fetchColumn();

$receita = (float)$tot['receita'];
$km      = (float)$tot['km'];
$n       = (int)$tot['n'];
$lucro   = $receita - $custo;

// custos por categoria
$st = $pdo->prepare('SELECT c.categoria, SUM(c.valor * CASE WHEN r.ida_volta = 1 THEN 2 ELSE 1 END) total FROM custos_rota c
                       JOIN rotas r ON r.id = c.rota_id
                      WHERE r.cliente_id = ? AND r.data_rota BETWEEN ? AND ?
                      GROUP BY c.categoria ORDER BY total DESC');
$st->execute([$cid, $ini, $fim]);
$cats = $st->fetchAll();

// últimas rotas do período
$st = $pdo->prepare('SELECT r.*, COALESCE((SELECT SUM(c.valor * CASE WHEN r.ida_volta = 1 THEN 2 ELSE 1 END)
                                                FROM custos_rota c WHERE c.rota_id = r.id),0) custo
                       FROM rotas r WHERE r.cliente_id = ? AND r.data_rota BETWEEN ? AND ?
                      ORDER BY r.data_rota DESC, r.id DESC LIMIT 8');
$st->execute([$cid, $ini, $fim]);
$rotas = $st->fetchAll();

$right = '<form method="get"><select name="p" class="custom-select" style="height:42px;" onchange="this.form.submit()">';
foreach (['dia' => 'Hoje', 'mes' => 'Este mês', 'ano' => 'Este ano'] as $k => $l) {
    $right .= '<option value="' . $k . '"' . ($k === $p ? ' selected' : '') . '>' . $l . '</option>';
}
$right .= '</select></form>';

app_header($u, 'Olá, ' . h(explode(' ', $u['nome'])[0]), 'Resumo das suas rotas e custos · ' . periodo_label($p), 'dashboard', $right);
$cores = ['--accent', '--accent-2', '--success', '--warning', '--danger', '--info'];
?>
<div class="row">
  <div class="col-6 col-lg-3 mb-3">
    <div class="dsk-card kpi-card">
      <div class="kpi-icon" style="color:var(--success);background:rgba(95,214,153,.14);"><i class="bi bi-cash-coin"></i></div>
      <p class="kpi-value"><?= brl($receita) ?></p>
      <div class="kpi-label">Receita das rotas</div>
    </div>
  </div>
  <div class="col-6 col-lg-3 mb-3">
    <div class="dsk-card kpi-card">
      <div class="kpi-icon" style="color:var(--danger);background:rgba(239,117,117,.14);"><i class="bi bi-graph-down-arrow"></i></div>
      <p class="kpi-value"><?= brl($custo) ?></p>
      <div class="kpi-label">Custos das rotas</div>
    </div>
  </div>
  <div class="col-6 col-lg-3 mb-3">
    <div class="dsk-card kpi-card">
      <div class="kpi-icon"><i class="bi bi-piggy-bank-fill"></i></div>
      <p class="kpi-value" style="color:var(--<?= $lucro >= 0 ? 'success' : 'danger' ?>);"><?= brl($lucro) ?></p>
      <div class="kpi-label">Lucro líquido</div>
    </div>
  </div>
  <div class="col-6 col-lg-3 mb-3">
    <div class="dsk-card kpi-card">
      <div class="kpi-icon" style="color:var(--info);background:rgba(111,179,232,.14);"><i class="bi bi-signpost-split-fill"></i></div>
      <p class="kpi-value"><?= number_format($km, 1, ',', '.') ?> km</p>
      <div class="kpi-label"><?= $n ?> rota(s) no período</div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-lg-4 mb-3">
    <div class="dsk-card h-100">
      <div class="section-title"><i class="bi bi-calculator-fill"></i> Médias</div>
      <table class="table-dsk"><tbody>
        <tr><td>Receita por km</td><td class="text-right font-mono"><?= brl($km > 0 ? $receita / $km : 0) ?></td></tr>
        <tr><td>Custo por km</td><td class="text-right font-mono"><?= brl($km > 0 ? $custo / $km : 0) ?></td></tr>
        <tr><td>Lucro por rota</td><td class="text-right font-mono"><?= brl($n > 0 ? $lucro / $n : 0) ?></td></tr>
        <tr><td>Margem</td><td class="text-right font-mono"><?= number_format($receita > 0 ? $lucro / $receita * 100 : 0, 1, ',', '.') ?>%</td></tr>
      </tbody></table>
    </div>
  </div>

  <div class="col-lg-8 mb-3">
    <div class="dsk-card h-100">
      <div class="section-title"><i class="bi bi-pie-chart-fill"></i> Custos por categoria</div>
      <?php if (!$cats): ?>
        <div class="empty-state"><i class="bi bi-pie-chart"></i>Nenhum custo lançado no período.</div>
      <?php else:
        $soma = array_sum(array_column($cats, 'total')) ?: 1;
        foreach ($cats as $i => $c): $pct = round($c['total'] / $soma * 100); ?>
        <div class="mb-2">
          <div class="d-flex justify-content-between" style="font-size:12.5px;"><span><?= h($c['categoria']) ?></span><span class="font-mono"><?= brl($c['total']) ?> · <?= $pct ?>%</span></div>
          <div class="progress"><div class="progress-bar" style="width:<?= $pct ?>%;background:var(<?= $cores[$i % 6] ?>);"></div></div>
        </div>
      <?php endforeach; endif; ?>
    </div>
  </div>
</div>

<div class="dsk-card">
  <div class="section-title"><i class="bi bi-map-fill"></i> Últimas rotas do período</div>
  <div class="table-responsive">
    <table class="table-dsk">
      <thead><tr><th>Data</th><th>Rota</th><th>Km</th><th>Receita</th><th>Custo</th><th>Lucro</th></tr></thead>
      <tbody>
      <?php foreach ($rotas as $r): $l = $r['valor_receber'] - $r['custo']; ?>
        <tr data-href="/cliente/custos.php?rota_id=<?= (int)$r['id'] ?>">
          <td class="font-mono"><?= date_br($r['data_rota']) ?></td>
          <td><?= h($r['origem']) ?> <i class="bi bi-arrow-right text-faint"></i> <?= h($r['destino']) ?></td>
          <td class="font-mono"><?= number_format($r['km'] * ($r['ida_volta'] ? 2 : 1), 1, ',', '.') ?></td>
          <td class="font-mono"><?= brl($r['valor_receber']) ?></td>
          <td class="font-mono"><?= brl($r['custo']) ?></td>
          <td class="font-mono" style="color:var(--<?= $l >= 0 ? 'success' : 'danger' ?>);"><?= brl($l) ?></td>
        </tr>
      <?php endforeach; if (!$rotas): ?>
        <tr><td colspan="6"><div class="empty-state"><i class="bi bi-signpost"></i>Nenhuma rota no período.</div></td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php app_footer();
