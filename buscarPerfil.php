
<?php

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . "/conexao.php";

// Recupera o token enviado pelo usuário
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

// Confere se a conexão está configurada
if (empty($SUPABASE_URL) || empty($SUPABASE_KEY)) {
    http_response_code(500);
    echo json_encode([
        "sucesso" => false,
        "erro" => "Configuração do Supabase incompleta."
    ]);
    exit;
}

// Busca o perfil e sua localização
$url = rtrim($SUPABASE_URL, "/")
    . "/rest/v1/tb_perfil_usuario"
    . "?select=nome,telefone,foto_avatar,tipo_conta,id_localizacao,tb_localizacao(cidade,estado)";

$ch = curl_init($url);

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "apikey: " . $SUPABASE_KEY,
        "Authorization: " . $authorization,
        "Accept: application/json"
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

http_response_code($status);
echo $resposta;

?>
