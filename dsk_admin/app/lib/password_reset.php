<?php
declare(strict_types=1);

function password_reset_ensure_table(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS password_reset_requests (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id INT UNSIGNED DEFAULT NULL,
            email_hash CHAR(64) NOT NULL,
            request_ip_hash CHAR(64) DEFAULT NULL,
            status ENUM(\'pending\', \'completed\', \'ignored\') NOT NULL DEFAULT \'pending\',
            requested_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            handled_at DATETIME DEFAULT NULL,
            handled_by INT UNSIGNED DEFAULT NULL,
            PRIMARY KEY (id),
            KEY idx_password_reset_request_email (email_hash, requested_at),
            KEY idx_password_reset_request_ip (request_ip_hash, requested_at),
            KEY idx_password_reset_request_status (status, requested_at),
            CONSTRAINT fk_password_reset_request_user FOREIGN KEY (user_id) REFERENCES usuarios (id) ON DELETE CASCADE,
            CONSTRAINT fk_password_reset_request_admin FOREIGN KEY (handled_by) REFERENCES usuarios (id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
}

function password_reset_request(PDO $pdo, string $email, ?string $ip): void
{
    password_reset_ensure_table($pdo);
    $pdo->exec("DELETE FROM password_reset_requests WHERE requested_at < DATE_SUB(NOW(), INTERVAL 90 DAY)");

    $emailHash = hash('sha256', mb_strtolower(trim($email)));
    $ipHash = $ip !== null && $ip !== '' ? hash('sha256', $ip) : null;

    $rateEmail = $pdo->prepare(
        'SELECT COUNT(*) FROM password_reset_requests
          WHERE email_hash = ? AND requested_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)'
    );
    $rateEmail->execute([$emailHash]);
    if ((int)$rateEmail->fetchColumn() >= 3) {
        return;
    }

    if ($ipHash !== null) {
        $rateIp = $pdo->prepare(
            'SELECT COUNT(*) FROM password_reset_requests
              WHERE request_ip_hash = ? AND requested_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)'
        );
        $rateIp->execute([$ipHash]);
        if ((int)$rateIp->fetchColumn() >= 10) {
            return;
        }
    }

    $lookup = $pdo->prepare(
        "SELECT u.id
           FROM usuarios u
          WHERE u.email = ? AND u.perfil = 'cliente' AND u.status = 'ativo'
          LIMIT 1"
    );
    $lookup->execute([mb_strtolower(trim($email))]);
    $userId = $lookup->fetchColumn();

    if ($userId !== false) {
        $pending = $pdo->prepare(
            "SELECT 1 FROM password_reset_requests WHERE user_id = ? AND status = 'pending' LIMIT 1"
        );
        $pending->execute([(int)$userId]);
        if ($pending->fetchColumn()) {
            return;
        }
    }

    $insert = $pdo->prepare(
        'INSERT INTO password_reset_requests (user_id, email_hash, request_ip_hash, status)
         VALUES (?, ?, ?, ?)'
    );
    $insert->execute([
        $userId !== false ? (int)$userId : null,
        $emailHash,
        $ipHash,
        $userId !== false ? 'pending' : 'ignored',
    ]);
}

/**
 * @return array{name:string, phone:string, password:string}
 */
function password_reset_generate(PDO $pdo, int $requestId, int $adminId): array
{
    $pdo->beginTransaction();
    try {
        $lookup = $pdo->prepare(
            "SELECT r.id, u.id AS user_id, u.nome, c.telefone
               FROM password_reset_requests r
               JOIN usuarios u ON u.id = r.user_id AND u.perfil = 'cliente'
               LEFT JOIN clientes c ON c.usuario_id = u.id
              WHERE r.id = ? AND r.status = 'pending'
              FOR UPDATE"
        );
        $lookup->execute([$requestId]);
        $request = $lookup->fetch();
        if (!$request) {
            throw new RuntimeException('Esta solicitação já foi atendida ou não está mais disponível.');
        }

        $phone = normalizar_telefone((string)$request['telefone']);
        if (strlen($phone) === 10 || strlen($phone) === 11) {
            $phone = '55' . $phone;
        }
        if (!preg_match('/^55\d{10,11}$/', $phone)) {
            throw new RuntimeException('O telefone/WhatsApp do motorista não está válido. Atualize o cadastro antes de gerar a senha.');
        }

        $password = bin2hex(random_bytes(12));
        $updatePassword = $pdo->prepare('UPDATE usuarios SET senha = ? WHERE id = ? AND perfil = \'cliente\'');
        $updatePassword->execute([password_hash($password, PASSWORD_DEFAULT), (int)$request['user_id']]);
        if ($updatePassword->rowCount() !== 1) {
            throw new RuntimeException('Não foi possível atualizar a senha deste motorista.');
        }

        $updateRequest = $pdo->prepare(
            "UPDATE password_reset_requests
                SET status = 'completed', handled_at = NOW(), handled_by = ?
              WHERE id = ? AND status = 'pending'"
        );
        $updateRequest->execute([$adminId, $requestId]);
        if ($updateRequest->rowCount() !== 1) {
            throw new RuntimeException('A solicitação já foi atendida por outro administrador.');
        }

        $pdo->commit();
        return [
            'name' => (string)$request['nome'],
            'phone' => $phone,
            'password' => $password,
        ];
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $error;
    }
}
