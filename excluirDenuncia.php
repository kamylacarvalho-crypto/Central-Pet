
<?php

header("Content-Type: application/json; charset=UTF-8");

require_once "conexao.php";

// Permitir somente requisições DELETE
if ($_SERVER["REQUEST_METHOD"] !== "DELETE") {
    http_response_code(405);
    echo json_encode([
        "sucesso" => false,
        "erro" => "Método não permitido."
    ]);
    exit;
}

// Receber o ID da denúncia
$id = $_GET["id"] ?? "";

if (!ctype_digit((string)$id) || (int)$id <= 0) {
    http_response_code(400);
    echo json_encode([
        "sucesso" => false,
        "erro" => "ID da denúncia inválido."
    ]);
    exit;
}

// Identificar o token do usuário
$cabecalhos = getallheaders();
$autorizacao = $cabecalhos["Authorization"]
    ?? $cabecalhos["authorization"]
    ?? "";

if (!preg_match('/Bearer\s+(.+)/i', $autorizacao, $partes)) {
    http_response_code(401);
    echo json_encode([
        "sucesso" => false,
        "erro" => "Usuário não autenticado."
    ]);
    exit;
}

$token = trim($partes[1]);

// Verificar usuário no Supabase
$curl = curl_init($SUPABASE_URL . "/auth/v1/user");

curl_setopt_array($curl, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "apikey: " . $SUPABASE_KEY,
        "Authorization: Bearer " . $token
    ]
]);

$respostaUsuario = curl_exec($curl);
$statusUsuario = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$erroUsuario = curl_error($curl);

curl_close($curl);

if ($respostaUsuario === false || $statusUsuario !== 200) {
    http_response_code(401);
    echo json_encode([
        "sucesso" => false,
        "erro" => "Não foi possível autenticar o usuário.",
        "detalhe" => $erroUsuario
    ]);
    exit;
}

$usuario = json_decode($respostaUsuario, true);
$idUsuario = $usuario["id"] ?? "";

// Excluir somente a denúncia pertencente ao usuário
$url = $SUPABASE_URL
    . "/rest/v1/tb_denuncia"
    . "?id_denuncia=eq." . (int)$id
    . "&id_usuario=eq." . urlencode($idUsuario);

$curl = curl_init($url);

curl_setopt_array($curl, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => "DELETE",
    CURLOPT_HTTPHEADER => [
        "apikey: " . $SUPABASE_KEY,
        "Authorization: Bearer " . $token,
        "Content-Type: application/json",
        "Prefer: return=minimal"
    ]
]);

$resposta = curl_exec($curl);
$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$erro = curl_error($curl);

curl_close($curl);

if ($resposta === false || $status < 200 || $status >= 300) {
    http_response_code(500);
    echo json_encode([
        "sucesso" => false,
        "erro" => "Não foi possível excluir a denúncia.",
        "detalhe" => $erro ?: $resposta
    ]);
    exit;
}

echo json_encode([
    "sucesso" => true,
    "mensagem" => "Denúncia excluída com sucesso."
]);

?>
