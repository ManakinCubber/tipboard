<?php
$postdata = json_decode(file_get_contents("php://input"), true);
if ($postdata['env'] === "production") {
    $api_url = "http://api.legalib.org/";
    $site_url = "http://legalib.org/";
} else {
    $api_url = "http://api.preprod.legalib.org/";
    $site_url = "http://app.preprod.legalib.org/";
}

switch ($postdata['tile']) {
    case 'API':
        reloadAPI($api_url);
        break;

    case 'FRONT':
        reloadFront($site_url);
        break;
}

function reloadAPI($api_url) {
    $ch = curl_init($api_url . "ping/api");
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_HEADER, true);
    $result = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpcode === 204) {
        $data = array(
            "tile" => "just_value",
            "key" => "API",
            "data" => array(
                "title"=> "Etat de l'API",
                "description"=> "production",
                "just-value" => "UP"
            )
        );
    }
    updateData($data);
}

function reloadFront($site_url) {
    $ch = curl_init($site_url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_HEADER, true);
    $result = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpcode === 200) {
        $data = array(
            "tile" => "just_value",
            "key" => "FRONT",
            "data" =>json_encode(
                array(
                    "title"=> "Etat du front",
                    "description"=> "production",
                    "just-value" => "UP"
                )
            )
        );
    }
    updateData($data);
}

function updateData($data_received) {
    $ch = curl_init("http://dash.legalib.org:7272/api/v0.1/1523223fdfa24d6489b0d9b623e697a7/push");
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data_received));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_HEADER, true);
    $result = curl_exec($ch);
    curl_close($ch);
}
