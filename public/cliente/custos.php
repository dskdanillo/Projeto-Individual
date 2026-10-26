<?php
require __DIR__ . '/../../app/bootstrap.php';
$u   = require_login('cliente');
$cid = (int)$u['cliente_id'];
$pdo = db();

function rota_do_cliente(PDO $pdo, int $rid, int $cid): ?array
{
    $st = $pdo->prepare('SELECT * FROM rotas WHERE id = ? AND cliente_id = ?');
    $st->execute([$rid, $cid]);
    return $st->fetch() ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $acao = $_POST['acao'] ?? '';
    $rid  = (int)($_POST['rota_id'] ?? 0);

    if ($acao === 'criar') {
        $rota  = rota_do_cliente($pdo, $rid, $cid);
        $cat   = (string)($_POST['categoria'] ?? '');
        $valor = num_in($_POST['valor'] ?? '');
        $data  = (string)($_POST['data_custo'] ?? '');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
            $data = date('Y-m-d');
        }
        if (!$rota) {
            flash('danger', 'Rota não encontrada.');
        } elseif (!in_array($cat, CATEGORIAS_CUSTO, true)) {
            flash('danger', 'Escolha uma categoria válida.');
        } elseif ($valor <= 0) {
            flash('danger', 'Informe um valor maior que zero.');
        } else {
            $pdo->prepare('INSERT INTO custos_rota (rota_id,categoria,descricao,valor,data_custo) VALUES (?,?,?,?,?)')
                ->execute([$rid, $cat, mb_substr(trim((string)($_POST['descricao'] ?? '')), 0, 200), $valor, $data]);
            flash('success', 'Custo lançado.');
        }
    } elseif ($acao === 'atualizar') {
        $id    = (int)($_POST['id'] ?? 0);
        $cat   = (string)($_POST['categoria'] ?? '');
        $valor = num_in($_POST['valor'] ?? '');
        $data  = (string)($_POST['data_custo'] ?? '');
        $dataValida = preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)
            && checkdate((int)substr($data, 5, 2), (int)substr($data, 8, 2), (int)substr($data, 0, 4));

        $st = $pdo->prepare('SELECT c.id FROM custos_rota c JOIN rotas r ON r.id = c.rota_id
                             WHERE c.id = ? AND c.rota_id = ? AND r.cliente_id = ?');
        $st->execute([$id, $rid, $cid]);
        if (!$st->fetch()) {
            flash('danger', 'Custo não encontrado nesta rota.');
        } elseif (!in_array($cat, CATEGORIAS_CUSTO, true)) {
            flash('danger', 'Escolha uma categoria válida.');
        } elseif ($valor <= 0) {
            flash('danger', 'Informe um valor maior que zero.');
        } elseif (!$dataValida) {
            flash('danger', 'Informe uma data válida.');
        } else {
            $pdo->prepare('UPDATE custos_rota SET categoria = ?, descricao = ?, valor = ?, data_custo = ?
                           WHERE id = ? AND rota_id = ?')
                ->execute([$cat, mb_substr(trim((string)($_POST['descricao'] ?? '')), 0, 200), $valor, $data, $id, $rid]);
            flash('success', 'Custo atualizado.');
        }
    } elseif ($acao === 'excluir') {
        $pdo->prepare('DELETE c FROM custos_rota c JOIN rotas r ON r.id = c.rota_id WHERE c.id = ? AND r.cliente_id = ?')
            ->execute([(int)($_POST['id'] ?? 0), $cid]);
        flash('success', 'Custo removido.');
    }
    redirect('/cliente/custos.php' . ($rid ? '?rota_id=' . $rid : ''));
}

$rid  = (int)($_GET['rota_id'] ?? 0);
$rota = $rid ? rota_do_cliente($pdo, $rid, $cid) : null;
$editId = (int)($_GET['editar'] ?? 0);
$custoEditando = null;
$totalCusto = 0.0;
$lucro = 0.0;
$kmTotal = 0.0;

$st = $pdo->prepare('SELECT id, origem, destino, data_rota FROM rotas WHERE cliente_id = ? ORDER BY data_rota DESC, id DESC LIMIT 100');
$st->execute([$cid]);
$todas = $st->fetchAll();

if ($rota) {
    $st = $pdo->prepare('SELECT * FROM custos_rota WHERE rota_id = ? ORDER BY data_custo DESC, id DESC');
    $st->execute([$rid]);
    $custos = $st->fetchAll();
    if ($editId) {
        $st = $pdo->prepare('SELECT * FROM custos_rota WHERE id = ? AND rota_id = ?');
        $st->execute([$editId, $rid]);
        $custoEditando = $st->fetch() ?: null;
        if (!$custoEditando) {
            flash('danger', 'Custo não encontrado nesta rota.');
        }
    }
    $multiplicadorCusto = $rota['ida_volta'] ? 2 : 1;
    $totalCusto = array_sum(array_column($custos, 'valor')) * $multiplicadorCusto;
    $lucro = $rota['valor_receber'] - $totalCusto;
    $kmTotal = $rota['km'] * ($rota['ida_volta'] ? 2 : 1);
} else {
    $st = $pdo->prepare('SELECT c.*, r.origem, r.destino, r.ida_volta FROM custos_rota c JOIN rotas r ON r.id = c.rota_id
                          WHERE r.cliente_id = ? ORDER BY c.data_custo DESC, c.id DESC LIMIT 100');
    $st->execute([$cid]);
    $custos = $st->fetchAll();
}

app_header($u, 'Custos da rota', $rota ? 'Lance o que você gastou para realizar esta rota' : 'Escolha uma rota para lançar os custos', 'custos');
?>
<div class="dsk-card mb-3">
  <form method="get">
    <label class="form-label" for="selRota">Rota</label>
    <select name="rota_id" id="selRota" class="custom-select" onchange="this.form.submit()">
      <option value="">— Selecione uma rota —</option>
      <?php foreach ($todas as $t): ?>
        <option value="<?= (int)$t['id'] ?>" <?= $t['id'] == $rid ? 'selected' : '' ?>>
          <?= h(date_br($t['data_rota'])) ?> · <?= h($t['origem']) ?> → <?= h($t['destino']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </form>
</div>

<?php if ($rota): ?>
  <div class="row">
    <div class="col-12 col-md-4 mb-3"><div class="dsk-card kpi-card">
      <div class="kpi-icon" style="color:var(--success);background:rgba(95,214,153,.14);"><i class="bi bi-cash-coin"></i></div>
      <p class="kpi-value"><?= brl($rota['valor_receber']) ?></p>
      <div class="kpi-label">A receber (<?= number_format($kmTotal, 1, ',', '.') ?> km <?= $rota['ida_volta'] ? 'ida e volta' : 'de ida' ?> × <?= brl($rota['valor_km']) ?>)</div>
    </div></div>
    <div class="col-6 col-md-4 mb-3"><div class="dsk-card kpi-card">
      <div class="kpi-icon" style="color:var(--danger);background:rgba(239,117,117,.14);"><i class="bi bi-graph-down-arrow"></i></div>
      <p class="kpi-value"><?= brl($totalCusto) ?></p>
      <div class="kpi-label">Custos desta rota</div>
    </div></div>
    <div class="col-6 col-md-4 mb-3"><div class="dsk-card kpi-card">
      <div class="kpi-icon"><i class="bi bi-piggy-bank-fill"></i></div>
      <p class="kpi-value" style="color:var(--<?= $lucro >= 0 ? 'success' : 'danger' ?>);"><?= brl($lucro) ?></p>
      <div class="kpi-label">Lucro da rota</div>
    </div></div>
  </div>

  <div class="dsk-card mb-3">
    <div class="section-title"><i class="bi <?= $custoEditando ? 'bi-pencil-square' : 'bi-plus-circle-fill' ?>"></i> <?= $custoEditando ? 'Editar custo' : 'Novo custo' ?></div>
    <div class="route-pill mb-3"><i class="bi bi-geo-alt"></i> <?= h($rota['origem']) ?> → <?= h($rota['destino']) ?></div>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="acao" value="<?= $custoEditando ? 'atualizar' : 'criar' ?>">
      <input type="hidden" name="rota_id" value="<?= $rid ?>">
      <?php if ($custoEditando): ?><input type="hidden" name="id" value="<?= (int)$custoEditando['id'] ?>"><?php endif; ?>
      <div class="form-row">
        <div class="form-group col-md-3">
          <label class="form-label">Categoria</label>
          <select name="categoria" class="custom-select" required>
            <?php foreach (CATEGORIAS_CUSTO as $c): ?><option value="<?= h($c) ?>" <?= ($custoEditando['categoria'] ?? '') === $c ? 'selected' : '' ?>><?= h($c) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="form-group col-md-4">
          <label class="form-label">Descrição (opcional)</label>
          <input type="text" name="descricao" class="form-control" maxlength="200" placeholder="Ex: Posto Shell" value="<?= h($custoEditando['descricao'] ?? '') ?>">
        </div>
        <div class="form-group col-6 col-md-2">
          <label class="form-label"><?= $rota['ida_volta'] ? 'Valor por trecho (R$)' : 'Valor (R$)' ?></label>
          <input type="text" inputmode="decimal" name="valor" class="form-control" placeholder="0,00" value="<?= $custoEditando ? h(number_format((float)$custoEditando['valor'], 2, ',', '.')) : '' ?>" required>
        </div>
        <div class="form-group col-6 col-md-3">
          <label class="form-label">Data</label>
          <input type="date" name="data_custo" class="form-control" value="<?= h($custoEditando['data_custo'] ?? $rota['data_rota']) ?>">
        </div>
      </div>
      <button class="btn btn-accent px-4" style="height:46px;"><i class="bi bi-check2-circle"></i> <?= $custoEditando ? 'Salvar alterações' : 'Lançar custo' ?></button>
      <?php if ($custoEditando): ?> <a href="/cliente/custos.php?rota_id=<?= $rid ?>" class="btn btn-outline-dsk">Cancelar</a><?php endif; ?>
    </form>
  </div>
<?php endif; ?>

<div class="dsk-card">
  <div class="section-title"><i class="bi bi-receipt"></i> <?= $rota ? 'Custos desta rota' : 'Todos os custos lançados' ?></div>
  <div class="table-responsive">
    <table class="table-dsk">
      <thead><tr><th>Data</th><?php if (!$rota): ?><th>Rota</th><?php endif; ?><th>Categoria</th><th>Descrição</th><th><?= $rota && $rota['ida_volta'] ? 'Valor total' : 'Valor' ?></th><th></th></tr></thead>
      <tbody>
      <?php foreach ($custos as $c): ?>
        <tr <?= !$rota ? 'data-href="/cliente/custos.php?rota_id=' . (int)$c['rota_id'] . '"' : '' ?>>
          <td class="font-mono"><?= date_br($c['data_custo']) ?></td>
          <?php if (!$rota): ?><td style="min-width:200px;"><?= h($c['origem']) ?> → <?= h($c['destino']) ?></td><?php endif; ?>
          <td><?= h($c['categoria']) ?></td>
          <td class="text-dim"><?= h($c['descricao']) ?></td>
          <td class="font-mono"><?= brl($c['valor'] * (($rota['ida_volta'] ?? $c['ida_volta']) ? 2 : 1)) ?></td>
          <td class="text-right">
            <a href="/cliente/custos.php?rota_id=<?= (int)$c['rota_id'] ?>&editar=<?= (int)$c['id'] ?>" class="btn btn-sm btn-outline-dsk" title="Editar" aria-label="Editar custo"><i class="bi bi-pencil"></i></a>
            <form method="post" class="d-inline" data-confirm="Remover este custo?">
              <?= csrf_field() ?><input type="hidden" name="acao" value="excluir">
              <input type="hidden" name="id" value="<?= (int)$c['id'] ?>"><input type="hidden" name="rota_id" value="<?= $rid ?>">
              <button class="btn btn-sm btn-outline-dsk" title="Remover"><i class="bi bi-trash"></i></button>
            </form>
          </td>
        </tr>
      <?php endforeach; if (!$custos): ?>
        <tr><td colspan="6"><div class="empty-state"><i class="bi bi-receipt"></i>Nenhum custo lançado.</div></td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php app_footer();
