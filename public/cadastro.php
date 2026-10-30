<?php
require __DIR__ . '/../app/bootstrap.php';
require_once APP_PATH . '/lib/mensalidades.php';
seed_if_empty();

if ($u = current_user()) {
    redirect(home_for($u['perfil']));
}

$erros = [];
$v = array_fill_keys(['nome', 'email', 'telefone', 'cidade', 'tipo_veiculo', 'modelo_veiculo', 'placa'], '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($v as $k => $_) {
        $v[$k] = trim((string)($_POST[$k] ?? ''));
    }
    $v['email'] = mb_strtolower($v['email']);
    $v['telefone'] = normalizar_telefone($v['telefone']);
    $v['cidade'] = formatar_cidade($v['cidade']);
    $v['placa'] = normalizar_placa($v['placa']);
    $senha  = (string)($_POST['senha'] ?? '');
    $senha2 = (string)($_POST['senha2'] ?? '');

    if ($v['nome'] === '')                                $erros[] = 'Informe o nome.';
    if (!filter_var($v['email'], FILTER_VALIDATE_EMAIL))  $erros[] = 'E-mail inválido.';
    if (strlen($v['telefone']) < 10 || strlen($v['telefone']) > 13) $erros[] = 'Informe um telefone válido com DDD (10 a 13 dígitos).';
    if (!cidade_valida($v['cidade'])) $erros[] = 'Informe a cidade no formato CIDADE / UF, por exemplo JOINVILLE / SC.';
    if (strlen($senha) < 6)                               $erros[] = 'A senha deve ter ao menos 6 caracteres.';
    if ($senha !== $senha2)                               $erros[] = 'As senhas não conferem.';
    if (!placa_valida($v['placa']))                       $erros[] = 'Informe uma placa válida (modelo antigo ABC-1234 ou Mercosul ABC1D23).';
    if (!in_array($v['tipo_veiculo'], ['Carro', 'Moto', 'Van', 'Utilitário'], true)) $v['tipo_veiculo'] = 'Carro';

    if (!$erros) {
        $pdo = db();
        $ex = $pdo->prepare('SELECT 1 FROM usuarios WHERE email = ?');
        $ex->execute([$v['email']]);
        if ($ex->fetch()) {
            $erros[] = 'Já existe um cadastro com este e-mail.';
        } else {
            $ex = $pdo->prepare('SELECT 1 FROM clientes WHERE telefone = ?');
            $ex->execute([$v['telefone']]);
            if ($ex->fetch()) {
                $erros[] = 'Este telefone já está cadastrado.';
            } else {
                $pdo->beginTransaction();
                try {
                    $pdo->prepare('INSERT INTO usuarios (nome,email,senha,perfil) VALUES (?,?,?,"cliente")')
                        ->execute([$v['nome'], $v['email'], password_hash($senha, PASSWORD_DEFAULT)]);
                    $uid = (int)$pdo->lastInsertId();
                    $pdo->prepare('INSERT INTO clientes (usuario_id,telefone,cidade,tipo_veiculo,modelo_veiculo,placa,dia_vencimento) VALUES (?,?,?,?,?,?,?)')
                        ->execute([$uid, $v['telefone'], $v['cidade'], $v['tipo_veiculo'], $v['modelo_veiculo'], $v['placa'], (int)date('j')]);
                    gerar_mensalidades($pdo, date('Y-m'), (int)$pdo->lastInsertId());
                    $pdo->commit();
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    if ($e->getCode() !== '23000') {
                        throw $e;
                    }
                    $erros[] = 'Já existe um cadastro com este e-mail ou telefone.';
                }
                if (!$erros) {
                    flash('success', 'Cadastro realizado! A primeira mensalidade já está disponível; entre com seu e-mail e senha.');
                    redirect('/index.php');
                }
            }
        }
    }
}

html_head('Cadastro');
echo theme_switch();
?>
<div class="auth-shell" style="align-items:flex-start;padding-top:50px;">
  <div class="auth-card" style="max-width:640px;">
    <div class="brand-mark">
      <div>
        <p class="brand-title">Criar conta de motorista</p>
        <div class="brand-sub">DSK · Gestão de Custo</div>
      </div>
    </div>
    <hr class="route-divider">

    <?php foreach ($erros as $e): ?>
      <div class="alert alert-danger py-2 px-3" style="font-size:13px;border-radius:10px;"><?= h($e) ?></div>
    <?php endforeach; ?>

    <form method="post" autocomplete="off">
      <?= csrf_field() ?>
      <div class="form-step-label"><i class="bi bi-person-vcard"></i> Dados pessoais</div>
      <div class="form-group">
        <label class="form-label">Nome completo</label>
        <input type="text" name="nome" class="form-control" value="<?= h($v['nome']) ?>" required>
      </div>
      <div class="form-row">
        <div class="form-group col-md-6">
          <label class="form-label">E-mail</label>
          <input type="email" name="email" class="form-control" value="<?= h($v['email']) ?>" required>
        </div>
        <div class="form-group col-md-6">
          <label class="form-label">Telefone / WhatsApp</label>
          <input type="tel" name="telefone" class="form-control" placeholder="(47) 90000-0000" value="<?= h($v['telefone']) ?>" required>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Cidade/UF</label>
        <input type="text" name="cidade" class="form-control" placeholder="JOINVILLE / SC" value="<?= h($v['cidade']) ?>" maxlength="100" pattern="[\p{Lu}\p{M}][\p{Lu}\p{M} .'-]* / [A-Z]{2}" data-uppercase title="Use CIDADE / UF, por exemplo JOINVILLE / SC." required>
      </div>

      <div class="form-step-label mt-3"><i class="bi bi-car-front"></i> Veículo</div>
      <div class="form-row">
        <div class="form-group col-md-4">
          <label class="form-label">Tipo</label>
          <select name="tipo_veiculo" class="custom-select">
            <?php foreach (['Carro', 'Moto', 'Van', 'Utilitário'] as $t): ?>
              <option <?= $v['tipo_veiculo'] === $t ? 'selected' : '' ?>><?= h($t) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group col-md-5">
          <label class="form-label">Modelo</label>
          <input type="text" name="modelo_veiculo" class="form-control" placeholder="Ex: Fiat Strada" value="<?= h($v['modelo_veiculo']) ?>">
        </div>
        <div class="form-group col-md-3">
          <label class="form-label">Placa</label>
          <input type="text" name="placa" class="form-control" placeholder="ABC-1234 ou ABC1D23" maxlength="8" data-uppercase autocapitalize="characters" spellcheck="false" value="<?= h($v['placa']) ?>">
        </div>
      </div>

      <div class="form-step-label mt-3"><i class="bi bi-shield-lock"></i> Acesso</div>
      <div class="form-row">
        <div class="form-group col-md-6">
          <label class="form-label">Senha</label>
          <input type="password" name="senha" class="form-control" placeholder="Mínimo 6 caracteres" required>
        </div>
        <div class="form-group col-md-6">
          <label class="form-label">Confirmar senha</label>
          <input type="password" name="senha2" class="form-control" required>
        </div>
      </div>

      <button type="submit" class="btn btn-dsk btn-dsk-primary w-100 mb-2"><i class="bi bi-check2-circle"></i> Concluir cadastro</button>
      <a href="/index.php" class="btn btn-dsk btn-dsk-link w-100"><i class="bi bi-arrow-left"></i> Voltar para o login</a>
    </form>
  </div>
</div>
<?php scripts_base();
