
<?php

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . "/conexao.php";

$headers = getallheaders();

$authorization = $headers["Authorization"]
    ?? $headers["authorization"]
    ?? "";

if (!$authorization) {
    http_response_code(401);
    echo json_encode([
        "sucesso" => false,
        "erro" => "Usuário não autenticado."
    ]);
    exit;
}

$dados = json_decode(file_get_contents("php://input"), true);

$nome = trim($dados["nome"] ?? "");
$telefone = trim($dados["telefone"] ?? "");
$cidade = trim($dados["cidade"] ?? "");

if ($nome === "" || $cidade === "") {
    http_response_code(400);
    echo json_encode([
        "sucesso" => false,
        "erro" => "Nome e cidade são obrigatórios."
    ]);
    exit;
}

$url = rtrim($SUPABASE_URL, "/")
    . "/rest/v1/tb_perfil_usuario?id_usuario=eq."
    . urlencode("");

/* Recupera o usuário autenticado pelo token */
$ch = curl_init(
    rtrim($SUPABASE_URL, "/") . "/auth/v1/user"
);

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "apikey: " . $SUPABASE_KEY,
        "Authorization: " . $authorization
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
        "erro" => "Não foi possível identificar o usuário."
    ]);
    exit;
}

/* Atualiza somente o perfil do usuário autenticado */
$url = rtrim($SUPABASE_URL, "/")
    . "/rest/v1/tb_perfil_usuario?id_usuario=eq."
    . urlencode($usuario["id"]);

$corpo = [
    "nome" => $nome,
    "telefone" => $telefone ?: null,
    "cidade" => $cidade
];

$ch = curl_init($url);

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => "PATCH",
    CURLOPT_POSTFIELDS => json_encode($corpo),
    CURLOPT_HTTPHEADER => [
        "apikey: " . $SUPABASE_KEY,
        "Authorization: " . $authorization,
        "Content-Type: application/json",
        "Prefer: return=representation"
    ]
]);

$resposta = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($resposta === false) {
    $erro = curl_error($ch);
    curl_close($ch);

    http_response_code(502);
    echo json_encode([
        "sucesso" => false,
        "erro" => $erro
    ]);
    exit;
}

curl_close($ch);

$resultado = json_decode($resposta, true);

if ($status < 200 || $status >= 300 || empty($resultado)) {
    http_response_code(400);

    echo json_encode([
        "sucesso" => false,
        "erro" => "Não foi possível atualizar o perfil.",
        "status_supabase" => $status,
        "resposta_supabase" => $resposta,
        "usuario_id" => $usuario["id"]
    ]);

    exit;
}
echo json_encode([
    "sucesso" => true,
    "mensagem" => "Perfil atualizado com sucesso!"
]);

?>
