<?php
require __DIR__ . '/../../app/bootstrap.php';
require_once APP_PATH . '/lib/admin.php';
$u = require_login('admin');
$pdo = db();
$idioma = (string)($_GET['idioma'] ?? 'pt');
if (!in_array($idioma, ['pt', 'en', 'es'], true)) {
    $idioma = 'pt';
}
$traducoesPdf = [
    'en' => [
        'Gestão de Custo' => 'Cost Management',
        'Relatório geral de clientes' => 'General client report',
        'Situação das contas e dados principais dos clientes cadastrados' => 'Account status and key details for registered clients',
        'Gerado em' => 'Generated on',
        'Clientes cadastrados' => 'Registered clients',
        'Contas ativas' => 'Active accounts',
        'Inadimplentes' => 'Overdue',
        'Contas inativas' => 'Inactive accounts',
        'Cliente' => 'Client',
        'Cidade' => 'City',
        'Telefone' => 'Phone',
        'Situação da conta' => 'Account status',
        'Mensalidade' => 'Subscription',
        'Venc.' => 'Due',
        'Nenhum cliente cadastrado.' => 'No clients registered.',
        'Ativo' => 'Active',
        'Inadimplente' => 'Overdue',
        'Inativo' => 'Inactive',
        'Relatório administrativo • DSK Gestão de Custo' => 'Administrative report • DSK Cost Management',
        'Página' => 'Page',
    ],
    'es' => [
        'Gestão de Custo' => 'Gestión de Costos',
        'Relatório geral de clientes' => 'Informe general de clientes',
        'Situação das contas e dados principais dos clientes cadastrados' => 'Estado de las cuentas y datos principales de los clientes registrados',
        'Gerado em' => 'Generado el',
        'Clientes cadastrados' => 'Clientes registrados',
        'Contas ativas' => 'Cuentas activas',
        'Inadimplentes' => 'Morosos',
        'Contas inativas' => 'Cuentas inactivas',
        'Cliente' => 'Cliente',
        'Cidade' => 'Ciudad',
        'Telefone' => 'Teléfono',
        'Situação da conta' => 'Estado de la cuenta',
        'Mensalidade' => 'Cuota',
        'Venc.' => 'Venc.',
        'Nenhum cliente cadastrado.' => 'No hay clientes registrados.',
        'Ativo' => 'Activo',
        'Inadimplente' => 'Moroso',
        'Inativo' => 'Inactivo',
        'Relatório administrativo • DSK Gestão de Custo' => 'Informe administrativo • DSK Gestión de Costos',
        'Página' => 'Página',
    ],
];
$t = static fn(string $valor): string => $traducoesPdf[$idioma][$valor] ?? $valor;

$clientes = $pdo->query("SELECT u.nome, u.status, c.cidade, c.telefone, c.valor_mensalidade, c.dia_vencimento
                           FROM usuarios u
                           LEFT JOIN clientes c ON c.usuario_id = u.id
                          WHERE u.perfil = 'cliente'
                          ORDER BY CASE u.status
                                   WHEN 'inadimplente' THEN 0
                                   WHEN 'inativo' THEN 1
                                   ELSE 2
                                   END, u.nome")->fetchAll();

$resumo = array_fill_keys(array_keys(STATUS_CONTA), 0);
foreach ($clientes as $cliente) {
    if (isset($resumo[$cliente['status']])) {
        $resumo[$cliente['status']]++;
    }
}

$largura = 842;
$altura = 595;
$conteudo = '';
$escape = static function (string $texto): string {
    $textoPdf = iconv('UTF-8', 'Windows-1252//TRANSLIT', $texto);
    if ($textoPdf === false) {
        throw new RuntimeException('Não foi possível converter o texto do relatório para PDF.');
    }
    return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $textoPdf);
};
$texto = static function (string $valor, float $x, float $topo, int $tamanho = 9, bool $negrito = false, array $cor = [0.12, 0.17, 0.27]) use (&$conteudo, $escape, $altura): void {
    $fonte = $negrito ? 'F2' : 'F1';
    $y = $altura - $topo - $tamanho;
    $conteudo .= sprintf("%.3f %.3f %.3f rg BT /%s %d Tf 1 0 0 1 %.2f %.2f Tm (%s) Tj ET\n", $cor[0], $cor[1], $cor[2], $fonte, $tamanho, $x, $y, $escape($valor));
};
$retangulo = static function (float $x, float $topo, float $w, float $h, array $cor) use (&$conteudo, $altura): void {
    $y = $altura - $topo - $h;
    $conteudo .= sprintf("q %.3f %.3f %.3f rg %.2f %.2f %.2f %.2f re f Q\n", $cor[0], $cor[1], $cor[2], $x, $y, $w, $h);
};
$limitar = static function (?string $valor, int $limite): string {
    $valor = trim((string)$valor);
    if (mb_strlen($valor, 'UTF-8') > $limite) {
        return mb_substr($valor, 0, $limite - 1, 'UTF-8') . '...';
    }
    return $valor !== '' ? $valor : '-';
};
$total = count($clientes);
$porPagina = 14;
$paginas = max(1, (int)ceil($total / $porPagina));
$objetos = [];
$objetos[1] = '<< /Type /Catalog /Pages 2 0 R >>';
$objetos[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
$objetos[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';
$idsPaginas = [];

for ($pagina = 0; $pagina < $paginas; $pagina++) {
    $idPagina = 5 + $pagina;
    $idConteudo = 5 + $paginas + $pagina;
    $idsPaginas[] = $idPagina . ' 0 R';
    $conteudo = '';

    $retangulo(0, 0, $largura, 112, [0.07, 0.12, 0.23]);
    $retangulo(32, 111, 778, 3, [0.83, 0.67, 0.37]);
    $texto('DSK  |  ' . mb_strtoupper($t('Gestão de Custo'), 'UTF-8'), 32, 20, 9, true, [1, 1, 1]);
    $texto($t('Relatório geral de clientes'), 32, 43, 22, true, [1, 1, 1]);
    $texto($t('Situação das contas e dados principais dos clientes cadastrados'), 32, 76, 10, false, [0.81, 0.86, 0.94]);
    $texto($t('Gerado em') . ' ' . date('d/m/Y H:i'), 632, 23, 9, false, [0.90, 0.92, 0.96]);

    $cartoes = [
        [$t('Clientes cadastrados'), (string)$total, [0.93, 0.95, 0.98], [0.12, 0.17, 0.27]],
        [$t('Contas ativas'), (string)$resumo['ativo'], [0.89, 0.96, 0.92], [0.12, 0.42, 0.29]],
        [$t('Inadimplentes'), (string)$resumo['inadimplente'], [0.99, 0.95, 0.87], [0.58, 0.37, 0.08]],
        [$t('Contas inativas'), (string)$resumo['inativo'], [0.98, 0.91, 0.91], [0.58, 0.22, 0.22]],
    ];
    foreach ($cartoes as $indice => [$rotulo, $valor, $fundo, $corTexto]) {
        $x = 32 + $indice * 197;
        $retangulo($x, 130, 185, 56, $fundo);
        $texto($rotulo, $x + 12, 141, 9, false, $corTexto);
        $texto($valor, $x + 12, 157, 17, true, $corTexto);
    }

    $topoTabela = 204;
    $retangulo(32, $topoTabela, 778, 26, [0.10, 0.16, 0.29]);
    $colunas = [
        [$t('Cliente'), 32, 210, 38],
        [$t('Cidade'), 242, 170, 30],
        [$t('Telefone'), 412, 125, 23],
        [$t('Situação da conta'), 537, 115, 22],
        [$t('Mensalidade'), 652, 100, 18],
        [$t('Venc.'), 752, 58, 8],
    ];
    foreach ($colunas as [$titulo, $x, $w]) {
        $texto($titulo, $x + 8, $topoTabela + 8, 8, true, [1, 1, 1]);
    }

    $linhas = array_slice($clientes, $pagina * $porPagina, $porPagina);
    if (!$linhas) {
        $texto($t('Nenhum cliente cadastrado.'), 42, $topoTabela + 43, 10, false, [0.39, 0.43, 0.51]);
    }
    foreach ($linhas as $indice => $cliente) {
        $topoLinha = $topoTabela + 26 + $indice * 22;
        if ($indice % 2 === 0) {
            $retangulo(32, $topoLinha, 778, 22, [0.96, 0.97, 0.98]);
        }
        $retangulo(32, $topoLinha + 21, 778, 0.5, [0.86, 0.88, 0.91]);
        $texto($limitar($cliente['nome'], 38), 40, $topoLinha + 7, 8, true);
        $texto($limitar($cliente['cidade'], 30), 250, $topoLinha + 7, 8);
        $texto($limitar($cliente['telefone'], 23), 420, $topoLinha + 7, 8);

        $status = (string)$cliente['status'];
        $labelStatus = $t(STATUS_CONTA[$status] ?? $status);
        $coresStatus = [
            'ativo' => [[0.89, 0.96, 0.92], [0.12, 0.42, 0.29]],
            'inadimplente' => [[0.99, 0.95, 0.87], [0.58, 0.37, 0.08]],
            'inativo' => [[0.98, 0.91, 0.91], [0.58, 0.22, 0.22]],
        ];
        [$fundoStatus, $textoStatus] = $coresStatus[$status] ?? [[0.93, 0.95, 0.98], [0.12, 0.17, 0.27]];
        $retangulo(545, $topoLinha + 4, 97, 14, $fundoStatus);
        $texto($limitar($labelStatus, 19), 550, $topoLinha + 7, 7, true, $textoStatus);
        $valorMensalidade = 'R$ ' . number_format((float)($cliente['valor_mensalidade'] ?? 0), 2, ',', '.');
        $texto($valorMensalidade, 660, $topoLinha + 7, 8);
        $texto((string)($cliente['dia_vencimento'] ?? '-'), 760, $topoLinha + 7, 8);
    }

    $texto($t('Relatório administrativo • DSK Gestão de Custo'), 32, 572, 8, false, [0.42, 0.46, 0.54]);
    $texto($t('Página') . ' ' . ($pagina + 1) . ' ' . ($idioma === 'en' ? 'of' : 'de') . ' ' . $paginas, 746, 572, 8, false, [0.42, 0.46, 0.54]);

    $objetos[$idPagina] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ' . $largura . ' ' . $altura . '] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents ' . $idConteudo . ' 0 R >>';
    $objetos[$idConteudo] = '<< /Length ' . strlen($conteudo) . " >>\nstream\n" . $conteudo . "endstream";
}

$objetos[2] = '<< /Type /Pages /Kids [' . implode(' ', $idsPaginas) . '] /Count ' . $paginas . ' >>';
ksort($objetos);
$pdf = "%PDF-1.4\n";
$deslocamentos = [0];
foreach ($objetos as $id => $objeto) {
    $deslocamentos[$id] = strlen($pdf);
    $pdf .= $id . " 0 obj\n" . $objeto . "\nendobj\n";
}
$inicioXref = strlen($pdf);
$pdf .= "xref\n0 " . (count($objetos) + 1) . "\n0000000000 65535 f \n";
for ($id = 1; $id <= count($objetos); $id++) {
    $pdf .= sprintf("%010d 00000 n \n", $deslocamentos[$id]);
}
$pdf .= "trailer\n<< /Size " . (count($objetos) + 1) . " /Root 1 0 R >>\nstartxref\n" . $inicioXref . "\n%%EOF";

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="relatorio-clientes-' . date('Y-m-d') . '.pdf"');
header('Content-Length: ' . strlen($pdf));
echo $pdf;
