
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

$dados = json_decode(file_get_contents("php://input"), true);

$destino = trim($dados["destino"] ?? "");
$valor = $dados["valor"] ?? 0;
$descricao = trim($dados["descricao"] ?? "");

if ($destino === "" || $descricao === "" || !is_numeric($valor) || $valor <= 0) {
    http_response_code(400);
    echo json_encode(["sucesso" => false, "erro" => "Preencha os campos corretamente."]);
    exit;
}

$despesa = [
    "destino" => $destino,
    "valor" => (float) $valor,
    "descricao" => $descricao
];

$resultado = supabaseRequest(
    "tb_despesa?select=*",
    "POST",
    $despesa,
    $token
);

if (!$resultado["sucesso"]) {
    http_response_code($resultado["status"] ?: 500);
    echo json_encode([
        "sucesso" => false,
        "erro" => $resultado["dados"]["message"] ?? "Erro ao registrar despesa."
    ]);
    exit;
}

echo json_encode([
    "sucesso" => true,
    "mensagem" => "Despesa registrada com sucesso!",
    "dados" => $resultado["dados"]
]);
