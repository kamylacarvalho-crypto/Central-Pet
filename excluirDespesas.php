
<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "conexao.php";

$headers = function_exists("getallheaders") ? getallheaders() : [];
$autorizacao = $headers["Authorization"] ?? $_SERVER["HTTP_AUTHORIZATION"] ?? "";
$token = "";

if (preg_match('/Bearer\s+(.+)/i', $autorizacao, $matches)) {
    $token = trim($matches[1]);
}

if (!$token) {
    http_response_code(401);
    echo json_encode(["sucesso" => false, "erro" => "Faça login novamente."]);
    exit;
}

$id = $_GET["id"] ?? "";

if (!ctype_digit((string)$id) || (int)$id <= 0) {
    http_response_code(400);
    echo json_encode(["sucesso" => false, "erro" => "Despesa inválida."]);
    exit;
}

$resultado = supabaseRequest(
    "tb_despesa?id_despesa=eq." . (int)$id,
    "DELETE",
    null,
    $token
);

if (!$resultado["sucesso"]) {
    http_response_code($resultado["status"] ?: 500);
    echo json_encode([
        "sucesso" => false,
        "erro" => $resultado["dados"]["message"] ?? "Erro ao excluir despesa."
    ]);
    exit;
}

echo json_encode(["sucesso" => true, "mensagem" => "Despesa excluída."]);
