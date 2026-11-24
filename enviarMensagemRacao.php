
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
    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Usuário não autenticado."
    ]);
    exit;
}

$token = trim($matches[1]);

$dados = json_decode(file_get_contents("php://input"), true);

$idDestinatario = $dados["id_destinatario"] ?? "";
$idBanco = $dados["id_banco"] ?? 0;
$assunto = trim($dados["assunto"] ?? "");
$conteudo = trim($dados["conteudo"] ?? "");

if (!$idDestinatario || !$idBanco || !$assunto || !$conteudo) {
    http_response_code(400);
    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Dados da mensagem incompletos."
    ]);
    exit;
}

// Identificar o usuário autenticado
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

$idRemetente = $usuario["id"];

// Verificar se a necessidade pertence à ONG autenticada
$banco = supabaseRequest(
    "tb_banco_racoes?select=id_banco,id_ong&id_banco=eq." .
    (int)$idBanco . "&limit=1",
    "GET",
    null,
    $token
);

$dadosBanco = $banco["dados"][0] ?? null;

if (
    empty($dadosBanco) ||
    $dadosBanco["id_ong"] !== $idRemetente
) {
    http_response_code(403);
    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Você não tem autorização para enviar mensagem sobre essa necessidade."
    ]);
    exit;
}

// Verificar se o destinatário demonstrou interesse nessa necessidade
$interesse = supabaseRequest(
    "tb_interesse_racao?select=id_interesse&id_banco=eq." .
    (int)$idBanco .
    "&id_usuario=eq." . rawurlencode($idDestinatario) .
    "&limit=1",
    "GET",
    null,
    $token
);

if (empty($interesse["dados"])) {
    http_response_code(403);
    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Esse usuário não demonstrou interesse nessa necessidade."
    ]);
    exit;
}

// Registrar mensagem
$mensagem = [
    "id_remetente" => $idRemetente,
    "id_destinatario" => $idDestinatario,
    "assunto" => $assunto,
    "conteudo" => $conteudo,
    "tipo_topico" => "Doação de ração"
];

$resultado = supabaseRequest(
    "tb_mensagem",
    "POST",
    $mensagem,
    $token
);

http_response_code($resultado["status"] ?: 500);

echo json_encode([
    "sucesso" => $resultado["sucesso"],
    "mensagem" => $resultado["sucesso"]
        ? "Mensagem enviada com sucesso!"
        : "Não foi possível enviar a mensagem.",
    "erro" => $resultado["sucesso"]
        ? null
        : ($resultado["resposta_original"] ?? $resultado["erro"] ?? null)
]);

?>
