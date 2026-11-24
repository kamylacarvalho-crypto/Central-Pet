
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
    'tb_interesse_racao?select=*&order=data_interesse.desc',
    'GET',
    null,
    $token
);

if (empty($resposta['sucesso'])) {
    http_response_code($resposta['status'] ?? 500);
    echo json_encode($resposta);
    exit;
}

$interesses = $resposta['dados'] ?? [];

foreach ($interesses as &$interesse) {

    $idBanco = $interesse['id_banco'];
    $idUsuario = $interesse['id_usuario'];

    $banco = supabaseRequest(
        'tb_banco_racoes?select=tipo_racao,quantidade,unidade,id_ong&id_banco=eq.'.
        $idBanco . '&limit=1',
        'GET',
        null,
        $token
    );

    $perfil = supabaseRequest(
        'tb_perfil_usuario?select=nome&id_usuario=eq.' .
        rawurlencode($idUsuario) . '&limit=1',
        'GET',
        null,
        $token
    );

    $dadosBanco = $banco['dados'][0] ?? [];
    $dadosPerfil = $perfil['dados'][0] ?? [];

    $interesse['tipo_racao'] = $dadosBanco['tipo_racao'] ?? '';
    $interesse['quantidade'] = $dadosBanco['quantidade'] ?? '';
    $interesse['unidade'] = $dadosBanco['unidade'] ?? '';
    $interesse['nome_usuario'] = $dadosPerfil['nome'] ?? 'Nome não informado';
    $interesse['id_ong'] = $dadosBanco['id_ong'] ?? '';
}

unset($interesse);

echo json_encode([
    'sucesso' => true,
    'dados' => $interesses
]);
