
<?php

header("Content-Type: application/json; charset=UTF-8");
require_once "conexao.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode([
        "sucesso" => false,
        "erro" => "Método não permitido."
    ]);
    exit;
}

$headers = function_exists("getallheaders") ? getallheaders() : [];
$autorizacao = $headers["Authorization"]
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
$dados = json_decode(file_get_contents("php://input"), true);

$idAnimal = $dados["id_animal"] ?? "";
$titulo = trim($dados["titulo"] ?? "");
$historia = trim($dados["historia"] ?? "");
$idDespesa = $dados["id_despesa"] ?? null;

if (
    !ctype_digit((string)$idAnimal) ||
    (int)$idAnimal <= 0 ||
    $titulo === "" ||
    $historia === ""
) {
    http_response_code(400);
    echo json_encode([
        "sucesso" => false,
        "erro" => "Preencha todos os campos obrigatórios."
    ]);
    exit;
}

$novaHistoria = [
    "id_animal" => (int)$idAnimal,
    "titulo" => $titulo,
    "historia" => $historia
];

if ($idDespesa !== null && $idDespesa !== "") {
    if (!ctype_digit((string)$idDespesa) || (int)$idDespesa <= 0) {
        http_response_code(400);
        echo json_encode([
            "sucesso" => false,
            "erro" => "Despesa inválida."
        ]);
        exit;
    }

    $novaHistoria["id_despesa"] = (int)$idDespesa;
}

$resultado = supabaseRequest(
    "tb_historia_impacto?select=*",
    "POST",
    $novaHistoria,
    $token
);

if (!$resultado["sucesso"]) {
    http_response_code($resultado["status"] ?: 500);
    echo json_encode([
        "sucesso" => false,
        "erro" => $resultado["dados"]["message"] ?? "Erro ao cadastrar história."
    ]);
    exit;
}

echo json_encode([
    "sucesso" => true,
    "mensagem" => "História cadastrada com sucesso!",
    "dados" => $resultado["dados"]
], JSON_UNESCAPED_UNICODE);
