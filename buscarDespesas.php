
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

$resultado = supabaseRequest(
    "tb_despesa?select=*&order=data_despesa.desc",
    "GET",
    null,
    $token
);

if (!$resultado["sucesso"]) {
    http_response_code($resultado["status"] ?: 500);
    echo json_encode([
        "sucesso" => false,
        "erro" => $resultado["dados"]["message"] ?? "Erro ao buscar despesas."
    ]);
    exit;
}

echo json_encode([
    "sucesso" => true,
    "dados" => $resultado["dados"] ?? []
]);
