<?php

header('Content-Type: application/json; charset=utf-8');


$arquivo = '../json/mensagens.json'; // arquivo JSON


if (!file_exists($arquivo)) { // Verificação de se o arquivo existe
    echo json_encode([
        "erro" => "Arquivo mensagens.json não encontrado."
    ]);
    exit;
}

$conteudo = file_get_contents($arquivo); //lendo o conteúdo do JSON

$mensagens = json_decode($conteudo, true); //convertendo o JSON para PHP

if ($mensagens === null) { //verificando se o JSON está correto
    echo json_encode([
        "erro" => "Erro ao ler o arquivo JSON."
    ]);
    exit;
}

echo json_encode($mensagens); //retornando as mensagens