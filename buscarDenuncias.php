
<?php

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . "/conexao.php";

// Verificar método
if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    http_response_code(405);

    echo json_encode([
        "sucesso" => false,
        "erro" => "Método não permitido."
    ]);

    exit;
}

// Receber token
$headers = getallheaders();

$authorization = $headers["Authorization"]
    ?? $headers["authorization"]
    ?? $_SERVER["HTTP_AUTHORIZATION"]
    ?? "";

if (!$authorization) {
    http_response_code(401);

    echo json_encode([
        "sucesso" => false,
        "erro" => "Usuário não autenticado."
    ]);

    exit;
}

// Identificar usuário
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

if ($respostaUsuario === false) {
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

$usuario = json_decode($respostaUsuario, true);

if ($statusUsuario !== 200 || empty($usuario["id"])) {
    http_response_code(401);

    echo json_encode([
        "sucesso" => false,
        "erro" => "Não foi possível identificar o usuário."
    ]);

    exit;
}

// Buscar denúncias do usuário autenticado
$url = rtrim($SUPABASE_URL, "/")
    . "/rest/v1/tb_denuncia"
    . "?select=*"
    . "&id_usuario=eq." . urlencode($usuario["id"])
    . "&order=id_denuncia.desc";

$ch = curl_init($url);

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "apikey: " . $SUPABASE_KEY,
        "Authorization: " . $authorization,
        "Content-Type: application/json"
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

if ($status < 200 || $status >= 300 || !is_array($resultado)) {
    http_response_code(400);

    echo json_encode([
        "sucesso" => false,
        "erro" => "Não foi possível buscar as denúncias.",
        "resposta_supabase" => $resposta
    ]);

    exit;
}

// Retornar resultado
echo json_encode([
    "sucesso" => true,
    "denuncias" => $resultado
]);

?>
