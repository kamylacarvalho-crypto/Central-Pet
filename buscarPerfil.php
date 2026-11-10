
<?php

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . "/conexao.php";

// Verifica se o Supabase está configurado
if (empty($SUPABASE_URL) || empty($SUPABASE_KEY)) {
    http_response_code(500);
    echo json_encode([
        "sucesso" => false,
        "erro" => "Configuração do Supabase incompleta."
    ]);
    exit;
}

// Recebe o token enviado pelo JavaScript
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

// 1. Identifica o usuário pelo token
$urlUsuario = rtrim($SUPABASE_URL, "/") . "/auth/v1/user";

$ch = curl_init($urlUsuario);

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "apikey: " . $SUPABASE_KEY,
        "Authorization: " . $authorization,
        "Accept: application/json"
    ],
    CURLOPT_TIMEOUT => 30
]);

$respostaUsuario = curl_exec($ch);
$statusUsuario = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$erroUsuario = curl_error($ch);

if ($respostaUsuario === false) {
    http_response_code(502);
    echo json_encode([
        "sucesso" => false,
        "erro" => $erroUsuario
    ]);
    exit;
}

$usuario = json_decode($respostaUsuario, true);

if ($statusUsuario < 200 || $statusUsuario >= 300 || empty($usuario["id"])) {
    http_response_code(401);
    echo json_encode([
        "sucesso" => false,
        "erro" => "Não foi possível identificar o usuário autenticado."
    ]);
    exit;
}

// 2. Busca somente o perfil desse usuário
$idUsuario = $usuario["id"];

$urlPerfil = rtrim($SUPABASE_URL, "/")
    . "/rest/v1/tb_perfil_usuario"
    . "?id_usuario=eq." . urlencode($idUsuario)
    . "&select=id_usuario,nome,telefone,cidade,foto_avatar,tipo_conta,id_localizacao,tb_localizacao(cidade,estado)";

$ch = curl_init($urlPerfil);

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "apikey: " . $SUPABASE_KEY,
        "Authorization: " . $authorization,
        "Accept: application/json"
    ],
    CURLOPT_TIMEOUT => 30
]);

$respostaPerfil = curl_exec($ch);
$statusPerfil = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$erroPerfil = curl_error($ch);

if ($respostaPerfil === false) {
    http_response_code(502);
    echo json_encode([
        "sucesso" => false,
        "erro" => $erroPerfil
    ]);
    exit;
}

if ($statusPerfil < 200 || $statusPerfil >= 300) {
    http_response_code($statusPerfil);
    echo json_encode([
        "sucesso" => false,
        "erro" => "Não foi possível buscar o perfil."
    ]);
    exit;
}

// Retorna os dados do perfil
http_response_code(200);
echo $respostaPerfil;

?>
