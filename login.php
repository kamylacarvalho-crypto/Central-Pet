
<?php

header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode([
        "sucesso" => false,
        "erro" => "Método não permitido."
    ]);
    exit;
}

$dados = json_decode(file_get_contents("php://input"), true);

if (!is_array($dados)) {
    http_response_code(400);
    echo json_encode([
        "sucesso" => false,
        "erro" => "Dados inválidos."
    ]);
    exit;
}

$email = strtolower(trim($dados["email"] ?? ""));
$senha = $dados["senha"] ?? "";

if (!is_string($senha) || $email === "" || $senha === "") {
    http_response_code(400);
    echo json_encode([
        "sucesso" => false,
        "erro" => "Preencha o e-mail e a senha."
    ]);
    exit;
}

$supabaseUrl = "https://wcjmjyqadcevocathzjc.supabase.co";
$supabaseAnonKey = "sb_publishable_Y9VlPHzQ-GIdjFzdgOFczw_h75NuF6g";

$url = $supabaseUrl . "/auth/v1/token?grant_type=password";

$payload = [
    "email" => $email,
    "password" => $senha
];

$jsonPayload = json_encode($payload);

if ($jsonPayload === false) {
    http_response_code(500);
    echo json_encode([
        "sucesso" => false,
        "erro" => "Falha ao gerar JSON."
    ]);
    exit;
}

$ch = curl_init($url);

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "apikey: " . $supabaseAnonKey
    ],
    CURLOPT_POSTFIELDS => $jsonPayload
]);

$resposta = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($resposta === false) {
    $erroCurl = curl_error($ch);
    curl_close($ch);

    http_response_code(502);
    echo json_encode([
        "sucesso" => false,
        "erro" => "Falha na comunicação com o Supabase.",
        "detalhe" => $erroCurl
    ]);
    exit;
}

curl_close($ch);

$dadosSupabase = json_decode($resposta, true);

if ($status < 200 || $status >= 300) {
    http_response_code($status);

    echo json_encode([
        "sucesso" => false,
        "erro" => $dadosSupabase["msg"]
            ?? $dadosSupabase["message"]
            ?? "Falha na autenticação.",
        "codigo" => $dadosSupabase["error_code"] ?? null
    ]);
    exit;
}

echo json_encode([
    "sucesso" => true,
    "mensagem" => "Login realizado com sucesso!",
    "session" => $dadosSupabase
]);
