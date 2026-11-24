
<?php

header("Content-Type: application/json; charset=utf-8");

require_once "conexao.php";

function responder($sucesso, $dados = [], $status = 200) {

    http_response_code($status);

    echo json_encode(
        array_merge(["sucesso" => $sucesso], $dados),
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


// =====================================================
// 1. VERIFICAR MÉTODO
// =====================================================

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    responder(false, ["erro" => "Método não permitido."], 405);
}


// =====================================================
// 2. VERIFICAR TOKEN
// =====================================================

$headers = function_exists("getallheaders")
    ? getallheaders()
    : [];

$token = $headers["Authorization"]
    ?? $headers["authorization"]
    ?? "";

if (!preg_match('/^Bearer\s+(.+)$/i', $token, $matches)) {
    responder(false, ["erro" => "Usuário não autenticado."], 401);
}

$token = $matches[1];


// =====================================================
// 3. VALIDAR USUÁRIO NO SUPABASE AUTH
// =====================================================

global $SUPABASE_URL, $SUPABASE_KEY;

$curlAuth = curl_init($SUPABASE_URL . "/auth/v1/user");

curl_setopt_array($curlAuth, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "apikey: " . $SUPABASE_KEY,
        "Authorization: Bearer " . $token
    ],
    CURLOPT_CONNECTTIMEOUT => 15,
    CURLOPT_TIMEOUT => 30
]);

$respostaAuth = curl_exec($curlAuth);
$statusAuth = curl_getinfo($curlAuth, CURLINFO_HTTP_CODE);

if ($respostaAuth === false || $statusAuth !== 200) {
    responder(false, ["erro" => "Sessão inválida. Faça login novamente."], 401);
}

$usuario = json_decode($respostaAuth, true);

if (empty($usuario["id"])) {
    responder(false, ["erro" => "Não foi possível identificar o usuário."], 401);
}

$idUsuario = $usuario["id"];


// =====================================================
// 4. VERIFICAR SE A CONTA É ONG
// =====================================================

$perfil = supabaseRequest(
    "tb_perfil_usuario?id_usuario=eq." .
    urlencode($idUsuario) .
    "&select=tipo_conta",
    "GET",
    null,
    $token
);

if (
    !$perfil["sucesso"] ||
    empty($perfil["dados"][0]["tipo_conta"]) ||
    $perfil["dados"][0]["tipo_conta"] !== "orgao"
) {
    responder(false, ["erro" => "Somente contas de ONG podem enviar imagens."], 403);
}


// =====================================================
// 5. VERIFICAR ARQUIVO
// =====================================================

if (
    !isset($_FILES["imagem"]) ||
    $_FILES["imagem"]["error"] !== UPLOAD_ERR_OK
) {
    responder(false, ["erro" => "Selecione uma imagem válida."]);
}

$arquivo = $_FILES["imagem"];

$limite = 5 * 1024 * 1024;

if ($arquivo["size"] > $limite) {
    responder(false, ["erro" => "A imagem deve ter no máximo 5 MB."]);
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($arquivo["tmp_name"]);

$tiposPermitidos = [
    "image/jpeg" => "jpg",
    "image/png" => "png",
    "image/webp" => "webp"
];

if (!isset($tiposPermitidos[$mime])) {
    responder(false, ["erro" => "Formato inválido. Use JPG, PNG ou WEBP."]);
}


// =====================================================
// 6. PREPARAR ENVIO
// =====================================================

$extensao = $tiposPermitidos[$mime];

$nomeArquivo = bin2hex(random_bytes(16)) . "." . $extensao;

$caminhoArquivo = $idUsuario . "/" . $nomeArquivo;

$urlStorage = $SUPABASE_URL .
    "/storage/v1/object/campanhas/" .
    $caminhoArquivo;


// =====================================================
// 7. ENVIAR IMAGEM AO SUPABASE STORAGE
// =====================================================

$conteudoImagem = file_get_contents($arquivo["tmp_name"]);

$curlStorage = curl_init($urlStorage);

curl_setopt_array($curlStorage, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $conteudoImagem,
    CURLOPT_HTTPHEADER => [
        "apikey: " . $SUPABASE_KEY,
        "Authorization: Bearer " . $token,
        "Content-Type: " . $mime,
        "x-upsert: false"
    ],
    CURLOPT_CONNECTTIMEOUT => 15,
    CURLOPT_TIMEOUT => 60
]);

$respostaStorage = curl_exec($curlStorage);
$statusStorage = curl_getinfo($curlStorage, CURLINFO_HTTP_CODE);

if (
    $respostaStorage === false ||
    $statusStorage < 200 ||
    $statusStorage >= 300
) {
    responder(false, [
        "erro" => "Não foi possível enviar a imagem.",
        "detalhes" => json_decode($respostaStorage ?: "{}", true)
    ], 500);
}


// =====================================================
// 8. RETORNAR ENDEREÇO PÚBLICO
// =====================================================

$urlPublica = $SUPABASE_URL .
    "/storage/v1/object/public/campanhas/" .
    $caminhoArquivo;

responder(true, [
    "mensagem" => "Imagem enviada com sucesso.",
    "imagem" => $urlPublica
]);

