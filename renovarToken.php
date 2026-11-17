
<?php

header("Content-Type: application/json; charset=UTF-8");

require_once "conexao.php";

$entrada = json_decode(file_get_contents("php://input"), true);

$refreshToken = $entrada["refresh_token"] ?? "";

if (empty($refreshToken)) {
    http_response_code(400);
    echo json_encode([
        "sucesso" => false,
        "erro" => "Refresh token não informado."
    ]);
    exit;
}

$url = rtrim($SUPABASE_URL, "/") .
    "/auth/v1/token?grant_type=refresh_token";

$curl = curl_init($url);

curl_setopt_array($curl, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode([
        "refresh_token" => $refreshToken
    ]),
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "apikey: " . $SUPABASE_KEY
    ],
    CURLOPT_TIMEOUT => 30
]);

$resposta = curl_exec($curl);
$erro = curl_error($curl);
$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

curl_close($curl);

if ($resposta === false) {
    http_response_code(500);
    echo json_encode([
        "sucesso" => false,
        "erro" => "Erro ao renovar sessão: " . $erro
    ]);
    exit;
}

$dados = json_decode($resposta, true);

if ($status < 200 || $status >= 300) {
    http_response_code($status);
    echo json_encode([
        "sucesso" => false,
        "erro" => $dados["msg"] ??
                 $dados["message"] ??
                 "Não foi possível renovar a sessão."
    ]);
    exit;
}

echo json_encode([
    "sucesso" => true,
    "access_token" => $dados["access_token"],
    "refresh_token" => $dados["refresh_token"]
]);

