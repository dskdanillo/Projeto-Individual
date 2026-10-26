<?php
declare(strict_types=1);

/* Funções e constantes compartilhadas pelas páginas do administrador. */

const STATUS_CONTA     = ['ativo' => 'Ativo', 'inadimplente' => 'Inadimplente', 'inativo' => 'Inativo'];
const TIPOS_VEICULO    = ['Carro', 'Moto', 'Van', 'Utilitário'];
const FORMAS_PAGAMENTO = ['Pix', 'Boleto', 'Cartão', 'Transferência', 'Dinheiro'];

function badge_conta(string $status): string
{
    return '<span class="badge-status ' . h($status) . '">' . h(STATUS_CONTA[$status] ?? $status) . '</span>';
}

/** paga | atrasada | pendente (calculada: não fica gravada no banco) */
function situacao_mensalidade(array $m, string $hoje): string
{
    if (!empty($m['pago_em'])) {
        return 'paga';
    }
    return $m['vencimento'] < $hoje ? 'atrasada' : 'pendente';
}

function badge_mensalidade(string $sit): string
{
    $label = ['paga' => 'Paga', 'pendente' => 'A vencer', 'atrasada' => 'Em atraso'][$sit] ?? $sit;
    return '<span class="badge-status ' . h($sit) . '">' . h($label) . '</span>';
}

function mes_br(string $date): string
{
    return date('m/Y', strtotime($date));
}

function mes_valido(string $ym): bool
{
    return (bool)preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $ym);
}

function data_valida(string $d): bool
{
    $dt = DateTime::createFromFormat('Y-m-d', $d);
    return $dt !== false && $dt->format('Y-m-d') === $d;
}

function dias_atraso(string $vencimento, string $hoje): int
{
    return max(0, (int)floor((strtotime($hoje) - strtotime($vencimento)) / 86400));
}

/** Lê e valida os campos do formulário de cliente. @return array{0:array,1:array} [dados, erros] */
function ler_cliente_post(array $post): array
{
    $max = ['nome' => 150, 'email' => 150, 'telefone' => 30, 'cidade' => 100, 'tipo_veiculo' => 40, 'modelo_veiculo' => 80, 'placa' => 10];
    $d = [];
    foreach ($max as $k => $len) {
        $d[$k] = mb_substr(trim((string)($post[$k] ?? '')), 0, $len);
    }
    $d['email'] = mb_strtolower($d['email']);
    $d['placa'] = normalizar_placa($d['placa']);
    $d['cidade'] = formatar_cidade($d['cidade']);
    $d['telefone'] = normalizar_telefone($d['telefone']);
    $d['senha']             = (string)($post['senha'] ?? '');

    $erros = [];
    if ($d['nome'] === '') {
        $erros[] = 'Informe o nome.';
    }
    if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
        $erros[] = 'E-mail (login) inválido.';
    }
    if (strlen($d['telefone']) < 10 || strlen($d['telefone']) > 13) {
        $erros[] = 'Informe um telefone válido com DDD (10 a 13 dígitos).';
    }
    if (!cidade_valida($d['cidade'])) {
        $erros[] = 'Informe a cidade no formato CIDADE / UF, por exemplo JOINVILLE / SC.';
    }
    if (!placa_valida($d['placa'])) {
        $erros[] = 'Informe uma placa válida (modelo antigo ABC-1234 ou Mercosul ABC1D23).';
    }
    if (!in_array($d['tipo_veiculo'], TIPOS_VEICULO, true)) {
        $d['tipo_veiculo'] = 'Carro';
    }
    return [$d, $erros];
}
