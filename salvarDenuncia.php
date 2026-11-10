
<?php

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . "/conexao.php";

// =====================================================
// VERIFICAR MÉTODO
// =====================================================

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);

    echo json_encode([
        "sucesso" => false,
        "erro" => "Método não permitido."
    ]);

    exit;
}

// =====================================================
// RECEBER TOKEN DE AUTENTICAÇÃO
// =====================================================

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

// =====================================================
// IDENTIFICAR USUÁRIO NO SUPABASE
// =====================================================

$ch = curl_init(
    rtrim($SUPABASE_URL, "/") . "/auth/v1/user"
);

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "apikey: " . $SUPABASE_KEY,
        "Authorization: " . $authorization
    ]
]);

$respostaUsuario = curl_exec($ch);
$statusUsuario = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($respostaUsuario === false) {
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

$usuario = json_decode($respostaUsuario, true);

if ($statusUsuario !== 200 || empty($usuario["id"])) {
    http_response_code(401);

    echo json_encode([
        "sucesso" => false,
        "erro" => "Não foi possível identificar o usuário."
    ]);

    exit;
}

// =====================================================
// RECEBER DADOS DO FORMULÁRIO
// =====================================================

$dados = json_decode(
    file_get_contents("php://input"),
    true
);

if (!is_array($dados)) {
    http_response_code(400);

    echo json_encode([
        "sucesso" => false,
        "erro" => "Dados inválidos."
    ]);

    exit;
}

$tipo = trim($dados["tipo"] ?? "");
$urgencia = trim($dados["urgencia"] ?? "Média");
$endereco = trim($dados["endereco"] ?? "");
$data = trim($dados["data"] ?? "");
$descricao = trim($dados["descricao"] ?? "");
$foto = $dados["foto"] ?? null;
$anonima = $dados["anonimo"] ?? false;

// VALIDAR DATA DA DENÚNCIA

if ($data !== "") {
    $dataValidada = DateTime::createFromFormat("Y-m-d", $data);

    if (!$dataValidada || $dataValidada->format("Y-m-d") !== $data) {
        http_response_code(400);

        echo json_encode([
            "sucesso" => false,
            "erro" => "Data da denúncia inválida."
        ]);

        exit;
    }
}

// =====================================================
// VALIDAR CAMPOS
// =====================================================

if (
    $tipo === "" ||
    $endereco === "" ||
    $descricao === ""
) {
    http_response_code(400);

    echo json_encode([
        "sucesso" => false,
        "erro" => "Preencha todos os campos obrigatórios."
    ]);

    exit;
}

if (!in_array($urgencia, ["Baixa", "Média", "Alta"], true)) {
    http_response_code(400);

    echo json_encode([
        "sucesso" => false,
        "erro" => "Nível de urgência inválido."
    ]);

    exit;
}

$anonima = filter_var(
    $anonima,
    FILTER_VALIDATE_BOOLEAN
);

// =====================================================
// PREPARAR DENÚNCIA
// =====================================================

$corpo = [
    "id_usuario" => $usuario["id"],
    "tipo_denuncia" => $tipo,
    "urgencia" => $urgencia,
    "descricao" => $descricao,
    "endereco_local" => $endereco,
    "status_denuncia" => "Recebida",
    "anonima" => $anonima,
    "data_denuncia" => $data !== ""
        ? $data . "T12:00:00-03:00"
        : date("c"),
    "url_provas" => $foto ?: null
];

// =====================================================
// ENVIAR PARA O SUPABASE
// =====================================================

$url = rtrim($SUPABASE_URL, "/")
    . "/rest/v1/tb_denuncia";

$ch = curl_init($url);

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($corpo),
    CURLOPT_HTTPHEADER => [
        "apikey: " . $SUPABASE_KEY,
        "Authorization: " . $authorization,
        "Content-Type: application/json",
        "Prefer: return=representation"
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

// =====================================================
// VERIFICAR RESULTADO
// =====================================================

$resultado = json_decode($resposta, true);

if (
    $status < 200 ||
    $status >= 300 ||
    empty($resultado)
) {
    http_response_code(400);

    echo json_encode([
        "sucesso" => false,
        "erro" => "Não foi possível salvar a denúncia.",
        "status_supabase" => $status,
        "resposta_supabase" => $resposta
    ]);

    exit;
}

// =====================================================
// RETORNAR SUCESSO
// =====================================================

http_response_code(201);

echo json_encode([
    "sucesso" => true,
    "mensagem" => "Denúncia salva com sucesso!",
    "denuncia" => $resultado[0]
]);

?>
