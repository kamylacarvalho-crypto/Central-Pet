
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

$dados = json_decode(file_get_contents('php://input'), true);

if (
    empty($dados['tipo_racao']) ||
    !isset($dados['quantidade']) ||
    empty($dados['unidade']) ||
    empty($dados['local_entrega'])
) {
    http_response_code(400);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Preencha todos os campos obrigatórios.'
    ]);
    exit;
}

$dadosSalvar = [
    'tipo_racao' => $dados['tipo_racao'],
    'quantidade' => $dados['quantidade'],
    'unidade' => $dados['unidade'],
    'local_entrega' => $dados['local_entrega'],
    'descricao' => $dados['descricao'] ?? null,
    'status' => 'Aberta'
];

$resposta = supabaseRequest(
    'tb_banco_racoes',
    'POST',
    $dadosSalvar,
    $token
);

http_response_code($resposta['status'] ?? 500);
echo json_encode($resposta);
