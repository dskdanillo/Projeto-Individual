<?php
require __DIR__ . '/../app/bootstrap.php';
seed_if_empty();

if ($u = current_user()) {
    redirect(home_for($u['perfil']));
}

$erro = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $erro = do_login((string)($_POST['email'] ?? ''), (string)($_POST['senha'] ?? ''));
    if ($erro === null) {
        $st = db()->prepare('SELECT perfil FROM usuarios WHERE id = ?');
        $st->execute([$_SESSION['uid']]);
        redirect(home_for((string)$st->fetchColumn()));
    }
}

html_head('Entrar');
echo theme_switch();
?>
<div class="auth-shell">
  <div class="auth-card">
    <svg class="login-mark" viewBox="0 0 320 112" role="img" aria-label="DSK">
      <defs>
        <linearGradient id="login-mark-gradient" x1="0" y1="0" x2="1" y2="1">
          <stop offset="0%" stop-color="var(--accent)"/>
          <stop offset="100%" stop-color="var(--accent-2)"/>
        </linearGradient>
      </defs>
      <circle class="login-mark__orbit" cx="160" cy="56" r="44"/>
      <path class="login-mark__route" d="M18 82 C65 82 66 29 111 29 S165 83 211 83 S255 34 302 34"/>
      <circle class="login-mark__pin" cx="302" cy="34" r="5"/>
      <text class="login-mark__letters" x="160" y="69" text-anchor="middle">DSK</text>
    </svg>
    <div class="brand-mark">
      <div>
        <p class="brand-title">DSK <span class="text-accent">·</span> Gestão de Custo</p>
        <div class="brand-sub">Controle de rotas, custos e lucro</div>
      </div>
    </div>

    <hr class="route-divider">

    <?= flash_render() ?>
    <?php if ($erro): ?>
      <div class="alert alert-danger py-2 px-3" style="font-size:13px;border-radius:10px;"><?= h($erro) ?></div>
    <?php endif; ?>

    <form method="post" autocomplete="off">
      <?= csrf_field() ?>
      <p class="text-dim mb-3" style="font-size:13px;">Entre com seu e-mail e senha.</p>

      <div class="input-wrap">
        <i class="bi bi-envelope"></i>
        <input type="email" name="email" class="form-control dsk-input" placeholder="seu@email.com" value="<?= h($_POST['email'] ?? '') ?>" required>
      </div>
      <div class="input-wrap">
        <i class="bi bi-lock"></i>
        <input type="password" name="senha" id="senha" class="form-control dsk-input" placeholder="Senha" required>
        <button type="button" class="toggle-pass" id="togglePass" aria-label="Mostrar senha"><i class="bi bi-eye"></i></button>
      </div>

      <button type="submit" class="btn btn-dsk btn-dsk-primary w-100 mb-2 mt-3">
        <i class="bi bi-box-arrow-in-right"></i> Entrar
      </button>
      <a href="/cadastro.php" class="btn btn-dsk btn-dsk-ghost w-100" id="linkCadastro">
        <i class="bi bi-person-plus"></i> Criar conta de motorista
      </a>
    </form>

  </div>
</div>
<?php
scripts_base('<script>
document.getElementById("togglePass").addEventListener("click", function(){
  var i = document.getElementById("senha"), ic = this.querySelector("i");
  if (i.type === "password") { i.type = "text"; ic.className = "bi bi-eye-slash"; }
  else { i.type = "password"; ic.className = "bi bi-eye"; }
});
</script>');
