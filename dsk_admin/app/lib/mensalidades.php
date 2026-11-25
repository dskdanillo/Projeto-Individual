<?php
declare(strict_types=1);

function proximo_fim_ciclo(string $inicio, int $diaAncoragem): string
{
    $data = DateTimeImmutable::createFromFormat('!Y-m-d', $inicio);
    if (!$data) {
        throw new InvalidArgumentException('Data de início do ciclo inválida.');
    }

    $proximoMes = $data->modify('first day of next month');
    $dia = min($diaAncoragem, (int)$proximoMes->format('t'));
    return $proximoMes->setDate(
        (int)$proximoMes->format('Y'),
        (int)$proximoMes->format('n'),
        $dia
    )->format('Y-m-d');
}

function inicio_ciclo_atual(string $inicio, int $diaAncoragem, string $hoje): string
{
    $cursor = $inicio;
    while (($proximo = proximo_fim_ciclo($cursor, $diaAncoragem)) <= $hoje) {
        $cursor = $proximo;
    }
    return $cursor;
}

function valor_proporcional(float $valorMensal, string $inicio, string $fim, string $data): float
{
    $inicioData = new DateTimeImmutable($inicio);
    $fimData = new DateTimeImmutable($fim);
    $dataAtual = new DateTimeImmutable($data);
    $diasCiclo = max(1, (int)$inicioData->diff($fimData)->days);
    $diasUsados = min($diasCiclo, max(0, (int)$inicioData->diff($dataAtual)->days));

    return round($valorMensal * $diasUsados / $diasCiclo, 2);
}

function migrar_cobrancas_ciclo(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS app_schema_migrations (
        version VARCHAR(100) NOT NULL PRIMARY KEY,
        applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    if (!$pdo->query("SHOW COLUMNS FROM mensalidades LIKE 'periodo_fim'")->fetchColumn()) {
        $pdo->exec('ALTER TABLE mensalidades ADD COLUMN periodo_fim DATE NULL AFTER referencia');
    }
    if (!$pdo->query("SHOW COLUMNS FROM mensalidades LIKE 'tipo'")->fetchColumn()) {
        $pdo->exec("ALTER TABLE mensalidades ADD COLUMN tipo ENUM('mensal','proporcional') NOT NULL DEFAULT 'mensal' AFTER periodo_fim");
    }
    if (!$pdo->query("SHOW COLUMNS FROM clientes LIKE 'inicio_ciclo'")->fetchColumn()) {
        $pdo->exec('ALTER TABLE clientes ADD COLUMN inicio_ciclo DATE DEFAULT NULL AFTER dia_vencimento');
    }
    $mensalidade = $pdo->query("SHOW COLUMNS FROM clientes LIKE 'valor_mensalidade'")->fetch();
    if ($mensalidade && (float)$mensalidade['Default'] !== 29.0) {
        $pdo->exec('ALTER TABLE clientes ALTER COLUMN valor_mensalidade SET DEFAULT 29.00');
    }

    $pdo->beginTransaction();
    try {
        $claim = $pdo->prepare("INSERT IGNORE INTO app_schema_migrations (version) VALUES ('usage-cycle-29-v1')");
        $claim->execute();
        if ($claim->rowCount() === 0) {
            $pdo->commit();
            return;
        }

        $pdo->exec('UPDATE mensalidades SET periodo_fim = vencimento WHERE periodo_fim IS NULL');
        $pdo->exec('UPDATE clientes SET valor_mensalidade = 29.00');

        $hoje = date('Y-m-d');
        $clientes = $pdo->query("SELECT c.id,
                                        COALESCE(DATE(u.criado_em), DATE(c.criado_em), CURRENT_DATE) AS inicio,
                                        DAY(COALESCE(u.criado_em, c.criado_em, CURRENT_TIMESTAMP)) AS dia
                                   FROM clientes c
                                   JOIN usuarios u ON u.id = c.usuario_id
                                  WHERE u.perfil = 'cliente'")->fetchAll();
        $cobrancas = $pdo->prepare('SELECT id, pago_em FROM mensalidades WHERE cliente_id = ? ORDER BY vencimento, id');
        $atualizarPaga = $pdo->prepare('UPDATE mensalidades SET referencia = ?, periodo_fim = ? WHERE id = ?');
        $atualizarPendente = $pdo->prepare('UPDATE mensalidades
                                               SET referencia = ?, periodo_fim = ?, vencimento = ?, valor = 29.00
                                             WHERE id = ? AND pago_em IS NULL');
        $atualizarInicio = $pdo->prepare('UPDATE clientes SET inicio_ciclo = ? WHERE id = ?');

        foreach ($clientes as $cliente) {
            $cursor = (string)$cliente['inicio'];
            $diaAncoragem = (int)$cliente['dia'];
            $cobrancas->execute([(int)$cliente['id']]);
            $registros = $cobrancas->fetchAll();
            foreach ($registros as $cobranca) {
                $fim = proximo_fim_ciclo($cursor, $diaAncoragem);
                if ($cobranca['pago_em'] === null) {
                    $atualizarPendente->execute([$cursor, $fim, $fim, (int)$cobranca['id']]);
                } else {
                    $atualizarPaga->execute([$cursor, $fim, (int)$cobranca['id']]);
                }
                $cursor = $fim;
            }
            $inicioCicloAtual = inicio_ciclo_atual((string)$cliente['inicio'], $diaAncoragem, $hoje);
            if ($cursor < $inicioCicloAtual) {
                $cursor = $inicioCicloAtual;
            }
            $atualizarInicio->execute([$cursor, (int)$cliente['id']]);
        }

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function gerar_mensalidades(PDO $pdo, string $hoje, ?int $clienteId = null): int
{
    $dataHoje = DateTimeImmutable::createFromFormat('!Y-m-d', $hoje);
    if (!$dataHoje || $dataHoje->format('Y-m-d') !== $hoje) {
        throw new InvalidArgumentException('Data de geração inválida.');
    }

    $sql = "SELECT c.id, c.valor_mensalidade,
                   COALESCE(c.inicio_ciclo, DATE(u.criado_em), DATE(c.criado_em), CURRENT_DATE) AS inicio,
                   DAY(COALESCE(u.criado_em, c.criado_em, CURRENT_TIMESTAMP)) AS dia
              FROM clientes c
              JOIN usuarios u ON u.id = c.usuario_id
             WHERE u.perfil = 'cliente'
               AND u.status IN ('ativo', 'inadimplente')
               AND c.valor_mensalidade > 0";
    $params = [];
    if ($clienteId !== null) {
        $sql .= ' AND c.id = ?';
        $params[] = $clienteId;
    }

    $clientes = $pdo->prepare($sql);
    $clientes->execute($params);
    $ultimaData = $pdo->prepare('SELECT MAX(periodo_fim) FROM mensalidades WHERE cliente_id = ?');
    $inserir = $pdo->prepare('INSERT IGNORE INTO mensalidades
        (cliente_id, referencia, periodo_fim, tipo, valor, vencimento)
        VALUES (?, ?, ?, \'mensal\', ?, ?)');
    $geradas = 0;

    foreach ($clientes->fetchAll() as $cliente) {
        $clienteIdAtual = (int)$cliente['id'];
        $inicio = (string)$cliente['inicio'];
        $diaAncoragem = (int)$cliente['dia'];
        $ultimaData->execute([$clienteIdAtual]);
        $cursor = (string)($ultimaData->fetchColumn() ?: $inicio);

        while (true) {
            $fim = proximo_fim_ciclo($cursor, $diaAncoragem);
            if ($fim > $hoje) {
                break;
            }
            $inserir->execute([$clienteIdAtual, $cursor, $fim, (float)$cliente['valor_mensalidade'], $fim]);
            if ($inserir->rowCount() === 0) {
                break;
            }
            $geradas++;
            $cursor = $fim;
        }
    }

    return $geradas;
}

function gerar_cobranca_cancelamento(
    PDO $pdo,
    int $clienteId,
    string $inicioAssinatura,
    int $diaAncoragem,
    float $valorMensal,
    string $hoje
): float {
    $aberta = $pdo->prepare('SELECT id, referencia, periodo_fim
                               FROM mensalidades
                              WHERE cliente_id = ? AND pago_em IS NULL
                                AND referencia <= ? AND periodo_fim > ?
                              ORDER BY periodo_fim LIMIT 1');
    $aberta->execute([$clienteId, $hoje, $hoje]);
    $cobranca = $aberta->fetch();

    $removerFuturas = $pdo->prepare('DELETE FROM mensalidades
                                      WHERE cliente_id = ? AND pago_em IS NULL AND referencia > ?');

    if ($cobranca) {
        $inicio = (string)$cobranca['referencia'];
        $fim = (string)$cobranca['periodo_fim'];
        $valor = valor_proporcional($valorMensal, $inicio, $fim, $hoje);
        $removerFuturas->execute([$clienteId, $inicio]);

        if ($valor <= 0) {
            $pdo->prepare('DELETE FROM mensalidades WHERE id = ? AND pago_em IS NULL')
                ->execute([(int)$cobranca['id']]);
            return 0.0;
        }

        $pdo->prepare("UPDATE mensalidades
                          SET valor = ?, periodo_fim = ?, vencimento = ?, tipo = 'proporcional'
                        WHERE id = ? AND pago_em IS NULL")
            ->execute([$valor, $hoje, $hoje, (int)$cobranca['id']]);
        return $valor;
    }

    $periodoPago = $pdo->prepare('SELECT referencia
                                    FROM mensalidades
                                   WHERE cliente_id = ? AND pago_em IS NOT NULL
                                     AND referencia <= ? AND periodo_fim > ?
                                   ORDER BY periodo_fim LIMIT 1');
    $periodoPago->execute([$clienteId, $hoje, $hoje]);
    $coberturaPaga = $periodoPago->fetchColumn();
    if ($coberturaPaga !== false) {
        $removerFuturas->execute([$clienteId, (string)$coberturaPaga]);
        return 0.0;
    }

    $ultima = $pdo->prepare('SELECT MAX(periodo_fim) FROM mensalidades WHERE cliente_id = ? AND periodo_fim <= ?');
    $ultima->execute([$clienteId, $hoje]);
    $inicio = (string)($ultima->fetchColumn() ?: $inicioAssinatura);
    $fim = proximo_fim_ciclo($inicio, $diaAncoragem);
    $valor = valor_proporcional($valorMensal, $inicio, $fim, $hoje);
    $removerFuturas->execute([$clienteId, $hoje]);

    if ($valor <= 0) {
        return 0.0;
    }

    $existente = $pdo->prepare('SELECT id, pago_em FROM mensalidades WHERE cliente_id = ? AND referencia = ?');
    $existente->execute([$clienteId, $inicio]);
    $registro = $existente->fetch();
    if ($registro) {
        if ($registro['pago_em'] !== null) {
            return 0.0;
        }
        $pdo->prepare("UPDATE mensalidades
                          SET valor = ?, periodo_fim = ?, vencimento = ?, tipo = 'proporcional'
                        WHERE id = ?")
            ->execute([$valor, $hoje, $hoje, (int)$registro['id']]);
        return $valor;
    }

    $pdo->prepare("INSERT INTO mensalidades
        (cliente_id, referencia, periodo_fim, tipo, valor, vencimento)
        VALUES (?, ?, ?, 'proporcional', ?, ?)")
        ->execute([$clienteId, $inicio, $hoje, $valor, $hoje]);

    return $valor;
}
