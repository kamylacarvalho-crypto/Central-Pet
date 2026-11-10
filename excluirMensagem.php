
<?php

header("Content-Type: application/json; charset=UTF-8");

require_once "conexao.php";

$headers = function_exists("getallheaders")
    ? getallheaders()
    : [];

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

$dados = json_decode(
    file_get_contents("php://input"),
    true
);

$idMensagem = $dados["id_mensagem"] ?? null;

if (!$idMensagem || !ctype_digit((string) $idMensagem)) {
    http_response_code(400);

    echo json_encode([
        "sucesso" => false,
        "erro" => "ID da mensagem inválido."
    ]);

    exit;
}

$resultado = supabaseRequest(
    "tb_mensagem?id_mensagem=eq." . $idMensagem,
    "PATCH",
    [
        "excluido_remetente" => true,
        "excluido_destinatario" => true
    ],
    $token
);

if (!$resultado["sucesso"]) {
    http_response_code($resultado["status"] ?: 500);

    echo json_encode([
        "sucesso" => false,
        "erro" => $resultado["resposta_original"]
            ?? "Não foi possível excluir a mensagem."
    ]);

    exit;
}

echo json_encode([
    "sucesso" => true,
    "mensagem" => "Mensagem excluída para todos."
]);

?>
