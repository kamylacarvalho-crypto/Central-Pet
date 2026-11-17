
<?php

header("Content-Type: application/json; charset=UTF-8");
require_once "conexao.php";

$headers = function_exists("getallheaders") ? getallheaders() : [];

$autorizacao =
    $headers["Authorization"]
    ?? $headers["authorization"]
    ?? $_SERVER["HTTP_AUTHORIZATION"]
    ?? "";

if (!preg_match('/Bearer\s+(.+)/i', $autorizacao, $matches)) {
    http_response_code(401);
    echo json_encode(["sucesso" => false, "erro" => "Usuário não autenticado."]);
    exit;
}

$token = trim($matches[1]);

$entrada = json_decode(file_get_contents("php://input"), true);

$idTriagem = filter_var($entrada["id_triagem"] ?? null, FILTER_VALIDATE_INT);
$categoria = trim($entrada["categoria"] ?? "");
$subcategoria = trim($entrada["subcategoria"] ?? "");
$prioridade = trim($entrada["prioridade"] ?? "");

$categorias = [
    "Adoção" => "#16A34A",
    "Denúncia" => "#E00808",
    "Doação" => "#2563EB",
    "Pedido de ajuda" => "#F59E0B",
    "Atendimento geral" => "#6B7280"
];

if (
    !$idTriagem ||
    !isset($categorias[$categoria]) ||
    $subcategoria === "" ||
    !in_array($prioridade, ["alta", "media", "baixa"], true)
) {
    http_response_code(400);
    echo json_encode(["sucesso" => false, "erro" => "Dados da triagem inválidos."]);
    exit;
}

$dados = [
    "selo_categoria" => $categoria,
    "selo_cor" => $categorias[$categoria],
    "subcategoria" => $subcategoria,
    "prioridade" => $prioridade,
    "analisado_em" => gmdate("c")
];

$resultado = supabaseRequest(
    "tb_triagem_ia?id_triagem=eq." . $idTriagem . "&select=id_triagem",
    "PATCH",
    $dados,
    $token
);

if (!$resultado["sucesso"] || empty($resultado["dados"])) {
    http_response_code(500);
    echo json_encode([
        "sucesso" => false,
        "erro" => "Não foi possível atualizar a triagem.",
        "detalhes" => $resultado["resposta_original"] ?? null
    ]);
    exit;
}

echo json_encode([
    "sucesso" => true,
    "mensagem" => "Triagem atualizada com sucesso."
], JSON_UNESCAPED_UNICODE);

?>
