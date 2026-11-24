
<?php

header("Content-Type: application/json; charset=UTF-8");
require_once "conexao.php";

// Verificar autenticação
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
        "mensagem" => "Usuário não autenticado."
    ]);
    exit;
}

$token = trim($matches[1]);

$dados = json_decode(file_get_contents("php://input"), true);
$idInteresse = (int)($dados["id_interesse"] ?? 0);

if ($idInteresse <= 0) {
    http_response_code(400);
    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Identificador do interesse inválido."
    ]);
    exit;
}

// Identificar a ONG autenticada
$ch = curl_init($SUPABASE_URL . "/auth/v1/user");

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "apikey: " . $SUPABASE_KEY,
        "Authorization: Bearer " . $token
    ]
]);

$respostaUsuario = curl_exec($ch);
$statusUsuario = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$usuario = json_decode($respostaUsuario, true);

if ($statusUsuario !== 200 || empty($usuario["id"])) {
    http_response_code(401);
    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Não foi possível identificar sua conta."
    ]);
    exit;
}

$idOng = $usuario["id"];

// Buscar o interesse
$interesse = supabaseRequest(
    "tb_interesse_racao?select=id_interesse,id_banco,status&id_interesse=eq." .
    $idInteresse . "&limit=1",
    "GET",
    null,
    $token
);

$dadosInteresse = $interesse["dados"][0] ?? null;

if (!$dadosInteresse) {
    http_response_code(404);
    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Interesse não encontrado."
    ]);
    exit;
}

$idBanco = (int)$dadosInteresse["id_banco"];

// Verificar se a necessidade pertence à ONG
$banco = supabaseRequest(
    "tb_banco_racoes?select=id_banco,id_ong,status&id_banco=eq." .
    $idBanco . "&limit=1",
    "GET",
    null,
    $token
);

$dadosBanco = $banco["dados"][0] ?? null;

if (!$dadosBanco || $dadosBanco["id_ong"] !== $idOng) {
    http_response_code(403);
    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Você não tem autorização para confirmar essa doação."
    ]);
    exit;
}

if ($dadosBanco["status"] === "Concluída") {
    http_response_code(409);
    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Essa necessidade já foi concluída."
    ]);
    exit;
}

// Atualizar o interesse
$atualizacaoInteresse = supabaseRequest(
    "tb_interesse_racao?id_interesse=eq." . $idInteresse,
    "PATCH",
    ["status" => "Doação recebida"],
    $token
);

if (empty($atualizacaoInteresse["sucesso"])) {
    http_response_code($atualizacaoInteresse["status"] ?: 500);
    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Não foi possível atualizar o interesse.",
        "erro" => $atualizacaoInteresse["resposta_original"] ?? null
    ]);
    exit;
}

// Concluir a necessidade de ração
$atualizacaoBanco = supabaseRequest(
    "tb_banco_racoes?id_banco=eq." . $idBanco,
    "PATCH",
    ["status" => "Concluída"],
    $token
);

if (empty($atualizacaoBanco["sucesso"])) {
    http_response_code($atualizacaoBanco["status"] ?: 500);
    echo json_encode([
        "sucesso" => false,
        "mensagem" => "O interesse foi atualizado, mas não foi possível concluir a necessidade.",
        "erro" => $atualizacaoBanco["resposta_original"] ?? null
    ]);
    exit;
}

echo json_encode([
    "sucesso" => true,
    "mensagem" => "Recebimento confirmado e necessidade concluída!"
]);

?>
