
<?php

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . "/conexao.php";

function responder($sucesso, $dados = [], $status = 200) {
    http_response_code($status);

    echo json_encode(
        array_merge(["sucesso" => $sucesso], $dados),
        JSON_UNESCAPED_UNICODE
    );

    exit;
}

if (!in_array($_SERVER["REQUEST_METHOD"], ["GET", "POST"], true)) {
    responder(false, ["erro" => "Método não permitido."], 405);
}

$cabecalhos = function_exists("getallheaders") ? getallheaders() : [];

$autorizacao =
    $cabecalhos["Authorization"] ??
    $cabecalhos["authorization"] ??
    $_SERVER["HTTP_AUTHORIZATION"] ??
    "";

if (!preg_match('/Bearer\s+(.+)/i', $autorizacao, $matches)) {
    responder(false, ["erro" => "Token não enviado."], 401);
}

$token = trim($matches[1]);

// =====================================================
// EXCLUIR ANIMAL DA ADOÇÃO
// =====================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $entrada = json_decode(
        file_get_contents("php://input"),
        true
    );

    $idAnimal = filter_var(
        $entrada["id_animal"] ?? null,
        FILTER_VALIDATE_INT
    );

    if (!$idAnimal || $idAnimal <= 0) {
        responder(false, [
            "erro" => "ID do animal inválido."
        ], 400);
    }

    $resultadoExclusao = supabaseRequest(
        "tb_animal_adocao?id_animal=eq." . $idAnimal,
        "DELETE",
        null,
        $token
    );

    if (!$resultadoExclusao["sucesso"]) {
        responder(false, [
            "erro" => "Não foi possível excluir o animal.",
            "detalhes" => $resultadoExclusao["resposta_original"] ?? null
        ], $resultadoExclusao["status"] ?: 500);
    }

    responder(true, [
        "mensagem" => "Animal removido da adoção com sucesso."
    ]);
}

$resultado = supabaseRequest(
    "tb_animal_adocao?select=*&order=data_resgate.desc",
    "GET",
    null,
    $token
);

if (!$resultado["sucesso"]) {
    responder(false, [
        "erro" => "Não foi possível carregar as adoções.",
        "detalhes" => $resultado["dados"] ?? $resultado["resposta_original"]
    ], $resultado["status"] ?: 500);
}

responder(true, [
    "adocoes" => $resultado["dados"]
]);
