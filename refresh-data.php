<?php
$postdata = json_decode(file_get_contents("php://input"), true);
if ($postdata['env'] === "production") {
    $env = array(
        "api_url" => "http://api.legalib.org/",
        "site_url" => "http://legalib.org/",
        "environnement" => "Production",
        "api_key" => "API",
        "front_key" => "FRONT",
    );
} else {
    $env = array(
        "api_url" => "http://api.preprod.legalib.org/",
        "site_url" => "http://app.preprod.legalib.org/",
        "environnement" => "Preprod",
        "api_key" => "API_PREPROD",
        "front_key" => "FRONT_PREPROD",
    );
}

switch ($postdata['tile']) {
    case 'API':
        reloadAPI($env);
        break;

    case 'FRONT':
        reloadFront($env);
        break;
}

function reloadAPI($env) {
    $ch = curl_init($env['api_url'] . "ping/api");
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_HEADER, true);
    $result = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpcode === 204) {
        $state = "UP";
    } else {
        $state = "DOWN";
    }
    $data = array(
        "tile" => "just_value",
        "key" => $env['api_key'],
        "data" =>json_encode(
            array(
                "title"=> "Etat de l'API",
                "description"=> $env['environnement'],
                "just-value" => $state
            )
        )
    );
    updateData($data);
}

function reloadFront($env) {
    $ch = curl_init($env['site_url']);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_HEADER, true);
    $result = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpcode === 200) {
        $state = "UP";
    } else {
        $state = "DOWN";
    }

    $data = array(
        "tile" => "just_value",
        "key" => $env['front_key'],
        "data" =>json_encode(
            array(
                "title"=> "Etat du front",
                "description"=> $env['environnement'],
                "just-value" => $state
            )
        )
    );
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
