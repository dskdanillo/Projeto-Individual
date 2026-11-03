<?php
require __DIR__ . '/../../app/bootstrap.php';
require APP_PATH . '/lib/password_reset.php';

$u = require_login('admin');
$pdo = db();
password_reset_ensure_table($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $requestId = filter_var($_POST['request_id'] ?? null, FILTER_VALIDATE_INT);
    if ($requestId === false || $requestId === null || $requestId < 1) {
        flash('danger', 'Solicitação inválida.');
        redirect('/admin/recuperacoes-senha.php');
    }

    try {
        $recovery = password_reset_generate($pdo, $requestId, (int)$u['id']);
        $message = "Olá, {$recovery['name']}! Conforme solicitado, sua senha temporária de acesso ao DSK é: {$recovery['password']}. Entre com seu e-mail cadastrado e use essa senha. Depois, altere-a na aba Dados pessoais. Se você não solicitou a recuperação, avise o administrador.";
        redirect('https://wa.me/' . $recovery['phone'] . '?text=' . rawurlencode($message));
    } catch (RuntimeException $error) {
        flash('danger', $error->getMessage());
        redirect('/admin/recuperacoes-senha.php');
    }
}

$requests = $pdo->query(
    "SELECT r.id, r.requested_at, u.nome, u.email, c.telefone
       FROM password_reset_requests r
       JOIN usuarios u ON u.id = r.user_id AND u.perfil = 'cliente'
       LEFT JOIN clientes c ON c.usuario_id = u.id
      WHERE r.status = 'pending'
      ORDER BY r.requested_at ASC, r.id ASC
      LIMIT 100"
)->fetchAll();

app_header($u, 'Recuperação de senhas', 'Solicitações dos motoristas para recuperar o acesso', 'recuperacoes-senha');
?>
<div class="dsk-card">
  <div class="section-title"><i class="bi bi-bell-fill"></i> Solicitações pendentes</div>
  <p class="text-dim">Ao atender uma solicitação, uma senha temporária aleatória será criada e o WhatsApp do motorista abrirá com a mensagem pronta para envio.</p>
  <?php if ($requests): ?>
    <div class="table-responsive">
      <table class="table-dsk compact">
        <thead><tr><th>Motorista</th><th>E-mail</th><th>WhatsApp</th><th>Solicitada em</th><th>Ação</th></tr></thead>
        <tbody>
        <?php foreach ($requests as $request): ?>
          <tr>
            <td><?= h($request['nome']) ?></td>
            <td><?= h($request['email']) ?></td>
            <td><?= h($request['telefone'] ?? 'Não informado') ?></td>
            <td class="font-mono"><?= h(date('d/m/Y H:i', strtotime($request['requested_at']))) ?></td>
            <td>
              <form method="post" onsubmit="return confirm('Gerar uma nova senha para este motorista e abrir o WhatsApp?');">
                <?= csrf_field() ?>
                <input type="hidden" name="request_id" value="<?= (int)$request['id'] ?>">
                <button type="submit" class="btn btn-sm btn-success">
                  <i class="bi bi-whatsapp"></i> Gerar senha e abrir WhatsApp
                </button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <div class="empty-state"><i class="bi bi-check2-circle"></i>Nenhuma solicitação de recuperação pendente.</div>
  <?php endif; ?>
</div>
<?php app_footer();
