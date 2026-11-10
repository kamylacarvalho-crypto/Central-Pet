
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

$resultado = supabaseRequest(
    "tb_mensagem?select=*&order=data_envio.desc",
    "GET",
    null,
    $token
);

if (!$resultado["sucesso"]) {

    http_response_code($resultado["status"] ?: 500);

    echo json_encode([
        "sucesso" => false,
        "erro" => $resultado["resposta_original"] ?? "Erro ao buscar mensagens."
    ]);

    exit;
}

echo json_encode([
    "sucesso" => true,
    "mensagens" => $resultado["dados"]
]);

?>
