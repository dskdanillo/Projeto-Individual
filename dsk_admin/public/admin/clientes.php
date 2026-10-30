<?php
require __DIR__ . '/../../app/bootstrap.php';
require_once APP_PATH . '/lib/admin.php';
require_once APP_PATH . '/lib/mensalidades.php';
$u    = require_login('admin');
$pdo  = db();

$erros = [];
$form  = null;   // valores digitados, para repovoar o formulário se der erro

/* ============================================================
   AÇÕES (POST)
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $acao = (string)($_POST['acao'] ?? '');
    $id   = (int)($_POST['id'] ?? 0);   // id da tabela usuarios

    // ---- CRIAR cliente ----
    if ($acao === 'criar') {
        [$d, $erros] = ler_cliente_post($_POST);
        if (strlen($d['senha']) < 6) {
            $erros[] = 'A senha deve ter ao menos 6 caracteres.';
        }
        if (!$erros) {
            $ex = $pdo->prepare('SELECT 1 FROM usuarios WHERE email = ?');
            $ex->execute([$d['email']]);
            if ($ex->fetch()) {
                $erros[] = 'Já existe um cadastro com este e-mail.';
            }
        }
        if (!$erros) {
            $ex = $pdo->prepare('SELECT 1 FROM clientes WHERE telefone = ?');
            $ex->execute([$d['telefone']]);
            if ($ex->fetch()) {
                $erros[] = 'Este telefone já está cadastrado.';
            }
        }
        if (!$erros) {
            $pdo->beginTransaction();
            try {
                $pdo->prepare("INSERT INTO usuarios (nome,email,senha,perfil,status) VALUES (?,?,?,'cliente','ativo')")
                    ->execute([$d['nome'], $d['email'], password_hash($d['senha'], PASSWORD_DEFAULT)]);
                $novo = (int)$pdo->lastInsertId();
                $pdo->prepare('INSERT INTO clientes (usuario_id,telefone,cidade,tipo_veiculo,modelo_veiculo,placa,valor_mensalidade,dia_vencimento) VALUES (?,?,?,?,?,?,?,?)')
                    ->execute([$novo, $d['telefone'], $d['cidade'], $d['tipo_veiculo'], $d['modelo_veiculo'], $d['placa'], $d['valor_mensalidade'], $d['dia_vencimento']]);
                gerar_mensalidades($pdo, date('Y-m'), (int)$pdo->lastInsertId());
                $pdo->commit();
            } catch (Throwable $e) {
                $pdo->rollBack();
                throw $e;
            }
            flash('success', 'Cliente cadastrado e primeira mensalidade lançada.');
            redirect('/admin/clientes.php?editar=' . $novo);
        }
        $form = $d;
        $_GET['novo'] = '1';

    // ---- SALVAR (dados, login e senha) ----
    } elseif ($acao === 'salvar') {
        [$d, $erros] = ler_cliente_post($_POST);
        if ($d['senha'] !== '' && strlen($d['senha']) < 6) {
            $erros[] = 'A nova senha deve ter ao menos 6 caracteres (ou deixe em branco para manter a atual).';
        }
        if (!$erros) {
            $ex = $pdo->prepare('SELECT 1 FROM usuarios WHERE email = ? AND id <> ?');
            $ex->execute([$d['email'], $id]);
            if ($ex->fetch()) {
                $erros[] = 'Já existe outro cadastro com este e-mail.';
            }
        }
        if (!$erros) {
            $ex = $pdo->prepare('SELECT 1 FROM clientes WHERE telefone = ? AND usuario_id <> ?');
            $ex->execute([$d['telefone'], $id]);
            if ($ex->fetch()) {
                $erros[] = 'Este telefone já está cadastrado em outra conta.';
            }
        }
        if (!$erros) {
            $pdo->beginTransaction();
            try {
                $up = $pdo->prepare("UPDATE usuarios SET nome = ?, email = ? WHERE id = ? AND perfil = 'cliente'");
                $up->execute([$d['nome'], $d['email'], $id]);
                if ($d['senha'] !== '') {
                    $pdo->prepare("UPDATE usuarios SET senha = ? WHERE id = ? AND perfil = 'cliente'")
                        ->execute([password_hash($d['senha'], PASSWORD_DEFAULT), $id]);
                }
                $pdo->prepare('INSERT INTO clientes (usuario_id,telefone,cidade,tipo_veiculo,modelo_veiculo,placa,valor_mensalidade,dia_vencimento)
                               VALUES (?,?,?,?,?,?,?,?)
                               ON DUPLICATE KEY UPDATE telefone = VALUES(telefone), cidade = VALUES(cidade), tipo_veiculo = VALUES(tipo_veiculo),
                                    modelo_veiculo = VALUES(modelo_veiculo), placa = VALUES(placa), valor_mensalidade = VALUES(valor_mensalidade),
                                    dia_vencimento = VALUES(dia_vencimento)')
                    ->execute([$id, $d['telefone'], $d['cidade'], $d['tipo_veiculo'], $d['modelo_veiculo'], $d['placa'], $d['valor_mensalidade'], $d['dia_vencimento']]);
                $pdo->commit();
            } catch (Throwable $e) {
                $pdo->rollBack();
                throw $e;
            }
            flash('success', $d['senha'] !== '' ? 'Dados salvos e senha redefinida.' : 'Dados do cliente salvos.');
            redirect('/admin/clientes.php?editar=' . $id);
        }
        $form = $d;
        $_GET['editar'] = (string)$id;

    // ---- STATUS da conta: reativar / manter offline (inadimplente) / desativar ----
    } elseif ($acao === 'status') {
        $novo   = (string)($_POST['novo_status'] ?? '');
        $motivo = mb_substr(trim((string)($_POST['motivo'] ?? '')), 0, 255);
        if (!isset(STATUS_CONTA[$novo])) {
            flash('danger', 'Situação inválida.');
        } else {
            $st = $pdo->prepare("UPDATE usuarios SET status = ?, status_motivo = ?, status_alterado_em = ? WHERE id = ? AND perfil = 'cliente'");
            $st->execute([$novo, ($novo === 'ativo' || $motivo === '') ? null : $motivo, date('Y-m-d H:i:s'), $id]);
            flash('success', [
                'ativo'        => 'Conta reativada: o cliente já pode entrar no sistema.',
                'inadimplente' => 'Conta mantida offline por inadimplência até o acerto.',
                'inativo'      => 'Conta desativada: o cliente não consegue mais entrar.',
            ][$novo]);
        }
        redirect('/admin/clientes.php?editar=' . $id);

    // ---- EXCLUIR conta ----
    } elseif ($acao === 'excluir') {
        $st = $pdo->prepare("DELETE FROM usuarios WHERE id = ? AND perfil = 'cliente'");
        $st->execute([$id]);
        flash($st->rowCount() ? 'success' : 'danger', $st->rowCount() ? 'Conta do cliente excluída.' : 'Cliente não encontrado.');
        redirect('/admin/clientes.php');
    }
}

/* ============================================================
   TELAS (GET): edição, novo cliente ou lista
   ============================================================ */
$editarId = (int)($_GET['editar'] ?? 0);
$novoCli  = isset($_GET['novo']);
$cli      = null;

if ($editarId > 0) {
    $st = $pdo->prepare("SELECT u.id, u.nome, u.email, u.status, u.status_motivo, u.status_alterado_em, u.criado_em,
                                c.id AS cliente_id, c.telefone, c.cidade, c.tipo_veiculo, c.modelo_veiculo, c.placa,
                                c.valor_mensalidade, c.dia_vencimento
                           FROM usuarios u LEFT JOIN clientes c ON c.usuario_id = u.id
                          WHERE u.id = ? AND u.perfil = 'cliente'");
    $st->execute([$editarId]);
    $cli = $st->fetch() ?: null;
    if (!$cli) {
        flash('danger', 'Cliente não encontrado.');
        redirect('/admin/clientes.php');
    }
}

/* valor de um campo do formulário: o que foi digitado > o que está no banco > padrão */
$f = function (string $k, $padrao = '') use (&$form, &$cli) {
    return $form[$k] ?? ($cli[$k] ?? $padrao);
};
/* ------------------------------------------------------------
   Formulário (usado em "novo" e em "editar")
   ------------------------------------------------------------ */
$campos_cliente = function (bool $novo) use ($f): void { ?>
      <div class="form-step-label"><i class="bi bi-person-vcard"></i> Dados do cliente</div>
      <div class="form-row">
        <div class="form-group col-md-6">
          <label class="form-label" for="nome">Nome</label>
          <input type="text" class="form-control" id="nome" name="nome" maxlength="150" value="<?= h($f('nome')) ?>" required>
        </div>
        <div class="form-group col-md-3">
          <label class="form-label" for="telefone">Telefone</label>
          <input type="tel" class="form-control" id="telefone" name="telefone" maxlength="30" value="<?= h($f('telefone')) ?>" required>
        </div>
        <div class="form-group col-md-3">
          <label class="form-label" for="cidade">Cidade</label>
          <input type="text" class="form-control" id="cidade" name="cidade" maxlength="100" pattern="[\p{Lu}\p{M}][\p{Lu}\p{M} .'-]* / [A-Z]{2}" data-uppercase title="Use CIDADE / UF, por exemplo JOINVILLE / SC." value="<?= h($f('cidade')) ?>" required>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group col-md-3">
          <label class="form-label" for="tipo_veiculo">Tipo de veículo</label>
          <select class="custom-select" id="tipo_veiculo" name="tipo_veiculo">
            <?php foreach (TIPOS_VEICULO as $t): ?><option value="<?= h($t) ?>" <?= $f('tipo_veiculo', 'Carro') === $t ? 'selected' : '' ?>><?= h($t) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="form-group col-md-6">
          <label class="form-label" for="modelo_veiculo">Modelo</label>
          <input type="text" class="form-control" id="modelo_veiculo" name="modelo_veiculo" maxlength="80" value="<?= h($f('modelo_veiculo')) ?>">
        </div>
        <div class="form-group col-md-3">
          <label class="form-label" for="placa">Placa</label>
          <input type="text" class="form-control" id="placa" name="placa" maxlength="8" data-uppercase autocapitalize="characters" spellcheck="false" placeholder="ABC-1234 ou ABC1D23" value="<?= h($f('placa')) ?>">
        </div>
      </div>

      <div class="form-step-label mt-2"><i class="bi bi-calendar-check"></i> Cobrança mensal</div>
      <div class="form-row">
        <div class="form-group col-md-6">
          <label class="form-label" for="valor_mensalidade">Valor mensal (R$)</label>
          <input type="text" inputmode="decimal" class="form-control" id="valor_mensalidade" name="valor_mensalidade"
                 value="<?= h(number_format((float)$f('valor_mensalidade', 49.90), 2, ',', '.')) ?>" required>
        </div>
        <div class="form-group col-md-6">
          <label class="form-label" for="dia_vencimento">Dia de vencimento</label>
          <input type="number" class="form-control" id="dia_vencimento" name="dia_vencimento" min="1" max="31"
                 value="<?= h($f('dia_vencimento', date('j'))) ?>" required>
          <div class="hint">Novos cadastros usam o dia em que a conta foi criada.</div>
        </div>
      </div>

      <div class="form-step-label mt-2"><i class="bi bi-key-fill"></i> Acesso do cliente (login e senha)</div>
      <div class="form-row">
        <div class="form-group col-md-6">
          <label class="form-label" for="email">E-mail (login)</label>
          <input type="email" class="form-control" id="email" name="email" maxlength="150" value="<?= h($f('email')) ?>" required>
        </div>
        <div class="form-group col-md-6">
          <label class="form-label" for="senha"><?= $novo ? 'Senha' : 'Nova senha (opcional)' ?></label>
          <div class="input-group">
            <input type="text" class="form-control" id="senha" name="senha" autocomplete="off" minlength="6"
                   placeholder="<?= $novo ? 'Mínimo 6 caracteres' : 'Deixe em branco para manter a atual' ?>" <?= $novo ? 'required' : '' ?>>
            <div class="input-group-append">
              <button type="button" class="btn btn-outline-dsk" id="btnGerarSenha" title="Gerar senha aleatória"><i class="bi bi-shuffle"></i> Gerar</button>
            </div>
          </div>
          <div class="hint"><?= $novo ? 'Anote a senha para entregar ao cliente.' : 'A senha atual não pode ser consultada (fica criptografada). Para redefinir, digite ou gere uma nova e informe ao cliente.' ?></div>
        </div>
      </div>
<?php };

$jsSenha = '<script>
(function(){
  var b = document.getElementById("btnGerarSenha");
  if (!b) return;
  b.addEventListener("click", function(){
    var c = "abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789", s = "", r = new Uint32Array(10);
    crypto.getRandomValues(r);
    for (var i = 0; i < r.length; i++) s += c[r[i] % c.length];
    document.getElementById("senha").value = s;
  });
})();
</script>';

/* ============================================================
   VISÃO 1 - EDITAR CLIENTE
   ============================================================ */
if ($cli):
    $voltar = '<a href="/admin/clientes.php" class="btn btn-outline-dsk px-3" style="height:42px;display:inline-flex;align-items:center;gap:6px;"><i class="bi bi-arrow-left"></i> Clientes</a>';
    app_header($u, 'Editar cliente', $cli['nome'] . ' · cliente desde ' . date_br($cli['criado_em']), 'clientes', $voltar);
?>
<?php if ($erros): ?>
  <div class="alert alert-danger py-2 px-3" style="font-size:13.5px;border-radius:10px;"><?= implode('<br>', array_map('h', $erros)) ?></div>
<?php endif; ?>

<div class="row">
  <div class="col-lg-8 mb-3">
    <div class="dsk-card">
      <div class="section-title"><i class="bi bi-pencil-square"></i> Informações do cliente</div>
      <form method="post" autocomplete="off">
        <?= csrf_field() ?>
        <input type="hidden" name="acao" value="salvar">
        <input type="hidden" name="id" value="<?= (int)$cli['id'] ?>">
        <?php $campos_cliente(false); ?>
        <button class="btn btn-accent px-4 mt-2" style="height:46px;"><i class="bi bi-check2-circle"></i> Salvar alterações</button>
      </form>
    </div>
  </div>

  <div class="col-lg-4 mb-3">
    <div class="dsk-card mb-3">
      <div class="section-title"><i class="bi bi-toggles"></i> Situação da conta</div>
      <div class="status-box mb-3">
        Atual: <?= badge_conta($cli['status']) ?>
        <?php if ($cli['status'] !== 'ativo' && $cli['status_motivo']): ?><div class="mt-2"><strong>Motivo:</strong> <?= h($cli['status_motivo']) ?></div><?php endif; ?>
        <?php if ($cli['status_alterado_em']): ?><div class="mt-1">Alterada em <?= h(date('d/m/Y H:i', strtotime($cli['status_alterado_em']))) ?></div><?php endif; ?>
      </div>
      <p class="text-dim" style="font-size:12.5px;">Conta <strong>inadimplente</strong> ou <strong>inativa</strong> fica offline: o cliente não consegue entrar (e quem estiver logado é desconectado) até você reativar.</p>
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="acao" value="status">
        <input type="hidden" name="id" value="<?= (int)$cli['id'] ?>">
        <label class="form-label" for="motivo">Motivo / observação</label>
        <input type="text" class="form-control mb-3" id="motivo" name="motivo" maxlength="255" placeholder="Ex.: 2 mensalidades em aberto"
               value="<?= h($cli['status'] !== 'ativo' ? $cli['status_motivo'] : '') ?>">
        <?php if ($cli['status'] !== 'ativo'): ?>
          <button name="novo_status" value="ativo" class="btn btn-accent btn-block mb-2" style="height:44px;"><i class="bi bi-unlock-fill"></i> Reativar conta</button>
        <?php endif; ?>
        <?php if ($cli['status'] !== 'inadimplente'): ?>
          <button name="novo_status" value="inadimplente" class="btn btn-warning-dsk btn-block mb-2" style="height:44px;"><i class="bi bi-lock-fill"></i> Manter offline (inadimplente)</button>
        <?php endif; ?>
        <?php if ($cli['status'] !== 'inativo'): ?>
          <button name="novo_status" value="inativo" class="btn btn-outline-dsk btn-block" style="height:44px;"><i class="bi bi-person-dash-fill"></i> Desativar conta</button>
        <?php endif; ?>
      </form>
    </div>

    <div class="dsk-card danger-zone">
      <div class="section-title"><i class="bi bi-trash3-fill"></i> Excluir conta</div>
      <p class="text-dim" style="font-size:12.5px;">Remove o cliente e <strong>todas as rotas, custos e mensalidades</strong> dele. Não dá para desfazer.</p>
      <form method="post" data-confirm="Excluir DEFINITIVAMENTE a conta de <?= h($cli['nome']) ?> e todos os dados dela?">
        <?= csrf_field() ?>
        <input type="hidden" name="acao" value="excluir">
        <input type="hidden" name="id" value="<?= (int)$cli['id'] ?>">
        <button class="btn btn-danger-dsk btn-block" style="height:44px;"><i class="bi bi-trash3"></i> Excluir conta do cliente</button>
      </form>
    </div>
  </div>
</div>

<?php app_footer($jsSenha);
    exit;
endif;

/* ============================================================
   VISÃO 2 - NOVO CLIENTE
   ============================================================ */
if ($novoCli):
    $voltar = '<a href="/admin/clientes.php" class="btn btn-outline-dsk px-3" style="height:42px;display:inline-flex;align-items:center;gap:6px;"><i class="bi bi-arrow-left"></i> Clientes</a>';
    app_header($u, 'Novo cliente', 'Cadastre um cliente manualmente', 'clientes', $voltar);
?>
<?php if ($erros): ?>
  <div class="alert alert-danger py-2 px-3" style="font-size:13.5px;border-radius:10px;"><?= implode('<br>', array_map('h', $erros)) ?></div>
<?php endif; ?>
<div class="dsk-card">
  <div class="section-title"><i class="bi bi-person-plus-fill"></i> Novo cliente</div>
  <form method="post" autocomplete="off">
    <?= csrf_field() ?>
    <input type="hidden" name="acao" value="criar">
    <?php $campos_cliente(true); ?>
    <button class="btn btn-accent px-4 mt-2" style="height:46px;"><i class="bi bi-check2-circle"></i> Cadastrar cliente</button>
    <a href="/admin/clientes.php" class="btn btn-outline-dsk ml-2" style="height:46px;padding:.65rem 1rem;">Cancelar</a>
  </form>
</div>
<?php app_footer($jsSenha);
    exit;
endif;

/* ============================================================
   VISÃO 3 - LISTA DE CLIENTES
   ============================================================ */
$q  = trim((string)($_GET['q'] ?? ''));
$fs = (string)($_GET['status'] ?? '');
$where  = ["u.perfil = 'cliente'"];
$params = [];
if ($q !== '') {
    $where[] = '(u.nome LIKE ? OR u.email LIKE ? OR c.cidade LIKE ? OR c.placa LIKE ?)';
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like, $like);
}
if (isset(STATUS_CONTA[$fs])) {
    $where[] = 'u.status = ?';
    $params[] = $fs;
}
$st = $pdo->prepare("SELECT u.id, u.nome, u.email, u.status, c.cidade, c.modelo_veiculo, c.placa,
                            (SELECT COUNT(*) FROM rotas r WHERE r.cliente_id = c.id) AS rotas
                       FROM usuarios u LEFT JOIN clientes c ON c.usuario_id = u.id
                      WHERE " . implode(' AND ', $where) . '
                      ORDER BY u.nome');
$st->execute($params);
$lista = $st->fetchAll();

$novo = '<a href="/admin/clientes.php?novo=1" class="btn btn-accent px-3" style="height:42px;display:inline-flex;align-items:center;justify-content:center;gap:6px;"><i class="bi bi-person-plus-fill"></i> Novo cliente</a>';
app_header($u, 'Clientes', 'Controle de acesso e cadastro de todos os clientes', 'clientes', $novo);
?>
<div class="dsk-card mb-3">
  <form method="get" class="filter-bar">
    <div class="form-row align-items-end">
      <div class="form-group col-md-6">
        <label class="form-label" for="q">Buscar</label>
        <input type="text" class="form-control" id="q" name="q" placeholder="Nome, e-mail, cidade ou placa" value="<?= h($q) ?>">
      </div>
      <div class="form-group col-md-3">
        <label class="form-label" for="status">Situação</label>
        <select class="custom-select" id="status" name="status">
          <option value="">Todas</option>
          <?php foreach (STATUS_CONTA as $k => $l): ?><option value="<?= h($k) ?>" <?= $fs === $k ? 'selected' : '' ?>><?= h($l) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="form-group col-md-3 d-flex" style="gap:8px;">
        <button class="btn btn-accent flex-fill" style="height:42px;"><i class="bi bi-search"></i> Filtrar</button>
        <?php if ($q !== '' || $fs !== ''): ?><a href="/admin/clientes.php" class="btn btn-outline-dsk" style="height:42px;padding-top:.6rem;" title="Limpar filtros"><i class="bi bi-x-lg"></i></a><?php endif; ?>
      </div>
    </div>
  </form>
</div>

<div class="dsk-card">
  <div class="section-title"><i class="bi bi-people-fill"></i> <?= count($lista) ?> cliente(s)</div>
  <p class="text-dim" style="font-size:12.5px;">Clique no nome do cliente para editar as informações, redefinir login/senha ou controlar o acesso.</p>
  <div class="table-responsive">
    <table class="table-dsk">
      <thead><tr><th>Cliente</th><th>E-mail (login)</th><th>Cidade</th><th>Veículo</th><th>Rotas</th><th>Conta</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($lista as $c): ?>
        <tr data-href="/admin/clientes.php?editar=<?= (int)$c['id'] ?>">
          <td style="min-width:160px;"><a class="name-link" href="/admin/clientes.php?editar=<?= (int)$c['id'] ?>"><?= h($c['nome']) ?></a></td>
          <td class="text-dim"><?= h($c['email']) ?></td>
          <td><?= h($c['cidade']) ?></td>
          <td><?= h($c['modelo_veiculo']) ?><?php if ($c['placa']): ?> <small class="text-faint font-mono"><?= h($c['placa']) ?></small><?php endif; ?></td>
          <td class="font-mono"><?= (int)$c['rotas'] ?></td>
          <td><?= badge_conta($c['status']) ?></td>
          <td class="text-right"><a href="/admin/clientes.php?editar=<?= (int)$c['id'] ?>" class="btn btn-sm btn-outline-dsk" title="Editar" aria-label="Editar cliente"><i class="bi bi-pencil"></i></a></td>
        </tr>
      <?php endforeach; if (!$lista): ?>
        <tr><td colspan="7"><div class="empty-state"><i class="bi bi-people"></i>Nenhum cliente encontrado.</div></td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php app_footer();
