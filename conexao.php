
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

    $curl = curl_init($url);

    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $metodo,
        CURLOPT_HTTPHEADER => $headers
    ]);

    if ($dados !== null) {
        curl_setopt(
            $curl,
            CURLOPT_POSTFIELDS,
            json_encode($dados)
        );
    }

    $resposta = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    if ($resposta === false) {
        $erro = curl_error($curl);
        curl_close($curl);

        return [
            "sucesso" => false,
            "status" => 0,
            "erro" => $erro
        ];
    }

    curl_close($curl);

    return [
        "sucesso" => $status >= 200 && $status < 300,
        "status" => $status,
        "dados" => json_decode($resposta, true),
        "resposta_original" => $resposta
    ];
}
