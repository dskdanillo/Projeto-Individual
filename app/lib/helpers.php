<?php
declare(strict_types=1);

function h(mixed $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function brl(float|int|string|null $v): string
{
    return 'R$ ' . number_format((float)$v, 2, ',', '.');
}

function date_br(string $d): string
{
    return date('d/m/Y', strtotime($d));
}

function normalizar_placa(string $placa): string
{
    return strtoupper((string)preg_replace('/[\s-]+/', '', trim($placa)));
}

function placa_valida(string $placa): bool
{
    return $placa === '' || (bool)preg_match('/^[A-Z]{3}(?:[0-9]{4}|[0-9][A-Z][0-9]{2})$/', $placa);
}

function normalizar_telefone(string $telefone): string
{
    return (string)preg_replace('/\D+/', '', $telefone);
}

function formatar_cidade(string $cidade): string
{
    $cidade = mb_strtoupper(trim((string)preg_replace('/\s+/u', ' ', $cidade)));
    return (string)preg_replace('/\s*\/\s*/u', ' / ', $cidade);
}

function cidade_valida(string $cidade): bool
{
    return (bool)preg_match('/^[\p{Lu}\p{M}][\p{Lu}\p{M} .\'-]* \/ [A-Z]{2}$/u', $cidade);
}

/** Converte "1.234,56" ou "3.5" em float. */
function num_in(?string $s): float
{
    $s = trim((string)$s);
    if (str_contains($s, ',')) {
        $s = str_replace('.', '', $s);
    }
    $s = str_replace(',', '.', $s);
    return is_numeric($s) ? (float)$s : 0.0;
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function flash(string $type, string $msg): void
{
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg];
}

function flash_render(): string
{
    $out = '';
    foreach ($_SESSION['flash'] ?? [] as $f) {
        $out .= '<div class="alert alert-' . h($f['type']) . ' py-2 px-3" style="font-size:13.5px;border-radius:10px;">' . h($f['msg']) . '</div>';
    }
    unset($_SESSION['flash']);
    return $out;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '">';
}

function csrf_check(): void
{
    $ok = isset($_POST['csrf']) && hash_equals($_SESSION['csrf'] ?? '', (string)$_POST['csrf']);
    if (!$ok) {
        http_response_code(419);
        exit('Sessão expirada. Volte e tente novamente.');
    }
}

/** @return array{0:string,1:string} [inicio, fim] */
function periodo_range(string $tipo): array
{
    $hoje = date('Y-m-d');
    return match ($tipo) {
        'dia'   => [$hoje, $hoje],
        'ano'   => [date('Y-01-01'), $hoje],
        default => [date('Y-m-01'), $hoje],
    };
}

function periodo_label(string $tipo): string
{
    return ['dia' => 'Hoje', 'mes' => 'Este mês', 'ano' => 'Este ano'][$tipo] ?? 'Este mês';
}

/** @return array{tipo:string,dia:string,mes:string,ano:string,inicio:string,fim:string,de:?string,ate:?string} */
function ler_filtro_periodo(array $input, string $padrao = 'todos'): array
{
    $hoje = date('Y-m-d');
    $tipo = (string)($input['periodo'] ?? $padrao);
    if (!in_array($tipo, ['todos', 'dia', 'mes', 'ano', 'intervalo'], true)) {
        throw new InvalidArgumentException('Selecione um período válido.');
    }

    $filtro = [
        'tipo' => $tipo,
        'dia' => $hoje,
        'mes' => date('Y-m'),
        'ano' => date('Y'),
        'inicio' => date('Y-m-01'),
        'fim' => $hoje,
        'de' => null,
        'ate' => null,
    ];

    if ($tipo === 'todos') {
        return $filtro;
    }

    if ($tipo === 'dia') {
        $dia = (string)($input['dia'] ?? $hoje);
        if (!data_iso_valida($dia)) {
            throw new InvalidArgumentException('Informe uma data válida.');
        }
        $filtro['dia'] = $dia;
        $filtro['de'] = $dia;
        $filtro['ate'] = $dia;
    } elseif ($tipo === 'mes') {
        $mes = (string)($input['mes'] ?? date('Y-m'));
        if (!preg_match('/^(\d{4})-(0[1-9]|1[0-2])$/', $mes)) {
            throw new InvalidArgumentException('Informe um mês válido.');
        }
        $filtro['mes'] = $mes;
        $filtro['de'] = $mes . '-01';
        $filtro['ate'] = date('Y-m-t', strtotime($filtro['de']));
    } elseif ($tipo === 'ano') {
        $ano = (string)($input['ano'] ?? date('Y'));
        if (!preg_match('/^\d{4}$/', $ano) || (int)$ano < 1900) {
            throw new InvalidArgumentException('Informe um ano válido.');
        }
        $filtro['ano'] = $ano;
        $filtro['de'] = $ano . '-01-01';
        $filtro['ate'] = $ano . '-12-31';
    } else {
        $inicio = (string)($input['inicio'] ?? date('Y-m-01'));
        $fim = (string)($input['fim'] ?? $hoje);
        if (!data_iso_valida($inicio) || !data_iso_valida($fim)) {
            throw new InvalidArgumentException('Informe as datas inicial e final corretamente.');
        }
        if ($inicio > $fim) {
            throw new InvalidArgumentException('A data inicial não pode ser posterior à data final.');
        }
        $filtro['inicio'] = $inicio;
        $filtro['fim'] = $fim;
        $filtro['de'] = $inicio;
        $filtro['ate'] = $fim;
    }

    return $filtro;
}

function data_iso_valida(string $data): bool
{
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $data, $partes)) {
        return false;
    }
    return checkdate((int)$partes[2], (int)$partes[3], (int)$partes[1]);
}
