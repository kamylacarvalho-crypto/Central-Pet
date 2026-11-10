<?php
// php/cadastro.php

header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["sucesso" => false, "erro" => "Método não permitido."]);
    exit;
}

$rawInput = file_get_contents("php://input");
$jsonInput = json_decode($rawInput, true);

if (is_array($jsonInput)) {
    $email = strtolower(trim($jsonInput["email"] ?? ""));
    $senha = $jsonInput["senha"] ?? "";
    $tipo_conta = $jsonInput["tipo_conta"] ?? "";
} else {
    $email = strtolower(trim($_POST["email"] ?? ""));
    $senha = $_POST["senha"] ?? "";
    $tipo_conta = $_POST["tipo_conta"] ?? "";
}

if (empty($email) || empty($senha) || empty($tipo_conta)) {
    http_response_code(400);
    echo json_encode(["sucesso" => false, "erro" => "Preencha todos os campos obrigatórios."]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(["sucesso" => false, "erro" => "Informe um e-mail válido."]);
    exit;
}

if (strlen($senha) < 6) {
    http_response_code(400);
    echo json_encode(["sucesso" => false, "erro" => "A senha deve ter pelo menos 6 caracteres."]);
    exit;
}

// Configurações do Supabase
$supabaseUrl = 'https://wcjmjyqadcevocathzjc.supabase.co';
// Cole a sua chave "anon" / "public" (começa com eyJ...)
$supabaseAnonKey = 'sb_publishable_Y9VlPHzQ-GIdjFzdgOFczw_h75NuF6g';

$url = $supabaseUrl . "/auth/v1/signup";
$tipoPerfil = ($tipo_conta === "orgao_legal") ? "orgao" : "usuario";

$payload = [
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
    "apikey: " . $supabaseAnonKey
],
    CURLOPT_POSTFIELDS => json_encode($payload)
]);

$resposta = curl_exec($ch);

if ($resposta === false) {
    $erroCurl = curl_error($ch);
    curl_close($ch);

    http_response_code(502);

    echo json_encode([
        "sucesso" => false,
        "erro" => "Falha na comunicação com o Supabase: " . $erroCurl
    ]);

    exit;
}

$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

curl_close($ch);

$dadosSupabase = json_decode($resposta, true);

if ($status < 200 || $status >= 300) {
    http_response_code(400);
    echo json_encode([
        "sucesso" => false,
        "erro" => $dadosSupabase["msg"] ?? $dadosSupabase["message"] ?? $dadosSupabase["error_description"] ?? "Erro ao realizar o cadastro no Supabase."
    ]);
    exit;
}

http_response_code(200);
echo json_encode([
    "sucesso" => true,
    "mensagem" => "Cadastro realizado com sucesso!"
]);