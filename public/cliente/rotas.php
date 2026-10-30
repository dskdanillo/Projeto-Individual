<?php
require __DIR__ . '/../../app/bootstrap.php';
$u   = require_login('cliente');
$cid = (int)$u['cliente_id'];
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $acao = $_POST['acao'] ?? '';

  if ($acao === 'criar' || $acao === 'editar') {
    $id      = (int)($_POST['id'] ?? 0);
        $origem  = trim((string)($_POST['origem'] ?? ''));
        $destino = trim((string)($_POST['destino'] ?? ''));
        $km      = num_in($_POST['km'] ?? '');
        $vkm     = num_in($_POST['valor_km'] ?? '');
        $data    = (string)($_POST['data_rota'] ?? '');
    $idaVolta = isset($_POST['ida_volta']) ? 1 : 0;
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
            $data = date('Y-m-d');
        }

        if ($origem === '' || $destino === '') {
            flash('danger', 'Informe a origem e o destino.');
        } elseif ($km <= 0) {
            flash('danger', 'A distância (km) precisa ser maior que zero.');
        } elseif ($vkm <= 0) {
            flash('danger', 'Informe o valor por km.');
        } else {
      $total = round($km * $vkm * ($idaVolta ? 2 : 1), 2);
      if ($acao === 'editar') {
        $st = $pdo->prepare('UPDATE rotas SET origem=?,destino=?,km=?,ida_volta=?,valor_km=?,valor_receber=?,data_rota=? WHERE id=? AND cliente_id=?');
        $st->execute([$origem, $destino, $km, $idaVolta, $vkm, $total, $data, $id, $cid]);
        flash('success', 'Rota atualizada.');
        redirect('/cliente/rotas.php');
      }

      $pdo->prepare('INSERT INTO rotas (cliente_id,origem,destino,km,ida_volta,valor_km,valor_receber,data_rota) VALUES (?,?,?,?,?,?,?,?)')
        ->execute([$cid, mb_substr($origem, 0, 255), mb_substr($destino, 0, 255), $km, $idaVolta, $vkm, $total, $data]);
      $novo = (int)$pdo->lastInsertId();
      flash('success', 'Rota cadastrada! Agora lance os custos dela.');
      redirect('/cliente/custos.php?rota_id=' . $novo);
        }
    } elseif ($acao === 'excluir') {
        $pdo->prepare('DELETE FROM rotas WHERE id = ? AND cliente_id = ?')->execute([(int)($_POST['id'] ?? 0), $cid]);
        flash('success', 'Rota excluída.');
    }
    redirect('/cliente/rotas.php');
}

$filtroErro = '';
try {
    $filtroRotas = ler_filtro_periodo($_GET, 'todos');
} catch (InvalidArgumentException $e) {
    $filtroRotas = ler_filtro_periodo([], 'todos');
    $filtroErro = $e->getMessage();
}

$sqlRotas = 'SELECT r.*, COALESCE((SELECT SUM(c.valor * CASE WHEN r.ida_volta = 1 THEN 2 ELSE 1 END)
                                     FROM custos_rota c WHERE c.rota_id = r.id),0) custo
               FROM rotas r WHERE r.cliente_id = ?';
$paramsRotas = [$cid];
if ($filtroRotas['de'] !== null && $filtroErro === '') {
    $sqlRotas .= ' AND r.data_rota BETWEEN ? AND ?';
    $paramsRotas[] = $filtroRotas['de'];
    $paramsRotas[] = $filtroRotas['ate'];
}
$sqlRotas .= ' ORDER BY r.data_rota DESC, r.id DESC';
$st = $pdo->prepare($sqlRotas);
$st->execute($paramsRotas);
$rotas = $st->fetchAll();

$editarId = (int)($_GET['editar'] ?? 0);
$editing = null;
if ($editarId > 0) {
    $st = $pdo->prepare('SELECT * FROM rotas WHERE id = ? AND cliente_id = ?');
    $st->execute([$editarId, $cid]);
    $editing = $st->fetch() ?: null;
    if (!$editing) {
        flash('danger', 'Rota não encontrada.');
        redirect('/cliente/rotas.php');
    }
}
$formData = $editing ?: [
    'origem' => '', 'destino' => '', 'km' => '', 'valor_km' => '',
    'data_rota' => date('Y-m-d'), 'ida_volta' => 0,
];

app_header($u, 'Rotas', 'Cadastre, edite e acompanhe os trajetos e valores', 'rotas');
?>
<div class="dsk-card mb-3">
  <div class="section-title"><i class="bi <?= $editing ? 'bi-pencil-square' : 'bi-plus-circle-fill' ?>"></i> <?= $editing ? 'Editar rota' : 'Nova rota' ?></div>

  <form method="post" id="formRota">
    <?= csrf_field() ?>
    <input type="hidden" name="acao" value="<?= $editing ? 'editar' : 'criar' ?>">
    <?php if ($editing): ?><input type="hidden" name="id" value="<?= (int)$editing['id'] ?>"><?php endif; ?>

    <div class="form-row">
      <div class="form-group col-md-6">
        <label class="form-label" for="origem">De (origem)</label>
        <input type="text" class="form-control" id="origem" name="origem" placeholder="Endereço de partida em Joinville" value="<?= h($formData['origem']) ?>" required>
      </div>
      <div class="form-group col-md-6">
        <label class="form-label" for="destino">Para (destino)</label>
        <input type="text" class="form-control" id="destino" name="destino" placeholder="Endereço de entrega em Joinville" value="<?= h($formData['destino']) ?>" required>
      </div>
    </div>

    <div class="d-flex align-items-center flex-wrap mb-3" style="gap:.75rem;">
      <button type="button" class="btn btn-outline-dsk" id="btnCalcKm" style="height:40px;padding:.4rem .9rem;"><i class="bi bi-geo-alt"></i> Calcular distância</button>
      <span id="kmStatus" style="font-size:12.5px;color:var(--text-dim);">Preencha origem e destino para calcular a distância automaticamente (OpenStreetMap).</span>
    </div>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css">
    <div id="mapaRota" class="mb-3" style="display:none;height:320px;border-radius:12px;border:1px solid var(--border, rgba(255,255,255,.12));overflow:hidden;z-index:0;"></div>

    <label class="return-option mb-3" for="ida_volta">
      <input type="checkbox" id="ida_volta" name="ida_volta" value="1" <?= $formData['ida_volta'] ? 'checked' : '' ?>>
      <i class="bi bi-arrow-return-left"></i>
      <span>Incluir retorno<small>Duplica a distância e o valor a receber.</small></span>
    </label>

    <div class="form-row align-items-end">
      <div class="form-group col-6 col-md-3">
        <label class="form-label" for="km">Distância de ida (km)</label>
        <input type="text" inputmode="decimal" class="form-control" id="km" name="km" placeholder="Automático ou digite" value="<?= $editing ? h(number_format((float)$formData['km'], 2, ',', '.')) : '' ?>" required>
      </div>
      <div class="form-group col-6 col-md-3">
        <label class="form-label" for="valor_km">Valor por km (R$)</label>
        <input type="text" inputmode="decimal" class="form-control" id="valor_km" name="valor_km" placeholder="0,00" value="<?= $editing ? h(number_format((float)$formData['valor_km'], 2, ',', '.')) : '' ?>" required>
      </div>
      <div class="form-group col-6 col-md-3">
        <label class="form-label" for="data_rota">Data</label>
        <input type="date" class="form-control" id="data_rota" name="data_rota" value="<?= h($formData['data_rota']) ?>">
      </div>
      <div class="form-group col-6 col-md-3">
        <label class="form-label">Distância total</label>
        <div class="receive-box mb-2" id="kmTotal">0,00 km</div>
        <label class="form-label">Você vai receber</label>
        <div class="receive-box" id="valorReceber">R$ 0,00</div>
      </div>
    </div>

    <button type="submit" class="btn btn-accent px-4" style="height:46px;"><i class="bi bi-check2-circle"></i> <?= $editing ? 'Atualizar rota' : 'Salvar rota' ?></button>
    <?php if ($editing): ?><a href="/cliente/rotas.php" class="btn btn-outline-dsk ml-2" style="height:46px;padding:.65rem 1rem;">Cancelar</a><?php endif; ?>
  </form>
</div>

<div class="dsk-card">
  <div class="section-title"><i class="bi bi-list-ul"></i> Minhas rotas</div>
  <p class="text-dim" style="font-size:12.5px;">Toque numa rota para lançar ou ver os custos dela.</p>
  <?php if ($filtroErro !== ''): ?>
    <div class="alert alert-danger py-2 px-3" style="font-size:13.5px;border-radius:10px;"><?= h($filtroErro) ?></div>
  <?php endif; ?>
  <form method="get" class="mb-3" data-date-filter>
    <div class="form-row align-items-end">
      <div class="form-group col-md-3">
        <label class="form-label" for="filtroRotasPeriodo">Filtrar histórico por</label>
        <select class="custom-select" id="filtroRotasPeriodo" name="periodo">
          <option value="todos" <?= $filtroRotas['tipo'] === 'todos' ? 'selected' : '' ?>>Todo o histórico</option>
          <option value="dia" <?= $filtroRotas['tipo'] === 'dia' ? 'selected' : '' ?>>Dia específico</option>
          <option value="mes" <?= $filtroRotas['tipo'] === 'mes' ? 'selected' : '' ?>>Mês</option>
          <option value="ano" <?= $filtroRotas['tipo'] === 'ano' ? 'selected' : '' ?>>Ano</option>
          <option value="intervalo" <?= $filtroRotas['tipo'] === 'intervalo' ? 'selected' : '' ?>>Período personalizado</option>
        </select>
      </div>
      <div class="form-group col-md-3" data-period-field="dia">
        <label class="form-label" for="filtroRotasDia">Data</label>
        <input type="date" class="form-control" id="filtroRotasDia" name="dia" value="<?= h($filtroRotas['dia']) ?>">
      </div>
      <div class="form-group col-md-3" data-period-field="mes">
        <label class="form-label" for="filtroRotasMes">Mês</label>
        <input type="month" class="form-control" id="filtroRotasMes" name="mes" value="<?= h($filtroRotas['mes']) ?>">
      </div>
      <div class="form-group col-md-3" data-period-field="ano">
        <label class="form-label" for="filtroRotasAno">Ano</label>
        <input type="number" class="form-control" id="filtroRotasAno" name="ano" min="1900" max="9999" value="<?= h($filtroRotas['ano']) ?>">
      </div>
      <div class="form-group col-md-3" data-period-field="intervalo">
        <label class="form-label" for="filtroRotasInicio">Data inicial</label>
        <input type="date" class="form-control" id="filtroRotasInicio" name="inicio" value="<?= h($filtroRotas['inicio']) ?>">
      </div>
      <div class="form-group col-md-3" data-period-field="intervalo">
        <label class="form-label" for="filtroRotasFim">Data final</label>
        <input type="date" class="form-control" id="filtroRotasFim" name="fim" value="<?= h($filtroRotas['fim']) ?>">
      </div>
      <div class="form-group col-md-3">
        <button type="submit" class="btn btn-outline-dsk" style="height:46px;"><i class="bi bi-funnel"></i> Filtrar histórico</button>
        <a href="/cliente/rotas.php" class="btn btn-outline-dsk" style="height:46px;padding:.65rem 1rem;">Limpar</a>
      </div>
    </div>
  </form>
  <div class="table-responsive">
    <table class="table-dsk">
      <thead><tr><th>Data</th><th>Rota</th><th>Km</th><th>R$/km</th><th>Receber</th><th>Custo</th><th>Lucro</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($rotas as $r): $l = $r['valor_receber'] - $r['custo']; ?>
        <tr data-href="/cliente/custos.php?rota_id=<?= (int)$r['id'] ?>">
          <td class="font-mono"><?= date_br($r['data_rota']) ?></td>
          <td style="min-width:220px;"><?= h($r['origem']) ?> <i class="bi bi-arrow-right text-faint"></i> <?= h($r['destino']) ?></td>
          <td class="font-mono"><?= number_format($r['km'] * ($r['ida_volta'] ? 2 : 1), 1, ',', '.') ?><?= $r['ida_volta'] ? ' <small class="text-dim">ida e volta</small>' : '' ?></td>
          <td class="font-mono"><?= brl($r['valor_km']) ?></td>
          <td class="font-mono"><?= brl($r['valor_receber']) ?></td>
          <td class="font-mono"><?= brl($r['custo']) ?></td>
          <td class="font-mono" style="color:var(--<?= $l >= 0 ? 'success' : 'danger' ?>);"><?= brl($l) ?></td>
          <td class="text-nowrap text-right">
            <a href="/cliente/rotas.php?editar=<?= (int)$r['id'] ?>" class="btn btn-sm btn-outline-dsk"><i class="bi bi-pencil"></i> Editar</a>
            <a href="/cliente/custos.php?rota_id=<?= (int)$r['id'] ?>" class="btn btn-sm btn-outline-dsk"><i class="bi bi-receipt"></i> Custos</a>
            <form method="post" class="d-inline" data-confirm="Excluir esta rota e os custos dela?">
              <?= csrf_field() ?><input type="hidden" name="acao" value="excluir"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
              <button class="btn btn-sm btn-outline-dsk" title="Excluir"><i class="bi bi-trash"></i></button>
            </form>
          </td>
        </tr>
      <?php endforeach; if (!$rotas): ?>
        <tr><td colspan="8"><div class="empty-state"><i class="bi bi-signpost"></i>Nenhuma rota cadastrada ainda.</div></td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php
$extra = '<script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js"></script><script src="/assets/js/routes.js"></script>';
$extra .= '<script src="/assets/js/date-filter.js"></script>';
app_footer($extra);
