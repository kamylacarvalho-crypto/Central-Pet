
<?php

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . "/conexao.php";

// Verificar se existe token de autenticação
$headers = getallheaders();

$authorization = $headers["Authorization"]
    ?? $headers["authorization"]
    ?? "";

if (!$authorization) {
    http_response_code(401);

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Usuário não autenticado."
    ]);

    exit;
}

// Validar o usuário no Supabase Auth
$urlUsuario = rtrim($SUPABASE_URL, "/") . "/auth/v1/user";

$curl = curl_init($urlUsuario);

curl_setopt_array($curl, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "apikey: " . $SUPABASE_KEY,
        "Authorization: " . $authorization,
        "Accept: application/json"
    ],
    CURLOPT_TIMEOUT => 30
]);

$respostaUsuario = curl_exec($curl);
$statusUsuario = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$erroUsuario = curl_error($curl);

curl_close($curl);

if ($respostaUsuario === false || $statusUsuario < 200 || $statusUsuario >= 300) {
    http_response_code(401);

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Não foi possível validar o usuário."
    ]);

    exit;
}

$usuario = json_decode($respostaUsuario, true);

if (empty($usuario["id"])) {
    http_response_code(401);

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Usuário inválido."
    ]);

    exit;
}

$idUsuario = $usuario["id"];


// Buscar o perfil do usuário autenticado
$urlPerfil = rtrim($SUPABASE_URL, "/")
    . "/rest/v1/tb_perfil_usuario"
    . "?id_usuario=eq." . urlencode($idUsuario)
    . "&select=tipo_conta";

$curl = curl_init($urlPerfil);

curl_setopt_array($curl, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "apikey: " . $SUPABASE_KEY,
        "Authorization: " . $authorization,
        "Accept: application/json"
    ],
    CURLOPT_TIMEOUT => 30
]);

$respostaPerfil = curl_exec($curl);
$statusPerfil = curl_getinfo($curl, CURLINFO_HTTP_CODE);

curl_close($curl);

$perfil = json_decode($respostaPerfil, true);

// Verificar se o perfil foi encontrado
if (
    $statusPerfil < 200 ||
    $statusPerfil >= 300 ||
    empty($perfil[0]["tipo_conta"]) ||
    $perfil[0]["tipo_conta"] !== "administrador"
) {
    http_response_code(403);

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Acesso permitido somente para administradores."
    ]);

    exit;
}


/* =========================================
   BUSCAR ESTATÍSTICAS DO CENTRAL PET
========================================= */

// Total de usuários comuns
$totalUsuarios = supabaseRequest(
    "tb_perfil_usuario?tipo_conta=eq.usuario&select=id_perfil",
    "GET",
    null,
    substr($authorization, 7)
);

// Total de ONGs
$totalOngs = supabaseRequest(
    "tb_perfil_usuario?tipo_conta=eq.orgao&select=id_perfil",
    "GET",
    null,
    substr($authorization, 7)
);

// Total de denúncias
$totalDenuncias = supabaseRequest(
    "tb_denuncia?select=id_denuncia",
    "GET",
    null,
    substr($authorization, 7)
);

// Total de animais cadastrados para adoção
$totalAdocoes = supabaseRequest(
    "tb_animal_adocao?select=id_animal",
    "GET",
    null,
    substr($authorization, 7)
);

// Total de doações cadastradas
$totalDoacoes = supabaseRequest(
    "tb_doacao?select=id_doacao",
    "GET",
    null,
    substr($authorization, 7)
);

// Total de campanhas cadastradas
$totalCampanhas = supabaseRequest(
    "tb_campanha?select=id_campanha",
    "GET",
    null,
    substr($authorization, 7)
);


/* =========================================
   DEVOLVER OS RESULTADOS
========================================= */

$resultados = [
    "total_usuarios" => count($totalUsuarios["dados"] ?? []),
    "total_ongs" => count($totalOngs["dados"] ?? []),
    "total_denuncias" => count($totalDenuncias["dados"] ?? []),
    "total_adocoes" => count($totalAdocoes["dados"] ?? []),
    "total_doacoes" => count($totalDoacoes["dados"] ?? []),
    "total_campanhas" => count($totalCampanhas["dados"] ?? [])
];

echo json_encode([
    "sucesso" => true,
    "estatisticas" => $resultados
], JSON_UNESCAPED_UNICODE);
