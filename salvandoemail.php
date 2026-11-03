<?php
require_once "conexao.php";
header("Content-Type: application/json");

$dados = json_decode(file_get_contents("php://input"), true);

if (!$dados) { // verificação se os dados existem
    echo json_encode([
        "erro" => "Nenhum dado foi enviado!"
    ]);
    exit;
}

$assunto = $dados["assunto"] ?? ""; // pegando os dados
$conteudo = $dados["conteudo"] ?? "";

if ($assunto == "" || $conteudo == "") { // verificação de se os campos estão preenchidos corrteamente
    echo json_encode([
        "erro" => "Preencha o campo do assunto e do conteúdo!"
    ]);
    exit;
}

$mensagem = [ // esses são os dados serão enviados para o banco de dados
    "assunto" => $assunto,
    "conteudo" => $conteudo
];

$url = "https://wcjmjyqadcevocathzjc.supabase.co/rest/v1/tb_mensagem"; // endereço da tebala

$ch = curl_init($url); // fazendo a requisição
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);

curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "apikey: sb_publishable_Y9VlPHzQ-GIdjFzdgOFczw_h75NuF6g",
    "Authorization: Bearer sb_publishable_Y9VlPHzQ-GIdjFzdgOFczw_h75NuF6g",
    "Prefer: return=representation"
]);


curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($mensagem));

$resposta = curl_exec($ch);

curl_close($ch);

echo $resposta; // mostrando a resposta 

?>