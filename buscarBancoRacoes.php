
<?php
require_once 'conexao.php';

header('Content-Type: application/json; charset=utf-8');

$token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

if (strpos($token, 'Bearer ') === 0) {
    $token = substr($token, 7);
}

if (!$token) {
    http_response_code(401);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Usuário não autenticado.'
    ]);
    exit;
}

$resposta = supabaseRequest(
    'tb_banco_racoes?select=*&order=data_cadastro.desc',
    'GET',
    null,
    $token
);

if (
    !empty($resposta['sucesso']) &&
    is_array($resposta['dados'])
) {

    // Busca os interesses do usuário conectado
    $interesses = supabaseRequest(
        'tb_interesse_racao?select=id_banco',
        'GET',
        null,
        $token
    );

    $idsInteresses = array_map(
        'intval',
        array_column($interesses['dados'] ?? [], 'id_banco')
    );

    foreach ($resposta['dados'] as &$item) {

        $item['interesse_enviado'] = in_array(
            (int)$item['id_banco'],
            $idsInteresses,
            true
        );
        $perfil = supabaseRequest(
            'tb_perfil_usuario?select=nome,razao_social&id_usuario=eq.' .
            rawurlencode($item['id_ong']) .
            '&limit=1',
            'GET',
            null,
            $token
        );

        $dadosPerfil = $perfil['dados'] ?? [];

        $item['nome_ong'] = $dadosPerfil[0]['razao_social']
            ?? $dadosPerfil[0]['nome']
            ?? 'ONG';
    }

    unset($item);
}

http_response_code($resposta['status'] ?? 500);
echo json_encode($resposta);