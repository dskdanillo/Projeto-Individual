<?php
require __DIR__ . '/../app/bootstrap.php';
seed_if_empty();

if ($u = current_user()) {
    redirect(home_for($u['perfil']));
}

$erro = null;
$perfil = ($_POST['perfil'] ?? 'cliente') === 'admin' ? 'admin' : 'cliente';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $erro = do_login((string)($_POST['email'] ?? ''), (string)($_POST['senha'] ?? ''), $perfil);
    if ($erro === null) {
        redirect(home_for($perfil));
    }
}

html_head('Entrar');
echo theme_switch();
?>
<div class="auth-shell">
  <div class="auth-card">
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
      <input type="hidden" name="perfil" id="perfil" value="<?= h($perfil) ?>">

      <div class="seg mb-3" role="tablist">
        <button type="button" data-perfil="cliente" class="<?= $perfil === 'cliente' ? 'active' : '' ?>"><i class="bi bi-person-badge"></i> Motorista</button>
        <button type="button" data-perfil="admin" class="<?= $perfil === 'admin' ? 'active' : '' ?>"><i class="bi bi-shield-lock"></i> Administrador</button>
      </div>

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
document.querySelectorAll(".seg [data-perfil]").forEach(function(b){
  b.addEventListener("click", function(){
    document.querySelectorAll(".seg button").forEach(function(x){ x.classList.remove("active"); });
    b.classList.add("active");
    document.getElementById("perfil").value = b.dataset.perfil;
    document.getElementById("linkCadastro").style.display = b.dataset.perfil === "admin" ? "none" : "flex";
  });
});
document.getElementById("linkCadastro").style.display = document.getElementById("perfil").value === "admin" ? "none" : "flex";
document.getElementById("togglePass").addEventListener("click", function(){
  var i = document.getElementById("senha"), ic = this.querySelector("i");
  if (i.type === "password") { i.type = "text"; ic.className = "bi bi-eye-slash"; }
  else { i.type = "password"; ic.className = "bi bi-eye"; }
});
</script>');
