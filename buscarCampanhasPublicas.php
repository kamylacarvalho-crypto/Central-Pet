
<?php

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . "/conexao.php";

function responder($sucesso, $mensagem, $dados = null, $status = 200) {

    http_response_code($status);

    echo json_encode([
        "sucesso" => $sucesso,
        "mensagem" => $mensagem,
        "dados" => $dados
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    responder(false, "Método não permitido.", null, 405);
}

// BUSCAR CAMPANHAS COM ARRECADAÇÃO
$resultado = supabaseRequest(
    "vw_arrecadacao_campanhas?select=*&order=id_campanha.desc",
    "GET"
);

if (!$resultado["sucesso"]) {
    responder(
        false,
        "Não foi possível carregar as campanhas.",
        $resultado["dados"] ?? null,
        400
    );
}

$campanhas = $resultado["dados"] ?? [];

if (empty($campanhas)) {
    responder(true, "Nenhuma campanha encontrada.", []);
}

// BUSCAR IMAGEM, DESCRIÇÃO, ONG E STATUS
$complemento = supabaseRequest(
    "tb_campanha?status=eq.ativa&select=*",
    "GET"
);

if (!$complemento["sucesso"]) {
    responder(false, "Erro ao buscar detalhes das campanhas.", null, 400);
}

$detalhes = [];

foreach (($complemento["dados"] ?? []) as $item) {
    $detalhes[$item["id_campanha"]] = $item;
}

// BUSCAR NOMES DAS ONGS
$idsOngs = [];

foreach ($detalhes as $item) {
    if (!empty($item["id_ong"])) {
        $idsOngs[] = $item["id_ong"];
    }
}

$idsOngs = array_unique($idsOngs);
$nomesOngs = [];

if (!empty($idsOngs)) {

    $idsFormatados = implode(",", $idsOngs);

    $perfis = supabaseRequest(
        "tb_perfil_usuario?id_usuario=in.(" . $idsFormatados . ")&select=id_usuario,nome",
        "GET"
    );

    if ($perfis["sucesso"]) {
        foreach (($perfis["dados"] ?? []) as $perfil) {
            $nomesOngs[$perfil["id_usuario"]] = $perfil["nome"] ?? "ONG";
        }
    }
}

// COMBINAR OS DADOS
$campanhasPublicas = [];

foreach ($campanhas as $campanha) {

    $id = $campanha["id_campanha"];

    if (!isset($detalhes[$id])) {
        continue;
    }

    $info = $detalhes[$id];

    $info["valor_arrecadado"] =
        (float)($campanha["valor_arrecadado"] ?? 0);

    $info["valor_pendente"] =
        (float)($campanha["valor_pendente"] ?? 0);

    $info["nome_ong"] =
        $nomesOngs[$info["id_ong"] ?? ""] ?? "ONG responsável";

    $campanhasPublicas[] = $info;
}

responder(
    true,
    "Campanhas e arrecadações carregadas com sucesso.",
    $campanhasPublicas
);
