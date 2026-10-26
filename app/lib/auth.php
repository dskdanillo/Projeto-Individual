<?php
declare(strict_types=1);

const ADMIN_PRINCIPAL_EMAIL = 'dsk.danillo@gmail.com';

function current_user(): ?array
{
    static $user = false;
    if ($user !== false) {
        return $user;
    }
    if (empty($_SESSION['uid'])) {
        return $user = null;
    }
    $st = db()->prepare(
        'SELECT u.id, u.nome, u.email, u.perfil, u.status,
                c.id AS cliente_id, c.tipo_veiculo, c.modelo_veiculo, c.placa
           FROM usuarios u
           LEFT JOIN clientes c ON c.usuario_id = u.id
          WHERE u.id = ?'
    );
    $st->execute([$_SESSION['uid']]);
    $r = $st->fetch();
    if ($r && $r['perfil'] === 'admin' && mb_strtolower($r['email']) !== ADMIN_PRINCIPAL_EMAIL) {
        return $user = null;
    }
    return $user = ($r && $r['status'] === 'ativo') ? $r : null;
}

function home_for(string $perfil): string
{
    return $perfil === 'admin' ? '/admin/dashboard.php' : '/cliente/dashboard.php';
}

function require_login(string $perfil): array
{
    $u = current_user();
    if (!$u) {
        redirect('/index.php');
    }
    if ($u['perfil'] !== $perfil) {
        redirect(home_for($u['perfil']));
    }
    return $u;
}

/** Retorna null se OK, ou a mensagem de erro. */
function do_login(string $email, string $senha, string $perfil): ?string
{
    $st = db()->prepare('SELECT * FROM usuarios WHERE email = ?');
    $st->execute([mb_strtolower(trim($email))]);
    $u = $st->fetch();

    if (!$u || !password_verify($senha, $u['senha'])) {
        return 'E-mail ou senha inválidos.';
    }
    if ($u['perfil'] === 'admin' && mb_strtolower($u['email']) !== ADMIN_PRINCIPAL_EMAIL) {
        return 'Esta conta não está autorizada como administrador.';
    }
    if ($u['status'] !== 'ativo') {
        return 'Esta conta está inativa.';
    }
    if ($u['perfil'] !== $perfil) {
        return $perfil === 'admin'
            ? 'Esta conta não é de administrador. Use a aba "Motorista".'
            : 'Esta conta é de administrador. Use a aba "Administrador".';
    }

    session_regenerate_id(true);
    $_SESSION['uid'] = (int)$u['id'];
    return null;
}
