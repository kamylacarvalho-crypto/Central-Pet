
<?php

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . "/conexao.php";

// Verificar autenticação
$headers = getallheaders();

$authorization = $headers["Authorization"]
    ?? $headers["authorization"]
    ?? "";

if (!$authorization || !str_starts_with($authorization, "Bearer ")) {
    http_response_code(401);

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Usuário não autenticado."
    ]);

    exit;
}

$token = substr($authorization, 7);

// Validar usuário no Supabase Auth
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

curl_close($curl);

$usuario = json_decode($respostaUsuario, true);

if (
    $respostaUsuario === false ||
    $statusUsuario < 200 ||
    $statusUsuario >= 300 ||
    empty($usuario["id"])
) {
    http_response_code(401);

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Sessão inválida."
    ]);

    exit;
}

// Verificar se é administrador
$idUsuario = $usuario["id"];

$perfilAdmin = supabaseRequest(
    "tb_perfil_usuario?id_usuario=eq." . urlencode($idUsuario) . "&select=tipo_conta",
    "GET",
    null,
    $token
);

if (
    !$perfilAdmin["sucesso"] ||
    empty($perfilAdmin["dados"][0]["tipo_conta"]) ||
    $perfilAdmin["dados"][0]["tipo_conta"] !== "administrador"
) {
    http_response_code(403);

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Acesso permitido somente para administradores."
    ]);

    exit;
}

// Buscar ONGs cadastradas
$ongs = supabaseRequest(
    "tb_perfil_usuario?tipo_conta=eq.orgao&select=id_perfil,id_usuario,nome,razao_social,cnpj,telefone,cidade&order=nome.asc&limit=1000",
    "GET",
    null,
    $token
);

if (!$ongs["sucesso"]) {
    http_response_code(500);

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Não foi possível buscar as ONGs.",
        "detalhes" => $ongs["dados"]
    ]);

    exit;
}

// Devolver resultados
echo json_encode([
    "sucesso" => true,
    "dados" => $ongs["dados"] ?? []
], JSON_UNESCAPED_UNICODE);

?>
