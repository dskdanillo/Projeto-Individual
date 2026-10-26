<?php
require __DIR__ . '/../../app/bootstrap.php';
require_once APP_PATH . '/lib/admin.php';
$u    = require_login('admin');
$pdo  = db();
$hoje = date('Y-m-d');

/* volta para a mesma tela, mantendo os filtros (só aceita as chaves conhecidas) */
function voltar_financeiro(): never
{
    parse_str((string)($_POST['qs'] ?? ''), $in);
    $qs = array_intersect_key($in, array_flip(['mes', 'situacao', 'cliente']));
    redirect('/admin/financeiro.php' . ($qs ? '?' . http_build_query($qs) : ''));
}

/* ============================================================
   AÇÕES (POST)
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $acao = (string)($_POST['acao'] ?? '');

    // ---- GERAR mensalidades do mês para todos os clientes ----
    if ($acao === 'gerar') {
        $mes = (string)($_POST['mes'] ?? '');
        if (!mes_valido($mes)) {
            flash('danger', 'Escolha um mês válido.');
        } else {
            $st = $pdo->prepare("INSERT IGNORE INTO mensalidades (cliente_id, referencia, valor, vencimento)
                                 SELECT c.id, CAST(? AS DATE), c.valor_mensalidade,
                                        DATE_ADD(CAST(? AS DATE), INTERVAL LEAST(c.dia_vencimento, DAY(LAST_DAY(CAST(? AS DATE)))) - 1 DAY)
                                   FROM clientes c JOIN usuarios u ON u.id = c.usuario_id
                                  WHERE u.perfil = 'cliente' AND u.status IN ('ativo','inadimplente') AND c.valor_mensalidade > 0");
            $ref = $mes . '-01';
            $st->execute([$ref, $ref, $ref]);
            $n = $st->rowCount();
            flash($n ? 'success' : 'warning', $n
                ? "$n mensalidade(s) de " . mes_br($ref) . ' gerada(s).'
                : 'Nenhuma mensalidade nova: os clientes já têm cobrança em ' . mes_br($ref) . '.');
        }

    // ---- NOVA cobrança avulsa ----
    } elseif ($acao === 'criar') {
        $cid  = (int)($_POST['cliente_id'] ?? 0);
        $mes  = (string)($_POST['mes'] ?? '');
        $valor = num_in((string)($_POST['valor'] ?? ''));
        $venc = (string)($_POST['vencimento'] ?? '');
        $ok = $pdo->prepare('SELECT 1 FROM clientes WHERE id = ?');
        $ok->execute([$cid]);
        if (!$ok->fetch())                  flash('danger', 'Escolha um cliente.');
        elseif (!mes_valido($mes))          flash('danger', 'Escolha o mês de referência.');
        elseif ($valor <= 0 || $valor > 99999) flash('danger', 'Informe um valor válido.');
        elseif (!data_valida($venc))        flash('danger', 'Informe a data de vencimento.');
        else {
            try {
                $pdo->prepare('INSERT INTO mensalidades (cliente_id, referencia, valor, vencimento) VALUES (?,?,?,?)')
                    ->execute([$cid, $mes . '-01', $valor, $venc]);
                flash('success', 'Cobrança lançada.');
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    flash('danger', 'Este cliente já tem cobrança para ' . mes_br($mes . '-01') . '.');
                } else {
                    throw $e;
                }
            }
        }

    // ---- DAR BAIXA (registrar pagamento) e, se quiser, reativar a conta ----
    } elseif ($acao === 'baixar') {
        $id    = (int)($_POST['id'] ?? 0);
        $data  = (string)($_POST['pago_em'] ?? '');
        $forma = (string)($_POST['forma'] ?? '');
        if (!data_valida($data)) {
            $data = $hoje;
        }
        if (!in_array($forma, FORMAS_PAGAMENTO, true)) {
            $forma = FORMAS_PAGAMENTO[0];
        }
        $st = $pdo->prepare('UPDATE mensalidades SET pago_em = ?, forma_pagamento = ? WHERE id = ? AND pago_em IS NULL');
        $st->execute([$data, $forma, $id]);

        if (!$st->rowCount()) {
            flash('danger', 'Cobrança não encontrada ou já paga.');
        } else {
            flash('success', 'Pagamento registrado.');
            if (!empty($_POST['reativar'])) {
                $q = $pdo->prepare('SELECT u.id, u.nome, u.status, c.id AS cid
                                      FROM mensalidades m JOIN clientes c ON c.id = m.cliente_id JOIN usuarios u ON u.id = c.usuario_id
                                     WHERE m.id = ?');
                $q->execute([$id]);
                $cli = $q->fetch();
                if ($cli && $cli['status'] === 'inadimplente') {
                    $r = $pdo->prepare('SELECT COUNT(*) FROM mensalidades WHERE cliente_id = ? AND pago_em IS NULL AND vencimento < ?');
                    $r->execute([$cli['cid'], $hoje]);
                    $restam = (int)$r->fetchColumn();
                    if ($restam === 0) {
                        $pdo->prepare("UPDATE usuarios SET status = 'ativo', status_motivo = NULL, status_alterado_em = ? WHERE id = ? AND perfil = 'cliente'")
                            ->execute([date('Y-m-d H:i:s'), $cli['id']]);
                        flash('success', 'Conta de ' . $cli['nome'] . ' reativada: não há mais cobranças vencidas.');
                    } else {
                        flash('warning', $cli['nome'] . ' ainda tem ' . $restam . ' cobrança(s) vencida(s). A conta continua offline.');
                    }
                }
            }
        }

    // ---- DESFAZER pagamento ----
    } elseif ($acao === 'estornar') {
        $pdo->prepare('UPDATE mensalidades SET pago_em = NULL, forma_pagamento = NULL WHERE id = ?')->execute([(int)($_POST['id'] ?? 0)]);
        flash('success', 'Pagamento desfeito: a cobrança voltou a ficar em aberto.');

    // ---- EXCLUIR cobrança ----
    } elseif ($acao === 'excluir') {
        $pdo->prepare('DELETE FROM mensalidades WHERE id = ?')->execute([(int)($_POST['id'] ?? 0)]);
        flash('success', 'Cobrança excluída.');
    }
    voltar_financeiro();
}

/* ============================================================
   FILTROS (GET)
   ============================================================ */
$mes = array_key_exists('mes', $_GET) ? (string)$_GET['mes'] : date('Y-m');   // vazio = todos os meses
if ($mes !== '' && !mes_valido($mes)) {
    $mes = date('Y-m');
}
$sit      = in_array($_GET['situacao'] ?? '', ['paga', 'pendente', 'atrasada'], true) ? $_GET['situacao'] : '';
$clienteF = (int)($_GET['cliente'] ?? 0);

$whereBase = ['1 = 1'];
$pBase     = [];
if ($mes !== '')    { $whereBase[] = 'm.referencia = ?'; $pBase[] = $mes . '-01'; }
if ($clienteF > 0)  { $whereBase[] = 'm.cliente_id = ?'; $pBase[] = $clienteF; }

/* KPIs (respeitam mês e cliente, não a situação) */
$st = $pdo->prepare('SELECT COALESCE(SUM(m.valor),0) previsto,
                            COALESCE(SUM(CASE WHEN m.pago_em IS NOT NULL THEN m.valor END),0) recebido,
                            COALESCE(SUM(CASE WHEN m.pago_em IS NULL AND m.vencimento >= ? THEN m.valor END),0) a_vencer,
                            COALESCE(SUM(CASE WHEN m.pago_em IS NULL AND m.vencimento <  ? THEN m.valor END),0) atraso
                       FROM mensalidades m WHERE ' . implode(' AND ', $whereBase));
$st->execute(array_merge([$hoje, $hoje], $pBase));
$k = $st->fetch();

/* lista (respeita também a situação) */
$where = $whereBase;
$p     = $pBase;
if ($sit === 'paga')     { $where[] = 'm.pago_em IS NOT NULL'; }
if ($sit === 'pendente') { $where[] = 'm.pago_em IS NULL AND m.vencimento >= ?'; $p[] = $hoje; }
if ($sit === 'atrasada') { $where[] = 'm.pago_em IS NULL AND m.vencimento < ?';  $p[] = $hoje; }
$st = $pdo->prepare('SELECT m.*, u.id AS usuario_id, u.nome, u.status
                       FROM mensalidades m JOIN clientes c ON c.id = m.cliente_id JOIN usuarios u ON u.id = c.usuario_id
                      WHERE ' . implode(' AND ', $where) . '
                      ORDER BY (m.pago_em IS NULL) DESC, m.vencimento ASC, u.nome LIMIT 300');
$st->execute($p);
$lista = $st->fetchAll();

/* contas offline por inadimplência */
$offline = $pdo->query("SELECT u.id, u.nome, u.status_motivo, u.status_alterado_em,
                               (SELECT COALESCE(SUM(m.valor),0) FROM mensalidades m WHERE m.cliente_id = c.id AND m.pago_em IS NULL) AS devendo,
                               (SELECT COUNT(*) FROM mensalidades m WHERE m.cliente_id = c.id AND m.pago_em IS NULL) AS qtd
                          FROM usuarios u JOIN clientes c ON c.usuario_id = u.id
                         WHERE u.perfil = 'cliente' AND u.status = 'inadimplente' ORDER BY u.nome")->fetchAll();

/* últimos 6 meses */
$st = $pdo->prepare('SELECT referencia, SUM(valor) previsto, SUM(CASE WHEN pago_em IS NOT NULL THEN valor ELSE 0 END) recebido
                       FROM mensalidades WHERE referencia >= ? GROUP BY referencia ORDER BY referencia DESC');
$st->execute([date('Y-m-01', strtotime('first day of -5 months'))]);
$meses = $st->fetchAll();

$clientes = $pdo->query("SELECT c.id, u.nome FROM clientes c JOIN usuarios u ON u.id = c.usuario_id WHERE u.perfil = 'cliente' ORDER BY u.nome")->fetchAll();

$qs = $_SERVER['QUERY_STRING'] ?? '';
$topo = '<button type="button" class="btn btn-outline-dsk px-3" style="height:42px;" data-toggle="modal" data-target="#modalGerar"><i class="bi bi-magic"></i> Gerar mensalidades</button>
         <button type="button" class="btn btn-accent px-3" style="height:42px;" data-toggle="modal" data-target="#modalNova"><i class="bi bi-plus-circle-fill"></i> Nova cobrança</button>';
app_header($u, 'Financeiro', 'Mensalidades, pagamentos e inadimplência dos clientes', 'financeiro', $topo);
?>
<div class="row">
  <?php foreach ([
      ['bi-receipt',               'Previsto',   $k['previsto'], '',                                                   ''],
      ['bi-check2-circle',         'Recebido',   $k['recebido'], 'color:var(--success);background:rgba(95,214,153,.14);', 'color:var(--success);'],
      ['bi-hourglass-split',       'A vencer',   $k['a_vencer'], 'color:var(--info);background:rgba(111,179,232,.14);',    ''],
      ['bi-exclamation-triangle-fill', 'Em atraso', $k['atraso'], 'color:var(--danger);background:rgba(239,117,117,.14);',  $k['atraso'] > 0 ? 'color:var(--danger);' : ''],
  ] as [$ic, $lb, $vl, $est, $estVal]): ?>
  <div class="col-6 col-lg-3 mb-3"><div class="dsk-card kpi-card">
    <div class="kpi-icon" <?= $est ? 'style="' . $est . '"' : '' ?>><i class="bi <?= $ic ?>"></i></div>
    <p class="kpi-value" <?= $estVal ? 'style="' . $estVal . '"' : '' ?>><?= brl($vl) ?></p>
    <div class="kpi-label"><?= $lb ?> · <?= $mes !== '' ? h(mes_br($mes . '-01')) : 'todos os meses' ?></div>
  </div></div>
  <?php endforeach; ?>
</div>

<div class="dsk-card mb-3">
  <form method="get" class="filter-bar">
    <div class="form-row align-items-end">
      <div class="form-group col-6 col-md-3">
        <label class="form-label" for="fMes">Mês de referência</label>
        <input type="month" class="form-control" id="fMes" name="mes" value="<?= h($mes) ?>">
        <div class="hint">Vazio = todos os meses</div>
      </div>
      <div class="form-group col-6 col-md-3">
        <label class="form-label" for="fSit">Situação</label>
        <select class="custom-select" id="fSit" name="situacao">
          <option value="">Todas</option>
          <option value="atrasada" <?= $sit === 'atrasada' ? 'selected' : '' ?>>Em atraso</option>
          <option value="pendente" <?= $sit === 'pendente' ? 'selected' : '' ?>>A vencer</option>
          <option value="paga"     <?= $sit === 'paga' ? 'selected' : '' ?>>Pagas</option>
        </select>
      </div>
      <div class="form-group col-md-4">
        <label class="form-label" for="fCli">Cliente</label>
        <select class="custom-select" id="fCli" name="cliente">
          <option value="">Todos</option>
          <?php foreach ($clientes as $c): ?><option value="<?= (int)$c['id'] ?>" <?= $clienteF === (int)$c['id'] ? 'selected' : '' ?>><?= h($c['nome']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="form-group col-md-2 d-flex" style="gap:8px;">
        <button class="btn btn-accent flex-fill" style="height:42px;"><i class="bi bi-funnel-fill"></i> Filtrar</button>
        <a href="/admin/financeiro.php" class="btn btn-outline-dsk" style="height:42px;padding-top:.6rem;" title="Voltar ao mês atual"><i class="bi bi-x-lg"></i></a>
      </div>
    </div>
  </form>
</div>

<div class="dsk-card mb-3">
  <div class="section-title"><i class="bi bi-list-check"></i> Cobranças (<?= count($lista) ?>)</div>
  <div class="table-responsive">
    <table class="table-dsk">
      <thead><tr><th>Cliente</th><th>Referência</th><th>Vencimento</th><th>Valor</th><th>Situação</th><th>Pagamento</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($lista as $m): $s = situacao_mensalidade($m, $hoje); ?>
        <tr>
          <td style="min-width:160px;">
            <a class="name-link" href="/admin/clientes.php?editar=<?= (int)$m['usuario_id'] ?>"><?= h($m['nome']) ?></a>
            <?php if ($m['status'] !== 'ativo'): ?><div><?= badge_conta($m['status']) ?></div><?php endif; ?>
          </td>
          <td class="font-mono"><?= h(mes_br($m['referencia'])) ?></td>
          <td class="font-mono"><?= h(date_br($m['vencimento'])) ?><?php if ($s === 'atrasada'): ?> <small style="color:var(--danger);"><?= dias_atraso($m['vencimento'], $hoje) ?> d</small><?php endif; ?></td>
          <td class="font-mono"><?= brl($m['valor']) ?></td>
          <td><?= badge_mensalidade($s) ?></td>
          <td class="font-mono text-dim"><?= $m['pago_em'] ? h(date_br($m['pago_em'])) . ' · ' . h($m['forma_pagamento']) : '—' ?></td>
          <td class="text-right" style="white-space:nowrap;">
            <?php if ($s !== 'paga'): ?>
              <button type="button" class="btn btn-sm btn-accent" style="width:auto;" data-toggle="modal" data-target="#modalBaixa"
                      data-id="<?= (int)$m['id'] ?>" data-nome="<?= h($m['nome']) ?>" data-valor="<?= h(brl($m['valor'])) ?>"
                      data-inad="<?= $m['status'] === 'inadimplente' ? '1' : '0' ?>"><i class="bi bi-check2"></i> Dar baixa</button>
            <?php else: ?>
              <form method="post" class="d-inline" data-confirm="Desfazer este pagamento? A cobrança volta a ficar em aberto.">
                <?= csrf_field() ?><input type="hidden" name="qs" value="<?= h($qs) ?>">
                <input type="hidden" name="acao" value="estornar"><input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                <button class="btn btn-sm btn-outline-dsk" title="Desfazer pagamento"><i class="bi bi-arrow-counterclockwise"></i></button>
              </form>
            <?php endif; ?>
            <form method="post" class="d-inline" data-confirm="Excluir esta cobrança?">
              <?= csrf_field() ?><input type="hidden" name="qs" value="<?= h($qs) ?>">
              <input type="hidden" name="acao" value="excluir"><input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
              <button class="btn btn-sm btn-outline-dsk" title="Excluir cobrança"><i class="bi bi-trash"></i></button>
            </form>
          </td>
        </tr>
      <?php endforeach; if (!$lista): ?>
        <tr><td colspan="7"><div class="empty-state"><i class="bi bi-receipt"></i>Nenhuma cobrança encontrada.<br><small>Use “Gerar mensalidades” para criar as cobranças do mês.</small></div></td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="row">
  <div class="col-lg-6 mb-3">
    <div class="dsk-card h-100">
      <div class="section-title"><i class="bi bi-lock-fill"></i> Contas offline por inadimplência</div>
      <div class="table-responsive">
        <table class="table-dsk compact">
          <thead><tr><th>Cliente</th><th>Em aberto</th><th>Motivo</th></tr></thead>
          <tbody>
          <?php foreach ($offline as $o): ?>
            <tr data-href="/admin/clientes.php?editar=<?= (int)$o['id'] ?>">
              <td><a class="name-link" href="/admin/clientes.php?editar=<?= (int)$o['id'] ?>"><?= h($o['nome']) ?></a></td>
              <td class="font-mono"><?= brl($o['devendo']) ?> <small class="text-faint">(<?= (int)$o['qtd'] ?>)</small></td>
              <td class="text-dim"><?= h($o['status_motivo'] ?: '—') ?></td>
            </tr>
          <?php endforeach; if (!$offline): ?>
            <tr><td colspan="3"><div class="empty-state"><i class="bi bi-unlock"></i>Nenhuma conta offline.</div></td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-lg-6 mb-3">
    <div class="dsk-card h-100">
      <div class="section-title"><i class="bi bi-bar-chart-fill"></i> Últimos 6 meses</div>
      <?php foreach ($meses as $mm): $pct = $mm['previsto'] > 0 ? (int)round($mm['recebido'] / $mm['previsto'] * 100) : 0; ?>
        <div class="mb-3">
          <div class="d-flex justify-content-between" style="font-size:12.5px;">
            <span class="font-mono"><?= h(mes_br($mm['referencia'])) ?></span>
            <span class="font-mono"><?= brl($mm['recebido']) ?> de <?= brl($mm['previsto']) ?> · <?= $pct ?>%</span>
          </div>
          <div class="progress"><div class="progress-bar" style="width:<?= $pct ?>%;"></div></div>
        </div>
      <?php endforeach; if (!$meses): ?>
        <div class="empty-state"><i class="bi bi-bar-chart"></i>Sem mensalidades nos últimos meses.</div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Modal: dar baixa -->
<div class="modal fade" id="modalBaixa" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document"><div class="modal-content">
    <form method="post">
      <?= csrf_field() ?><input type="hidden" name="qs" value="<?= h($qs) ?>">
      <input type="hidden" name="acao" value="baixar"><input type="hidden" name="id" id="baixaId">
      <div class="modal-header"><h5 class="modal-title font-display">Registrar pagamento</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Fechar"><span aria-hidden="true">&times;</span></button></div>
      <div class="modal-body">
        <div class="route-pill mb-3"><i class="bi bi-person"></i> <span id="baixaNome"></span> · <strong id="baixaValor"></strong></div>
        <div class="form-row">
          <div class="form-group col-6">
            <label class="form-label" for="baixaData">Data do pagamento</label>
            <input type="date" class="form-control" id="baixaData" name="pago_em" value="<?= h($hoje) ?>" max="<?= h($hoje) ?>">
          </div>
          <div class="form-group col-6">
            <label class="form-label" for="baixaForma">Forma</label>
            <select class="custom-select" id="baixaForma" name="forma">
              <?php foreach (FORMAS_PAGAMENTO as $fp): ?><option><?= h($fp) ?></option><?php endforeach; ?>
            </select>
          </div>
        </div>
        <label class="return-option mb-0" id="baixaReativarBox" style="display:none;">
          <input type="checkbox" name="reativar" value="1" checked>
          <i class="bi bi-unlock-fill"></i>
          <span>Reativar a conta<small>Só reativa se não restar nenhuma cobrança vencida.</small></span>
        </label>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-dsk" data-dismiss="modal">Cancelar</button>
        <button class="btn btn-accent px-4"><i class="bi bi-check2-circle"></i> Confirmar pagamento</button>
      </div>
    </form>
  </div></div>
</div>

<!-- Modal: nova cobrança -->
<div class="modal fade" id="modalNova" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document"><div class="modal-content">
    <form method="post">
      <?= csrf_field() ?><input type="hidden" name="qs" value="<?= h($qs) ?>"><input type="hidden" name="acao" value="criar">
      <div class="modal-header"><h5 class="modal-title font-display">Nova cobrança</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Fechar"><span aria-hidden="true">&times;</span></button></div>
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label" for="novaCli">Cliente</label>
          <select class="custom-select" id="novaCli" name="cliente_id" required>
            <option value="">— Selecione —</option>
            <?php foreach ($clientes as $c): ?><option value="<?= (int)$c['id'] ?>"><?= h($c['nome']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="form-row">
          <div class="form-group col-6">
            <label class="form-label" for="novaMes">Mês de referência</label>
            <input type="month" class="form-control" id="novaMes" name="mes" value="<?= h(date('Y-m')) ?>" required>
          </div>
          <div class="form-group col-6">
            <label class="form-label" for="novaValor">Valor (R$)</label>
            <input type="text" inputmode="decimal" class="form-control" id="novaValor" name="valor" placeholder="0,00" required>
          </div>
        </div>
        <div class="form-group mb-0">
          <label class="form-label" for="novaVenc">Vencimento</label>
          <input type="date" class="form-control" id="novaVenc" name="vencimento" value="<?= h($hoje) ?>" required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-dsk" data-dismiss="modal">Cancelar</button>
        <button class="btn btn-accent px-4"><i class="bi bi-check2-circle"></i> Lançar cobrança</button>
      </div>
    </form>
  </div></div>
</div>

<!-- Modal: gerar mensalidades do mês -->
<div class="modal fade" id="modalGerar" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document"><div class="modal-content">
    <form method="post">
      <?= csrf_field() ?><input type="hidden" name="qs" value="<?= h($qs) ?>"><input type="hidden" name="acao" value="gerar">
      <div class="modal-header"><h5 class="modal-title font-display">Gerar mensalidades</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Fechar"><span aria-hidden="true">&times;</span></button></div>
      <div class="modal-body">
        <p class="text-dim" style="font-size:13px;">Cria a cobrança do mês para todos os clientes <strong>ativos</strong> e <strong>inadimplentes</strong>, usando o valor e o dia de vencimento de cada um. Quem já tem cobrança nesse mês é ignorado (não duplica).</p>
        <label class="form-label" for="gerarMes">Mês de referência</label>
        <input type="month" class="form-control" id="gerarMes" name="mes" value="<?= h(date('Y-m')) ?>" required>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-dsk" data-dismiss="modal">Cancelar</button>
        <button class="btn btn-accent px-4"><i class="bi bi-magic"></i> Gerar</button>
      </div>
    </form>
  </div></div>
</div>
<?php app_footer('<script>
$("#modalBaixa").on("show.bs.modal", function (e) {
  var b = $(e.relatedTarget);
  $("#baixaId").val(b.data("id"));
  $("#baixaNome").text(b.data("nome"));
  $("#baixaValor").text(b.data("valor"));
  $("#baixaReativarBox").css("display", String(b.data("inad")) === "1" ? "inline-flex" : "none");
});
</script>');
