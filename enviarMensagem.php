
<?php

header("Content-Type: application/json; charset=UTF-8");

require_once "conexao.php";

// ID da conta Ong
$idDestinatario = "775c2501-eb33-46a8-a87c-285951cc222f";

// Receber token de autenticação
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
        "mensagem" => "Usuário não autenticado."
    ]);

    exit;
}

$token = trim($matches[1]);

// Receber dados enviados pelo JavaScript
$dados = json_decode(
    file_get_contents("php://input"),
    true
);

if (
    empty($dados["assunto"]) ||
    empty($dados["conteudo"])
) {
    http_response_code(400);

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Preencha o assunto e a mensagem."
    ]);

    exit;
}

// Montar mensagem
$mensagem = [
    "id_destinatario" => $idDestinatario,
    "assunto" => $dados["assunto"],
    "conteudo" => $dados["conteudo"]
];

// Enviar para o Supabase
$resultado = supabaseRequest(
    "tb_mensagem",
    "POST",
    $mensagem,
    $token
);

http_response_code(
    $resultado["status"] ?: 500
);

echo json_encode([
    "sucesso" => $resultado["sucesso"],
    "mensagem" => $resultado["sucesso"]
        ? "Mensagem enviada com sucesso!"
        : "Não foi possível enviar a mensagem.",
    "dados" => $resultado["dados"] ?? null,
    "erro" => $resultado["sucesso"]
        ? null
        : ($resultado["resposta_original"] ?? $resultado["erro"] ?? null)
]);

?>
