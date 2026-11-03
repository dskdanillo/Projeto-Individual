<?php
require __DIR__ . '/../app/bootstrap.php';
require APP_PATH . '/lib/password_reset.php';

$pdo = db();
password_reset_ensure_table($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = mb_strtolower(trim((string)($_POST['email'] ?? '')));
    if (filter_var($email, FILTER_VALIDATE_EMAIL) && strlen($email) <= 150) {
        password_reset_request($pdo, $email, $_SERVER['REMOTE_ADDR'] ?? null);
    }
    flash('success', 'Se houver uma conta ativa com esse e-mail, o administrador receberá um alerta para ajudar na recuperação.');
    redirect('/recuperar-senha.php');
}

header('Cache-Control: no-store, no-cache, must-revalidate');
html_head('Recuperar senha');
echo theme_switch();
?>
<div class="auth-shell">
  <div class="auth-card">
    <div class="brand-mark">
      <div>
        <p class="brand-title">DSK <span class="text-accent">·</span> Gestão de Custo</p>
        <div class="brand-sub">Recuperação de acesso</div>
      </div>
    </div>
    <hr class="route-divider">

    <?= flash_render() ?>
    <h1 class="page-title mb-2">Esqueceu sua senha?</h1>
    <p class="text-dim mb-3" style="font-size:13px;">Informe o e-mail cadastrado. Se a conta estiver ativa, o administrador receberá um alerta e poderá enviar uma senha temporária pelo WhatsApp cadastrado.</p>
    <form method="post" autocomplete="off">
      <?= csrf_field() ?>
      <div class="input-wrap">
        <i class="bi bi-envelope"></i>
        <input type="email" name="email" class="form-control dsk-input" placeholder="seu@email.com" maxlength="150" required>
      </div>
      <button type="submit" class="btn btn-dsk btn-dsk-primary w-100 mt-3">
        <i class="bi bi-bell"></i> Solicitar ajuda ao administrador
      </button>
    </form>

    <a href="/index.php" class="btn btn-dsk btn-dsk-link w-100 mt-2">
      <i class="bi bi-arrow-left"></i> Voltar para o login
    </a>
  </div>
</div>
<?php scripts_base(); ?>
