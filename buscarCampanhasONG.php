
<?php

header("Content-Type: application/json; charset=UTF-8");

require_once "conexao.php";

function responder($sucesso, $mensagem, $dados = null, $status = 200) {
    http_response_code($status);

    echo json_encode([
        "sucesso" => $sucesso,
        "mensagem" => $mensagem,
        "dados" => $dados
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// Somente GET
if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    responder(false, "Método não permitido.", null, 405);
}

// Receber token
$cabecalhos = function_exists("getallheaders") ? getallheaders() : [];

$autorizacao = $cabecalhos["Authorization"]
    ?? $cabecalhos["authorization"]
    ?? $_SERVER["HTTP_AUTHORIZATION"]
    ?? "";

if (!preg_match('/Bearer\s+(.+)/i', $autorizacao, $matches)) {
    responder(false, "Token de autenticação não enviado.", null, 401);
}

$token = trim($matches[1]);

// Verificar usuário autenticado
global $SUPABASE_URL, $SUPABASE_KEY;

$curl = curl_init($SUPABASE_URL . "/auth/v1/user");

curl_setopt_array($curl, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "apikey: " . $SUPABASE_KEY,
        "Authorization: Bearer " . $token
    ],
    CURLOPT_CONNECTTIMEOUT => 15,
    CURLOPT_TIMEOUT => 30
]);

$respostaAuth = curl_exec($curl);
$statusAuth = curl_getinfo($curl, CURLINFO_HTTP_CODE);

if ($respostaAuth === false) {
    $erroCurl = curl_error($curl);

    responder(
        false,
        "Erro na comunicação com o Supabase: " . $erroCurl,
        null,
        500
    );
}

$usuario = json_decode($respostaAuth, true);

if ($statusAuth !== 200 || empty($usuario["id"])) {
    error_log("STATUS AUTH: " . $statusAuth);
error_log("RESPOSTA AUTH: " . $respostaAuth);
    responder(
        false,
        "Falha na autenticação.",
        [
            "status_auth" => $statusAuth,
            "resposta_auth" => $usuario
        ],
        401
    );
}

$idUsuario = $usuario["id"];

// Verificar se o usuário pertence a uma ONG
$perfil = supabaseRequest(
    "tb_perfil_usuario?id_usuario=eq." . urlencode($idUsuario) . "&select=tipo_conta",
    "GET",
    null,
    $token
);

if (
    !$perfil["sucesso"] ||
    empty($perfil["dados"][0]["tipo_conta"]) ||
    $perfil["dados"][0]["tipo_conta"] !== "orgao"
) {
    responder(false, "Apenas contas de ONG podem acessar esta lista.", null, 403);
}

// Buscar campanhas da ONG
$resultado = supabaseRequest(
    "tb_campanha?id_ong=eq." . urlencode($idUsuario) . "&select=*&order=data_criacao.desc",
    "GET",
    null,
    $token
);

if (!$resultado["sucesso"]) {
    responder(
        false,
        "Não foi possível buscar as campanhas.",
        $resultado["dados"] ?? $resultado["resposta_original"] ?? null,
        400
    );
}

responder(
    true,
    "Campanhas carregadas com sucesso.",
    $resultado["dados"] ?? []
);
