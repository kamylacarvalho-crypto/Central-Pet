
<?php

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . "/conexao.php";

function responder($sucesso, $dados = [], $status = 200) {
    http_response_code($status);

    echo json_encode(
        array_merge(["sucesso" => $sucesso], $dados),
        JSON_UNESCAPED_UNICODE
    );

    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    responder(false, ["erro" => "Método não permitido."], 405);
}

$dados = json_decode(file_get_contents("php://input"), true);

if (!is_array($dados)) {
    responder(false, ["erro" => "Dados inválidos."], 400);
}

// AUTENTICAÇÃO

$cabecalhos = getallheaders();

$autorizacao =
    $cabecalhos["Authorization"] ??
    $cabecalhos["authorization"] ??
    "";

if (!preg_match('/Bearer\s+(.+)/i', $autorizacao, $matches)) {
    responder(false, ["erro" => "Token não enviado."], 401);
}

$token = trim($matches[1]);

$curl = curl_init($SUPABASE_URL . "/auth/v1/user");

curl_setopt_array($curl, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "apikey: " . $SUPABASE_KEY,
        "Authorization: Bearer " . $token
    ],
    CURLOPT_CONNECTTIMEOUT => 15,
    CURLOPT_TIMEOUT => 60
]);

$respostaAuth = curl_exec($curl);
$statusAuth = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$erroAuth = curl_error($curl);

curl_close($curl);

if ($respostaAuth === false) {
    responder(false, ["erro" => "Falha na autenticação: " . $erroAuth], 500);
}

$usuario = json_decode($respostaAuth, true);

if ($statusAuth !== 200 || empty($usuario["id"])) {
    responder(false, ["erro" => "Sessão inválida."], 401);
}

// DADOS DA DOAÇÃO

$tipo = trim($dados["tipo_doacao"] ?? "");
$descricao = trim($dados["descricao_item"] ?? "");
$pagamento = trim($dados["tipo_pagamento"] ?? "");
$valor = $dados["valor"] ?? null;



// VALIDAÇÃO

if ($tipo === "" || $descricao === "" || $pagamento === "") {
    responder(false, ["erro" => "Preencha todos os dados da doação."], 400);
}

if (!is_numeric($valor) || (float)$valor <= 0) {
    responder(false, ["erro" => "Valor da doação inválido."], 400);
}

// REGISTRO DA DOAÇÃO

$doacao = [
    "id_usuario" => $usuario["id"],
    "tipo_doacao" => $tipo,
    "descricao_item" => $descricao,
    "status_pagamento" => "Confirmado",
    "valor" => (float)$valor,
    "tipo_pagamento" => $pagamento
];

// ENVIO PARA O SUPABASE

$resultado = supabaseRequest(
    "tb_doacao?select=*",
    "POST",
    $doacao,
    $token
);

if (!$resultado["sucesso"]) {
    responder(false, [
        "erro" => "Não foi possível salvar a doação.",
        "detalhes" => $resultado["dados"] ?? $resultado["resposta_original"]
    ], $resultado["status"] ?: 500);
}

$registro = $resultado["dados"][0] ?? null;

if (!$registro) {
    responder(false, [
        "erro" => "O Supabase não retornou o registro salvo."
    ], 500);
}

responder(true, [
    "mensagem" => "Doação registrada com sucesso.",
    "doacao" => $registro
]);

