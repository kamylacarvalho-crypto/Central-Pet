<?php

require_once "conexao.php";

header("Content-Type: application/json");


$dados = json_decode(file_get_contents("php://input"), true);


$email = $dados["email"] ?? ""; // Pegar email e senha
$senha = $dados["senha"] ?? "";


if ($email == "" || $senha == "") { // Verificar se os campos foram preenchidos
    echo json_encode([
        "erro" => "Informe o email e a senha."
    ]);
    exit;
}


$url = "https://wcjmjyqadcevocathzjc.supabase.co/auth/v1/token?grant_type=password"; // Endereço do login no Supabase


$usuario = [ // Dados do login
    "email" => $email,
    "password" => $senha
];


$ch = curl_init($url); // Fazendo requisição

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);

curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "apikey: sb_publishable_Y9VlPHzQ-GIdjFzdgOFczw_h75NuF6g"
]);

curl_setopt(
    $ch,
    CURLOPT_POSTFIELDS,
    json_encode($usuario)
);


$resposta = curl_exec($ch);// Resposta

curl_close($ch);


echo $resposta;

?>