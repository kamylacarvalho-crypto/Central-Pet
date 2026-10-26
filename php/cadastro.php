<?php

require_once __DIR__ . "/conexao.php";

header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);

    echo json_encode([
        "sucesso" => false,
        "erro" => "Método não permitido."
    ]);

    exit;
}

// Recebe os dados enviados pelo JavaScript
$dados = json_decode(file_get_contents("php://input"), true);

if (!is_array($dados)) {
    http_response_code(400);

    echo json_encode([
        "sucesso" => false,
        "erro" => "Os dados enviados são inválidos."
    ]);

    exit;
}

$email = trim($dados["email"] ?? "");
$senha = $dados["senha"] ?? "";
$tipo_conta = $dados["tipo_conta"] ?? "";

// Valida os campos
if ($email === "" || $senha === "" || $tipo_conta === "") {
    http_response_code(400);

    echo json_encode([
        "sucesso" => false,
        "erro" => "Preencha o e-mail, a senha e o tipo de conta."
    ]);

    exit;
}

// Valida o e-mail
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);

    echo json_encode([
        "sucesso" => false,
        "erro" => "Informe um e-mail válido."
    ]);

    exit;
}

// Valida o tamanho mínimo da senha
if (strlen($senha) < 6) {
    http_response_code(400);

    echo json_encode([
        "sucesso" => false,
        "erro" => "A senha deve ter pelo menos 6 caracteres."
    ]);

    exit;
}

// Aceita somente os tipos de conta permitidos.
// O administrador não pode ser criado pelo formulário público.
$tiposPermitidos = [
    "usuario",
    "orgao_legal"
];

if (!in_array($tipo_conta, $tiposPermitidos, true)) {
    http_response_code(400);

    echo json_encode([
        "sucesso" => false,
        "erro" => "Tipo de conta inválido."
    ]);

    exit;
}

// URL de cadastro do Supabase
$url = rtrim($SUPABASE_URL, "/") . "/auth/v1/signup";

// Padroniza o tipo de perfil
$tipoPerfil = ($tipo_conta === "orgao_legal")
    ? "orgao"
    : "usuario";

// Envia os dados para o Supabase
$usuario = [
    "email" => $email,
    "password" => $senha,
    "data" => [
        "tipo" => $tipoPerfil
    ]
];

$ch = curl_init($url);

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "apikey: " . $SUPABASE_KEY
    ],
    CURLOPT_POSTFIELDS => json_encode($usuario)
]);

$resposta = curl_exec($ch);
$erro = curl_error($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

curl_close($ch);

// Trata erros de comunicação
if ($resposta === false) {
    http_response_code(500);

    echo json_encode([
        "sucesso" => false,
        "erro" => "Não foi possível conectar ao Supabase.",
        "detalhe" => $erro
    ]);

    exit;
}

// Converte a resposta do Supabase
$respostaSupabase = json_decode($resposta, true);

// Verifica se o cadastro foi aceito
if ($status < 200 || $status >= 300) {
    http_response_code($status);

    echo json_encode([
        "sucesso" => false,
        "erro" => $respostaSupabase["msg"]
            ?? $respostaSupabase["message"]
            ?? $respostaSupabase["error_description"]
            ?? "Não foi possível realizar o cadastro.",
        "detalhes" => $respostaSupabase
    ]);

    exit;
}

// Cadastro realizado
echo json_encode([
    "sucesso" => true,
    "mensagem" => "Cadastro realizado com sucesso!",
    "tipo_conta" => $tipo_conta,
    "confirmacao_email_necessaria" =>
        empty($respostaSupabase["session"])
]);