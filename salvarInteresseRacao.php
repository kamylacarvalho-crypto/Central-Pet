
<?php
require_once 'conexao.php';

header('Content-Type: application/json; charset=utf-8');

$token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

if (strpos($token, 'Bearer ') === 0) {
    $token = substr($token, 7);
}

if (!$token) {
    http_response_code(401);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Usuário não autenticado.'
    ]);
    exit;
}

$dados = json_decode(file_get_contents('php://input'), true);

$idBanco = $dados['id_banco'] ?? null;

if (!$idBanco || !ctype_digit((string)$idBanco)) {
    http_response_code(400);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Necessidade inválida.'
    ]);
    exit;
}

// Identifica o usuário pelo próprio token
$curl = curl_init($SUPABASE_URL . '/auth/v1/user');

curl_setopt_array($curl, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'apikey: ' . $SUPABASE_KEY,
        'Authorization: Bearer ' . $token
    ]
]);

$usuarioResposta = curl_exec($curl);
$statusUsuario = curl_getinfo($curl, CURLINFO_HTTP_CODE);

curl_close($curl);

$usuarioDados = json_decode($usuarioResposta, true);

if (
    $statusUsuario !== 200 ||
    empty($usuarioDados['id'])
)
 {
    http_response_code(401);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Não foi possível identificar o usuário.'
    ]);
    exit;
}

$idUsuario = $usuarioDados['id'];


// Verifica se o usuário já demonstrou interesse nessa necessidade
$interesseExistente = supabaseRequest(
    'tb_interesse_racao?select=id_interesse&id_banco=eq.' .
    (int)$idBanco .
    '&id_usuario=eq.' . rawurlencode($idUsuario) .
    '&limit=1',
    'GET',
    null,
    $token
);

if (!empty($interesseExistente['dados'])) {
    http_response_code(409);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Você já demonstrou interesse nessa necessidade. A ONG já recebeu seu interesse!'
    ]);
    exit;
}


$resposta = supabaseRequest(
    'tb_interesse_racao',
    'POST',
    [
        'id_banco' => (int)$idBanco,
        'id_usuario' => $idUsuario
    ],
    $token
);

if (($resposta['status'] ?? 500) >= 400) {
    http_response_code($resposta['status']);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Não foi possível registrar o interesse. Talvez você já tenha demonstrado interesse nessa necessidade.'
    ]);
    exit;
}

http_response_code(201);
echo json_encode([
    'sucesso' => true,
    'mensagem' => 'Seu interesse foi registrado com sucesso!'
]);
