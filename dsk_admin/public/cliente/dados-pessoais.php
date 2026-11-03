<?php
require __DIR__ . '/../../app/bootstrap.php';
$u = require_login('cliente');
$pdo = db();
$id = (int)$u['id'];

$st = $pdo->prepare('SELECT u.nome, u.email, c.telefone, c.cidade, c.tipo_veiculo,
                            c.modelo_veiculo, c.placa, c.consumo_km_l, c.preco_combustivel
                       FROM usuarios u JOIN clientes c ON c.usuario_id = u.id
                      WHERE u.id = ? AND u.perfil = \'cliente\'');
$st->execute([$id]);
$dados = $st->fetch();
if (!$dados) {
    throw new RuntimeException('Não foi possível localizar os dados deste cliente.');
}

$erros = [];
$form = $dados;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (($_POST['action'] ?? '') === 'change_password') {
        $senhaAtual = (string)($_POST['senha_atual'] ?? '');
        $senhaNova = (string)($_POST['senha_nova'] ?? '');
        $confirmacao = (string)($_POST['confirmacao_senha'] ?? '');
        $check = $pdo->prepare("SELECT senha FROM usuarios WHERE id = ? AND perfil = 'cliente'");
        $check->execute([$id]);
        $senhaHash = $check->fetchColumn();

        if (!is_string($senhaHash) || !password_verify($senhaAtual, $senhaHash)) {
            $erros[] = 'A senha atual está incorreta.';
        }
        if (mb_strlen($senhaNova, 'UTF-8') < 8) {
            $erros[] = 'A nova senha precisa ter pelo menos 8 caracteres.';
        }
        if ($senhaNova !== $confirmacao) {
            $erros[] = 'As senhas não conferem.';
        }

        if (!$erros) {
            $pdo->prepare("UPDATE usuarios SET senha = ? WHERE id = ? AND perfil = 'cliente'")
                ->execute([password_hash($senhaNova, PASSWORD_DEFAULT), $id]);
            flash('success', 'Sua senha foi alterada.');
            redirect('/cliente/dados-pessoais.php');
        }
    } else {
    $form = [
        'nome' => mb_substr(trim((string)($_POST['nome'] ?? '')), 0, 150),
        'email' => mb_strtolower(trim((string)($_POST['email'] ?? ''))),
        'telefone' => normalizar_telefone((string)($_POST['telefone'] ?? '')),
        'cidade' => formatar_cidade((string)($_POST['cidade'] ?? '')),
        'tipo_veiculo' => trim((string)($_POST['tipo_veiculo'] ?? '')),
        'modelo_veiculo' => mb_substr(trim((string)($_POST['modelo_veiculo'] ?? '')), 0, 80),
        'placa' => normalizar_placa((string)($_POST['placa'] ?? '')),
        'consumo_km_l' => trim((string)($_POST['consumo_km_l'] ?? '')),
        'preco_combustivel' => trim((string)($_POST['preco_combustivel'] ?? '')),
    ];

    if ($form['nome'] === '') {
        $erros[] = 'Informe seu nome.';
    }
    if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $erros[] = 'Informe um e-mail válido.';
    }
    if (strlen($form['telefone']) < 10 || strlen($form['telefone']) > 13) {
        $erros[] = 'Informe um telefone válido com DDD (10 a 13 dígitos).';
    }
    if (!cidade_valida($form['cidade'])) {
        $erros[] = 'Informe a cidade no formato CIDADE / UF, por exemplo JOINVILLE / SC.';
    }
    if (!in_array($form['tipo_veiculo'], ['Carro', 'Moto', 'Van', 'Utilitário'], true)) {
        $erros[] = 'Selecione um tipo de veículo válido.';
    }
    if (!placa_valida($form['placa'])) {
        $erros[] = 'Informe uma placa válida (modelo antigo ABC-1234 ou Mercosul ABC1D23).';
    }
    if (($form['consumo_km_l'] === '') !== ($form['preco_combustivel'] === '')) {
        $erros[] = 'Informe o consumo do veículo e o preço do combustível juntos.';
    } elseif ($form['consumo_km_l'] !== '') {
        $consumo = num_in($form['consumo_km_l']);
        $precoCombustivel = num_in($form['preco_combustivel']);
        if ($consumo < 0.1 || $consumo > 100) {
            $erros[] = 'Informe um consumo entre 0,1 e 100 km/L.';
        }
        if ($precoCombustivel <= 0 || $precoCombustivel > 1000) {
            $erros[] = 'Informe um preço por litro maior que zero e até R$ 1.000,00.';
        }
    }
    if (!$erros) {
        $check = $pdo->prepare('SELECT 1 FROM usuarios WHERE email = ? AND id <> ?');
        $check->execute([$form['email'], $id]);
        if ($check->fetch()) {
            $erros[] = 'Este e-mail já está cadastrado em outra conta.';
        }
    }
    if (!$erros) {
        $check = $pdo->prepare('SELECT 1 FROM clientes WHERE telefone = ? AND usuario_id <> ?');
        $check->execute([$form['telefone'], $id]);
        if ($check->fetch()) {
            $erros[] = 'Este telefone já está cadastrado em outra conta.';
        }
    }

    if (!$erros) {
        $pdo->beginTransaction();
        try {
            $pdo->prepare("UPDATE usuarios SET nome = ?, email = ? WHERE id = ? AND perfil = 'cliente'")
                ->execute([$form['nome'], $form['email'], $id]);
            $pdo->prepare('UPDATE clientes
                              SET telefone = ?, cidade = ?, tipo_veiculo = ?, modelo_veiculo = ?, placa = ?,
                                  consumo_km_l = ?, preco_combustivel = ?
                            WHERE usuario_id = ?')
                ->execute([
                    $form['telefone'], $form['cidade'], $form['tipo_veiculo'],
                    $form['modelo_veiculo'], $form['placa'],
                    $form['consumo_km_l'] === '' ? null : num_in($form['consumo_km_l']),
                    $form['preco_combustivel'] === '' ? null : num_in($form['preco_combustivel']),
                    $id,
                ]);
            $pdo->commit();
            flash('success', 'Seus dados pessoais foram atualizados.');
            redirect('/cliente/dados-pessoais.php');
        } catch (PDOException $e) {
            $pdo->rollBack();
            if ($e->getCode() !== '23000') {
                throw $e;
            }
            $erros[] = 'Este e-mail ou telefone já está cadastrado em outra conta.';
        }
    }
    }
}

app_header($u, 'Dados pessoais', 'Atualize suas informações de cadastro', 'dados-pessoais');
?>
<?php if ($erros): ?>
  <div class="alert alert-danger py-2 px-3" style="font-size:13.5px;border-radius:10px;"><?= implode('<br>', array_map('h', $erros)) ?></div>
<?php endif; ?>

<div class="dsk-card">
  <form method="post" autocomplete="on">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save_profile">
    <div class="form-step-label"><i class="bi bi-person-vcard"></i> Cadastro pessoal</div>
    <div class="form-row">
      <div class="form-group col-md-6">
        <label class="form-label" for="nome">Nome completo</label>
        <input type="text" class="form-control" id="nome" name="nome" maxlength="150" value="<?= h($form['nome']) ?>" required>
      </div>
      <div class="form-group col-md-6">
        <label class="form-label" for="email">E-mail de acesso</label>
        <input type="email" class="form-control" id="email" name="email" maxlength="150" value="<?= h($form['email']) ?>" required>
      </div>
      <div class="form-group col-md-6">
        <label class="form-label" for="telefone">Telefone / WhatsApp</label>
        <input type="tel" class="form-control" id="telefone" name="telefone" value="<?= h($form['telefone']) ?>" required>
      </div>
      <div class="form-group col-md-6">
        <label class="form-label" for="cidade">Cidade / UF</label>
        <input type="text" class="form-control" id="cidade" name="cidade" maxlength="100"
               pattern="[\p{Lu}\p{M}][\p{Lu}\p{M} .'-]* / [A-Z]{2}" data-uppercase
               title="Use CIDADE / UF, por exemplo JOINVILLE / SC." value="<?= h($form['cidade']) ?>" required>
      </div>
      <div class="form-group col-md-4">
        <label class="form-label" for="tipo_veiculo">Tipo de veículo</label>
        <select class="custom-select" id="tipo_veiculo" name="tipo_veiculo" required>
          <?php foreach (['Carro', 'Moto', 'Van', 'Utilitário'] as $tipo): ?>
            <option value="<?= h($tipo) ?>" <?= $form['tipo_veiculo'] === $tipo ? 'selected' : '' ?>><?= h($tipo) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group col-md-5">
        <label class="form-label" for="modelo_veiculo">Modelo do veículo</label>
        <input type="text" class="form-control" id="modelo_veiculo" name="modelo_veiculo" maxlength="80" value="<?= h($form['modelo_veiculo']) ?>">
      </div>
      <div class="form-group col-md-3">
        <label class="form-label" for="placa">Placa</label>
        <input type="text" class="form-control" id="placa" name="placa" maxlength="8" data-uppercase autocapitalize="characters" spellcheck="false" value="<?= h($form['placa']) ?>">
      </div>
      <div class="form-group col-md-4">
        <label class="form-label" for="consumo_km_l">Consumo do veículo (km/L)</label>
        <input type="text" inputmode="decimal" class="form-control" id="consumo_km_l" name="consumo_km_l"
               placeholder="Ex.: 10,5" value="<?= h($form['consumo_km_l'] ?? '') ?>">
      </div>
      <div class="form-group col-md-4">
        <label class="form-label" for="preco_combustivel">Preço do combustível (R$/L)</label>
        <input type="text" inputmode="decimal" class="form-control" id="preco_combustivel" name="preco_combustivel"
               placeholder="Ex.: 5,89" value="<?= h($form['preco_combustivel'] ?? '') ?>">
      </div>
      <div class="form-group col-md-12 text-dim" style="font-size:12.5px;">
        Informe os dois valores para que os custos de combustível sejam calculados automaticamente por rota. Deixe ambos em branco para não usar o cálculo automático.
      </div>
    </div>

    <button type="submit" class="btn btn-accent px-4" style="height:46px;"><i class="bi bi-check2-circle"></i> Salvar dados</button>
  </form>
</div>
<div class="dsk-card mt-3">
  <div class="section-title"><i class="bi bi-shield-lock"></i> Alterar senha</div>
  <p class="text-dim">Depois de entrar com a senha temporária enviada pelo administrador, troque-a por uma senha pessoal.</p>
  <form method="post" autocomplete="off">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="change_password">
    <div class="form-row">
      <div class="form-group col-md-4">
        <label class="form-label" for="senha_atual">Senha atual</label>
        <input type="password" class="form-control" id="senha_atual" name="senha_atual" autocomplete="current-password" required>
      </div>
      <div class="form-group col-md-4">
        <label class="form-label" for="senha_nova">Nova senha</label>
        <input type="password" class="form-control" id="senha_nova" name="senha_nova" minlength="8" autocomplete="new-password" required>
      </div>
      <div class="form-group col-md-4">
        <label class="form-label" for="confirmacao_senha">Confirme a nova senha</label>
        <input type="password" class="form-control" id="confirmacao_senha" name="confirmacao_senha" minlength="8" autocomplete="new-password" required>
      </div>
    </div>
    <button type="submit" class="btn btn-accent px-4" style="height:46px;"><i class="bi bi-key"></i> Alterar senha</button>
  </form>
</div>
<?php app_footer();
