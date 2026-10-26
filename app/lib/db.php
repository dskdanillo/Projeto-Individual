<?php
declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $name = getenv('DB_NAME') ?: 'projetoindividual';
    $user = getenv('DB_USER') ?: 'dsk';
    $pass = getenv('DB_PASS') ?: 'dsk123';

    $pdo = new PDO("mysql:host=$host;dbname=$name;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
    if (!$pdo->query("SHOW COLUMNS FROM rotas LIKE 'ida_volta'")->fetchColumn()) {
        $pdo->exec('ALTER TABLE rotas ADD COLUMN ida_volta TINYINT(1) NOT NULL DEFAULT 0 AFTER km');
    }
    return $pdo;
}

/** Cria o admin e um cliente de demonstração se o banco estiver vazio. */
function seed_if_empty(): void
{
    $pdo = db();
    if ((int)$pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn() > 0) {
        return;
    }

    $ins = $pdo->prepare('INSERT INTO usuarios (nome,email,senha,perfil) VALUES (?,?,?,?)');
    $ins->execute(['Administrador DSK', 'dsk.danillo@gmail.com', password_hash('12345@54321', PASSWORD_DEFAULT), 'admin']);
    $ins->execute(['Carlos Souza', 'carlos.souza@email.com', password_hash('123456', PASSWORD_DEFAULT), 'cliente']);
    $uid = (int)$pdo->lastInsertId();

    $pdo->prepare('INSERT INTO clientes (usuario_id,telefone,cidade,tipo_veiculo,modelo_veiculo,placa) VALUES (?,?,?,?,?,?)')
        ->execute([$uid, '(47) 99999-0000', 'Joinville/SC', 'Carro', 'Fiat Strada', 'QFR4D12']);
    $cid = (int)$pdo->lastInsertId();

    $rotas = [
        ['Centro, Joinville - SC', 'Iririú, Joinville - SC', 8.5, 3.50, 1],
        ['Bucarein, Joinville - SC', 'América, Joinville - SC', 5.2, 3.50, 2],
        ['Joinville - SC', 'Jaraguá do Sul - SC', 46.0, 2.80, 3],
        ['Joinville - SC', 'São Francisco do Sul - SC', 62.0, 2.60, 5],
        ['Boa Vista, Joinville - SC', 'Aventureiro, Joinville - SC', 14.3, 3.20, 8],
    ];
    $insR = $pdo->prepare('INSERT INTO rotas (cliente_id,origem,destino,km,valor_km,valor_receber,data_rota) VALUES (?,?,?,?,?,?,?)');
    $insC = $pdo->prepare('INSERT INTO custos_rota (rota_id,categoria,descricao,valor,data_custo) VALUES (?,?,?,?,?)');

    foreach ($rotas as [$o, $d, $km, $vk, $dias]) {
        $data = date('Y-m-d', strtotime("-$dias days"));
        $insR->execute([$cid, $o, $d, $km, $vk, round($km * $vk, 2), $data]);
        $rid = (int)$pdo->lastInsertId();
        $insC->execute([$rid, 'Combustível', 'Abastecimento', round($km * 0.75, 2), $data]);
        if ($km > 40) {
            $insC->execute([$rid, 'Pedágio', 'Pedágio ida e volta', 18.40, $data]);
        }
        if ($km > 10) {
            $insC->execute([$rid, 'Alimentação', 'Lanche', 22.00, $data]);
        }
    }
}
