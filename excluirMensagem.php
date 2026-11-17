
<?php

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . "/conexao.php";

// AUTENTICAÇÃO
$headers = function_exists("getallheaders") ? getallheaders() : [];

$autorizacao =
    $headers["Authorization"]
    ?? $headers["authorization"]
    ?? $_SERVER["HTTP_AUTHORIZATION"]
    ?? "";

if (!preg_match('/Bearer\s+(.+)/i', $autorizacao, $matches)) {
    http_response_code(401);
    echo json_encode([
        "sucesso" => false,
        "erro" => "Usuário não autenticado."
    ]);
    exit;
}

$token = trim($matches[1]);

// RECEBER DADOS
$dados = json_decode(file_get_contents("php://input"), true);

$idMensagem = filter_var(
    $dados["id_mensagem"] ?? null,
    FILTER_VALIDATE_INT
);

if (!$idMensagem || $idMensagem <= 0) {
    http_response_code(400);
    echo json_encode([
        "sucesso" => false,
        "erro" => "ID da mensagem inválido."
    ]);
    exit;
}

// EXCLUSÃO SOMENTE DA CAIXA DA ONG
$somenteDestinatario =
    ($dados["somente_destinatario"] ?? false) === true;

$camposExclusao = $somenteDestinatario
    ? [
        "excluido_destinatario" => true
    ]
    : [
        "excluido_remetente" => true,
        "excluido_destinatario" => true
    ];

// ATUALIZAR SUPABASE
$resultado = supabaseRequest(
    "tb_mensagem?id_mensagem=eq." . $idMensagem,
    "PATCH",
    $camposExclusao,
    $token
);

if (!$resultado["sucesso"]) {
    http_response_code($resultado["status"] ?: 500);

    echo json_encode([
        "sucesso" => false,
        "erro" => "Não foi possível excluir a mensagem.",
        "detalhes" => $resultado["resposta_original"] ?? null
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

echo json_encode([
    "sucesso" => true,
    "mensagem" => "Mensagem removida da caixa da ONG."
], JSON_UNESCAPED_UNICODE);

?>
