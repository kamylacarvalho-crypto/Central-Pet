
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

// Somente POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
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

// Verificar usuário no Supabase Auth
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
    responder(false, "Não foi possível verificar a autenticação.", null, 500);
}

$usuario = json_decode($respostaAuth, true);

if ($statusAuth !== 200 || empty($usuario["id"])) {
    responder(false, "Usuário não autenticado.", null, 401);
}

$idUsuario = $usuario["id"];

// Verificar se é uma ONG
$perfil = supabaseRequest(
    "tb_perfil_usuario?id_usuario=eq." . urlencode($idUsuario) . "&select=tipo_conta",
    "GET",
    null,
    $token
);

if (!$perfil["sucesso"] || empty($perfil["dados"][0]["tipo_conta"])) {
    responder(false, "Perfil do usuário não encontrado.", null, 403);
}

if ($perfil["dados"][0]["tipo_conta"] !== "orgao") {
    responder(false, "Somente contas de ONG podem criar campanhas.", null, 403);
}

// Receber dados enviados em JSON
$entrada = json_decode(file_get_contents("php://input"), true);

if (!is_array($entrada)) {
    responder(false, "Dados inválidos.", null, 400);
}

$titulo = trim($entrada["titulo"] ?? "");
$descricao = trim($entrada["descricao"] ?? "");
$imagem = trim($entrada["imagem"] ?? "");
$meta = $entrada["meta_valor"] ?? null;
$dataInicio = $entrada["data_inicio"] ?? "";
$dataFim = $entrada["data_fim"] ?? "";

// Validar campos
if (
    $titulo === "" ||
    $descricao === "" ||
    $imagem === "" ||
    $meta === null ||
    $dataInicio === "" ||
    $dataFim === ""
) {
    responder(false, "Preencha todos os campos da campanha.", null, 400);
}

if (mb_strlen($titulo) > 150) {
    responder(false, "O título deve ter no máximo 150 caracteres.", null, 400);
}

if (!is_numeric($meta) || (float)$meta <= 0) {
    responder(false, "A meta financeira deve ser maior que zero.", null, 400);
}

$inicio = DateTime::createFromFormat("!Y-m-d", $dataInicio);
$fim = DateTime::createFromFormat("!Y-m-d", $dataFim);

if (
    !$inicio ||
    !$fim ||
    $inicio->format("Y-m-d") !== $dataInicio ||
    $fim->format("Y-m-d") !== $dataFim
) {
    responder(false, "Informe datas válidas.", null, 400);
}

if ($fim < $inicio) {
    responder(false, "A data final não pode ser anterior à inicial.", null, 400);
}

// Preparar registro
$campanha = [
    "id_ong" => $idUsuario,
    "titulo" => $titulo,
    "descricao" => $descricao,
    "imagem" => $imagem,
    "meta_valor" => (float)$meta,
    "data_inicio" => $dataInicio,
    "data_fim" => $dataFim,
    "status" => "ativa"
];

// Salvar no Supabase
$resultado = supabaseRequest(
    "tb_campanha",
    "POST",
    $campanha,
    $token
);

if (!$resultado["sucesso"]) {
    responder(
        false,
        "Não foi possível cadastrar a campanha.",
        $resultado["dados"] ?? $resultado["resposta_original"] ?? null,
        400
    );
}

responder(
    true,
    "Campanha cadastrada com sucesso!",
    $resultado["dados"]
);
