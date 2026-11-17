<?php

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . "/conexao.php";
// =====================================================
// 1. RECEBER E VALIDAR A AUTENTICAÇÃO
// =====================================================

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
// 2. RECEBER O ID DA MENSAGEM OU DA DENÚNCIA
// =====================================================

$entrada = json_decode(
    file_get_contents("php://input"),
    true
);

$idMensagem = filter_var(
    $entrada["id_mensagem"] ?? null,
    FILTER_VALIDATE_INT
);

$idDenuncia = filter_var(
    $entrada["id_denuncia"] ?? null,
    FILTER_VALIDATE_INT
);

$ehMensagem = $idMensagem && $idMensagem > 0;
$ehDenuncia = $idDenuncia && $idDenuncia > 0;

if (!$ehMensagem && !$ehDenuncia) {

    http_response_code(400);

    echo json_encode([
        "sucesso" => false,
        "erro" => "Informe um ID válido de mensagem ou denúncia."
    ]);

    exit;
}

if ($ehMensagem && $ehDenuncia) {

    http_response_code(400);

    echo json_encode([
        "sucesso" => false,
        "erro" => "Informe apenas um tipo de registro por vez."
    ]);

    exit;
}


 




// =====================================================
// 4. BUSCAR A MENSAGEM OU DENÚNCIA NO SUPABASE
// =====================================================

if ($ehMensagem) {

    // Buscar mensagem
    $resultadoRegistro = supabaseRequest(
        "tb_mensagem?select=id_mensagem,id_destinatario,assunto,conteudo&id_mensagem=eq." . $idMensagem,
        "GET",
        null,
        $token
    );

    if (!$resultadoRegistro["sucesso"]) {

        http_response_code(
            $resultadoRegistro["status"] ?: 500
        );

        echo json_encode([
            "sucesso" => false,
            "erro" => "Não foi possível buscar a mensagem."
        ]);

        exit;
    }

    error_log("ID MENSAGEM RECEBIDO: " . $idMensagem);

error_log(
    "RESULTADO BUSCA MENSAGEM: " .
    json_encode($resultadoRegistro, JSON_UNESCAPED_UNICODE)
);

    $registro = $resultadoRegistro["dados"][0] ?? null;

    if (!$registro) {

        http_response_code(404);

        echo json_encode([
            "sucesso" => false,
            "erro" => "Mensagem não encontrada."
        ]);

        exit;
    }

    $assunto = $registro["assunto"] ?? "";
    $conteudo = $registro["conteudo"] ?? "";

} else {

    // Buscar denúncia
    $resultadoRegistro = supabaseRequest(
        "tb_denuncia?select=id_denuncia,tipo_denuncia,urgencia,descricao,endereco_local,ponto_referencia,status_denuncia&id_denuncia=eq." . $idDenuncia,
        "GET",
        null,
        $token
    );

    if (!$resultadoRegistro["sucesso"]) {

        http_response_code(
            $resultadoRegistro["status"] ?: 500
        );

        echo json_encode([
            "sucesso" => false,
            "erro" => "Não foi possível buscar a denúncia.",
            "detalhes" => $resultadoRegistro["resposta_original"] ?? null
        ]);

        exit;
    }

    $registro = $resultadoRegistro["dados"][0] ?? null;

    if (!$registro) {

        http_response_code(404);

        echo json_encode([
            "sucesso" => false,
            "erro" => "Denúncia não encontrada."
        ]);

        exit;
    }

    $assunto = "Denúncia: " . ($registro["tipo_denuncia"] ?? "");

    $conteudo =
        "Tipo: " . ($registro["tipo_denuncia"] ?? "") .
        "\nUrgência informada: " . ($registro["urgencia"] ?? "") .
        "\nDescrição: " . ($registro["descricao"] ?? "") .
        "\nEndereço: " . ($registro["endereco_local"] ?? "") .
        "\nPonto de referência: " . ($registro["ponto_referencia"] ?? "") .
        "\nStatus: " . ($registro["status_denuncia"] ?? "");

}



/* =====================================================
   5. TRIAGEM AUTOMÁTICA CENTRAL PET
   CLASSIFICAÇÃO POR REGRAS E PALAVRAS-CHAVE
===================================================== */

function normalizarTextoTriagem($texto) {

    $texto = trim((string)$texto);

    if (function_exists("mb_strtolower")) {
        $texto = mb_strtolower($texto, "UTF-8");
    } else {
        $texto = strtolower($texto);
    }

    if (function_exists("iconv")) {
        $normalizado = iconv(
            "UTF-8",
            "ASCII//TRANSLIT//IGNORE",
            $texto
        );

        if ($normalizado !== false) {
            $texto = $normalizado;
        }
    }

    return $texto;
}

function cortarResumoTriagem($texto, $limite = 240) {

    if (function_exists("mb_strlen") && function_exists("mb_substr")) {

        if (mb_strlen($texto, "UTF-8") > $limite) {
            return mb_substr($texto, 0, $limite, "UTF-8") . "...";
        }

    } elseif (function_exists("iconv_strlen") && function_exists("iconv_substr")) {

        if (iconv_strlen($texto, "UTF-8") > $limite) {
            return iconv_substr($texto, 0, $limite, "UTF-8") . "...";
        }

    } elseif (strlen($texto) > $limite) {

        return substr($texto, 0, $limite) . "...";
    }

    return $texto;
}


// Juntar assunto e conteúdo para análise
$textoOriginal = trim($assunto . " " . $conteudo);

$textoAnalise = normalizarTextoTriagem($textoOriginal);


// =====================================================
// 6. IDENTIFICAR A CATEGORIA
// =====================================================

$regrasCategoria = [

    "denuncia" => [
        "subcategoria" => "Maus-tratos, abandono ou situação de risco",
        "termos" => [
            "denuncia",
            "maus tratos",
            "maltrato",
            "abandon",
            "agress",
            "violencia",
            "espanc",
            "envenen",
            "atropel",
            "negligencia",
            "preso em corrente"
        ]
    ],

    "adocao" => [
        "subcategoria" => "Interesse ou oferta de adoção",
        "termos" => [
            "adocao",
            "adotar",
            "adotante",
            "lar adotivo",
            "novo lar",
            "lar temporario",
            "disponivel para adocao"
        ]
    ],

    "doacao" => [
        "subcategoria" => "Oferta ou solicitação de doação",
        "termos" => [
            "doacao",
            "doar",
            "racao",
            "medicamento",
            "contribuicao",
            "pix",
            "cobertor",
            "alimento",
            "arrecadacao"
        ]
    ],

    "pedido_de_ajuda" => [
        "subcategoria" => "Resgate ou pedido de atendimento",
        "termos" => [
            "resgate",
            "socorro",
            "preciso de ajuda",
            "atendimento veterinario",
            "veterinario",
            "animal doente",
            "encontrei um animal",
            "encontrei um cachorro",
            "encontrei um gato"
        ]
    ]

];

$categoria = "atendimento_geral";
$subcategoria = "Informações gerais";

foreach ($regrasCategoria as $nomeCategoria => $regra) {

    foreach ($regra["termos"] as $termo) {

        if (strpos($textoAnalise, $termo) !== false) {

            $categoria = $nomeCategoria;
            $subcategoria = $regra["subcategoria"];

            break 2;
        }
    }
}


// =====================================================
// 7. IDENTIFICAR A PRIORIDADE
// =====================================================

$termosRiscoImediato = [

    "sangrando",
    "sangue",
    "atropel",
    "envenen",
    "espanc",
    "apanhando",
    "sem agua",
    "sem comida",
    "sem alimento",
    "preso no sol",
    "nao consegue respirar",
    "risco de morte",
    "agonizando",
    "convuls",
    "ferido",
    "ferida"

];

$termoRiscoEncontrado = null;

foreach ($termosRiscoImediato as $termo) {

    if (strpos($textoAnalise, $termo) !== false) {

        $termoRiscoEncontrado = $termo;

        break;
    }
}

if ($termoRiscoEncontrado !== null) {

    $prioridade = "alta";

    $justificativa =
        "A prioridade alta foi sugerida porque o texto contém o termo relacionado a possível risco imediato: "
        . $termoRiscoEncontrado . ".";

} elseif (
    in_array($categoria, ["denuncia", "pedido_de_ajuda"], true) ||
    strpos($textoAnalise, "urgente") !== false
) {

    $prioridade = "media";

    $justificativa =
        "O conteúdo indica necessidade de avaliação e providências pela equipe, mas as regras não identificaram um termo específico de risco imediato.";

} else {

    $prioridade = "baixa";

    $justificativa =
        "O conteúdo foi classificado como uma solicitação sem indicação identificada de risco imediato.";

}


// =====================================================
// 8. GERAR RESUMO E RESULTADO DA TRIAGEM
// =====================================================

$textoResumo = trim(
    preg_replace(
        '/\s+/',
        ' ',
        strip_tags((string)$conteudo)
    )
);

if ($textoResumo === "") {
    $textoResumo = trim((string)$assunto);
}

if ($textoResumo === "") {
    $textoResumo = "Registro sem descrição.";
}

$resumo = cortarResumoTriagem($textoResumo);

$justificativa .=
    " Classificação automática por palavras-chave. A equipe da ONG deve revisar a categoria e a prioridade antes de tomar providências.";

$classificacao = [

    "categoria" => $categoria,

    "subcategoria" => $subcategoria,

    "prioridade" => $prioridade,

    "resumo" => $resumo,

    "justificativa" => $justificativa

];

// =====================================================
// 9. CONVERTER A CATEGORIA PARA O SELO DO CENTRAL PET
// =====================================================

$categorias = [

    "denuncia" => [
        "nome" => "Denúncia",
        "cor" => "#E00808"
    ],

    "adocao" => [
        "nome" => "Adoção",
        "cor" => "#16A34A"
    ],

    "doacao" => [
        "nome" => "Doação",
        "cor" => "#2563EB"
    ],

    "pedido_de_ajuda" => [
        "nome" => "Pedido de ajuda",
        "cor" => "#F59E0B"
    ],

    "atendimento_geral" => [
        "nome" => "Atendimento geral",
        "cor" => "#6B7280"
    ]

];

$categoria = $classificacao["categoria"];

$prioridade = $classificacao["prioridade"];

if (
    !isset($categorias[$categoria]) ||
    !in_array($prioridade, ["alta", "media", "baixa"], true)
) {

    http_response_code(502);

    echo json_encode([
        "sucesso" => false,
        "erro" => "A categoria ou prioridade retornada é inválida."
    ]);

    exit;
}


// =====================================================
// 10. SALVAR A CLASSIFICAÇÃO NO SUPABASE
// =====================================================

$dadosTriagem = [
    "selo_categoria" => $categorias[$categoria]["nome"],
    "selo_cor" => $categorias[$categoria]["cor"],
    "assunto" => $assunto,
    "subcategoria" => $classificacao["subcategoria"] ?? "",
    "prioridade" => $prioridade,
    "resumo_ia" => $classificacao["resumo"],
    "justificativa_ia" => $classificacao["justificativa"],
    "status_triagem" => "concluida",
    "analisado_em" => gmdate("c")
];

if ($ehMensagem) {
    $filtroTriagem = "id_mensagem=eq." . $idMensagem;
} else {
    $filtroTriagem = "id_denuncia=eq." . $idDenuncia;
}

$resultadoSalvar = supabaseRequest(
    "tb_triagem_ia?" . $filtroTriagem . "&select=id_triagem",
    "PATCH",
    $dadosTriagem,
    $token
);

$linhasAtualizadas = $resultadoSalvar["dados"] ?? [];

if (
    !$resultadoSalvar["sucesso"] ||
    !is_array($linhasAtualizadas) ||
    count($linhasAtualizadas) === 0
) {
    http_response_code(500);

    echo json_encode([
        "sucesso" => false,
        "erro" => "A análise foi realizada, mas nenhuma triagem foi atualizada.",
        "detalhes" => $resultadoSalvar["resposta_original"] ?? null
    ], JSON_UNESCAPED_UNICODE);

    exit;
}
// =====================================================
// 11. DEVOLVER O RESULTADO AO JAVASCRIPT
// =====================================================

echo json_encode([

    "sucesso" => true,

    "mensagem" => "Mensagem analisada e classificada com sucesso!",

    "triagem" => [

        "id_mensagem" => $ehMensagem ? $idMensagem : null,

        "id_denuncia" => $ehDenuncia ? $idDenuncia : null,

        "categoria" => $categorias[$categoria]["nome"],

        "cor" => $categorias[$categoria]["cor"],

        "subcategoria" => $classificacao["subcategoria"] ?? "",

        "prioridade" => $prioridade,

        "resumo" => $classificacao["resumo"],

        "justificativa" => $classificacao["justificativa"]

    ]

], JSON_UNESCAPED_UNICODE);

?>
