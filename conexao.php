
<?php

$SUPABASE_URL = "https://wcjmjyqadcevocathzjc.supabase.co";
$SUPABASE_KEY = "sb_publishable_Y9VlPHzQ-GIdjFzdgOFczw_h75NuF6g";

function supabaseRequest(
    $endpoint,
    $metodo = "GET",
    $dados = null,
    $token = null
) {
    global $SUPABASE_URL, $SUPABASE_KEY;

    $url = $SUPABASE_URL . "/rest/v1/" . $endpoint;

    $headers = [
        "apikey: " . $SUPABASE_KEY,
        "Content-Type: application/json",
        "Accept: application/json"
    ];

    if ($token) {
        $headers[] = "Authorization: Bearer " . $token;
    } else {
        $headers[] = "Authorization: Bearer " . $SUPABASE_KEY;
    }

    // Solicitar ao Supabase as linhas afetadas pelo PATCH/POST
    if (in_array(strtoupper($metodo), ["PATCH", "POST", "DELETE"])) {
        $headers[] = "Prefer: return=representation";
    }

    $curl = curl_init($url);

    $opcoes = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => strtoupper($metodo),
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_TIMEOUT => 60
    ];

    if ($dados !== null) {
        $opcoes[CURLOPT_POSTFIELDS] = json_encode(
            $dados,
            JSON_UNESCAPED_UNICODE
        );
    }

    curl_setopt_array($curl, $opcoes);

    $resposta = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $erro = curl_error($curl);

    curl_close($curl);

    if ($resposta === false) {
        return [
            "sucesso" => false,
            "status" => 0,
            "erro" => $erro,
            "dados" => null,
            "resposta_original" => null
        ];
    }

    return [
        "sucesso" => $status >= 200 && $status < 300,
        "status" => $status,
        "dados" => json_decode($resposta, true),
        "resposta_original" => $resposta
    ];
}
