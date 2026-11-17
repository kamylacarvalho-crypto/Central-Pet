
<?php

header("Content-Type: application/json; charset=UTF-8");

require_once "conexao.php";

$headers = function_exists("getallheaders")
    ? getallheaders()
    : [];

$autorizacao =
    $headers["Authorization"]
    ?? $headers["authorization"]
    ?? $_SERVER["HTTP_AUTHORIZATION"]
    ?? "";

if (!preg_match('/Bearer\s+(.+)/i', $autorizacao, $matches)) {

    http_response_code(401);

    echo json_encode([
        "sucesso" => false,
        "erro" => "Usuário não autenticado."
    ]);

    exit;
}

$token = trim($matches[1]);

// =====================================================
// EXCLUIR MENSAGEM SOMENTE DA CAIXA DA ONG
// =====================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $entrada = json_decode(
        file_get_contents("php://input"),
        true
    );

    $idMensagem = filter_var(
        $entrada["id_mensagem"] ?? null,
        FILTER_VALIDATE_INT
    );

    if (!$idMensagem || $idMensagem <= 0) {
        http_response_code(400);

        echo json_encode([
            "sucesso" => false,
            "erro" => "ID da mensagem inválido."
        ]);

        exit;
    }

    $resultadoExclusao = supabaseRequest(
        "tb_mensagem?id_mensagem=eq." . $idMensagem,
        "PATCH",
        [
            "excluido_destinatario" => true
        ],
        $token
    );

    if (!$resultadoExclusao["sucesso"]) {
        http_response_code(
            $resultadoExclusao["status"] ?: 500
        );

        echo json_encode([
            "sucesso" => false,
            "erro" => "Não foi possível excluir a mensagem."
        ]);

        exit;
    }

    echo json_encode([
        "sucesso" => true,
        "mensagem" => "Mensagem removida da caixa da ONG."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    http_response_code(405);

    echo json_encode([
        "sucesso" => false,
        "erro" => "Método não permitido."
    ]);

    exit;
}

// BUSCAR MENSAGENS
$resultado = supabaseRequest(
    "tb_mensagem?select=*&excluido_remetente=eq.false&excluido_destinatario=eq.false&order=data_envio.desc",
    "GET",
    null,
    $token
);

if (!$resultado["sucesso"]) {

    http_response_code($resultado["status"] ?: 500);

    echo json_encode([
        "sucesso" => false,
        "erro" => $resultado["resposta_original"] ?? "Erro ao buscar mensagens."
    ]);

    exit;
}

$mensagens = $resultado["dados"] ?? [];

// INICIALIZAR TRIAGEM
$idsMensagens = [];

foreach ($mensagens as &$mensagem) {

    $mensagem["triagem"] = null;

    if (!empty($mensagem["id_mensagem"])) {
        $idsMensagens[] = (int) $mensagem["id_mensagem"];
    }
}

unset($mensagem);

$idsMensagens = array_unique($idsMensagens);

// BUSCAR TRIAGENS DAS MENSAGENS
if (!empty($idsMensagens)) {

    $ids = implode(",", $idsMensagens);

    $resultadoTriagem = supabaseRequest(
        "tb_triagem_ia?select=*&id_mensagem=in.(" . $ids . ")&order=analisado_em.desc",
        "GET",
        null,
        $token
    );

    if ($resultadoTriagem["sucesso"]) {

        $triagens = $resultadoTriagem["dados"] ?? [];

        $triagensPorMensagem = [];

        foreach ($triagens as $triagem) {

            $idMensagem = $triagem["id_mensagem"];

            if (!isset($triagensPorMensagem[$idMensagem])) {
                $triagensPorMensagem[$idMensagem] = $triagem;
            }
        }

        foreach ($mensagens as &$mensagem) {

            $idMensagem = $mensagem["id_mensagem"];

            if (isset($triagensPorMensagem[$idMensagem])) {
                $mensagem["triagem"] = $triagensPorMensagem[$idMensagem];
            }
        }

        unset($mensagem);
    }
}

// RETORNAR MENSAGENS COM TRIAGEM
echo json_encode([
    "sucesso" => true,
    "mensagens" => $mensagens
]);

?>
