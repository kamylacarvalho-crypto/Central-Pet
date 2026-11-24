
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

$id = $_GET['id'] ?? null;

if (!$id || !ctype_digit((string)$id)) {
    http_response_code(400);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'ID inválido.'
    ]);
    exit;
}

$resposta = supabaseRequest(
    'tb_banco_racoes?id_banco=eq.' . $id,
    'DELETE',
    null,
    $token
);

http_response_code($resposta['status'] ?? 500);
echo json_encode($resposta);
