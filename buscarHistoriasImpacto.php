
<?php

header("Content-Type: application/json; charset=UTF-8");
require_once "conexao.php";

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

$resultado = supabaseRequest(
    "tb_historia_impacto?select=*,tb_animal(id_animal,nome,especie,raca,idade,porte,url_foto)&order=data_publicacao.desc",
    "GET",
    null,
    $token
);

if (!$resultado["sucesso"]) {
    http_response_code($resultado["status"] ?: 500);

    echo json_encode([
        "sucesso" => false,
        "erro" => $resultado["dados"]["message"] ?? "Erro ao buscar histórias."
    ]);

    exit;
}

echo json_encode([
    "sucesso" => true,
    "dados" => $resultado["dados"] ?? []
], JSON_UNESCAPED_UNICODE);
