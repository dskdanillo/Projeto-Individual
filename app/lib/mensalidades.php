<?php
declare(strict_types=1);

function gerar_mensalidades(PDO $pdo, string $mes, ?int $clienteId = null, ?string $ateData = null): int
{
    if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $mes)) {
        throw new InvalidArgumentException('Mês de referência inválido.');
    }

    $referencia = $mes . '-01';
    $sql = "INSERT IGNORE INTO mensalidades (cliente_id, referencia, valor, vencimento)
            SELECT c.id, CAST(? AS DATE), c.valor_mensalidade,
                   DATE_ADD(CAST(? AS DATE), INTERVAL LEAST(c.dia_vencimento, DAY(LAST_DAY(CAST(? AS DATE)))) - 1 DAY)
              FROM clientes c
              JOIN usuarios u ON u.id = c.usuario_id
             WHERE u.perfil = 'cliente'
               AND u.status IN ('ativo', 'inadimplente')
               AND c.valor_mensalidade > 0";
    $params = [$referencia, $referencia, $referencia];

    if ($clienteId !== null) {
        $sql .= ' AND c.id = ?';
        $params[] = $clienteId;
    }

    if ($ateData !== null) {
        $sql .= " AND DATE_ADD(CAST(? AS DATE), INTERVAL LEAST(c.dia_vencimento, DAY(LAST_DAY(CAST(? AS DATE)))) - 1 DAY) <= CAST(? AS DATE)";
        array_push($params, $referencia, $referencia, $ateData);
    }

    $st = $pdo->prepare($sql);
    $st->execute($params);
    return $st->rowCount();
}
