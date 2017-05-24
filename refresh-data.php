<?php
$postdata = json_decode(file_get_contents("php://input"), true);
if ($postdata['env'] === "production") {
    $env = array(
        "api_url" => "http://api.legalib.org/",
        "site_url" => "http://legalib.org/",
        "environnement" => "Production",
        "api_key" => "API",
        "db_key" => "DATABASE",
        "front_key" => "FRONT",
        "info_key" => "INFO",
    );
} else {
    $env = array(
        "api_url" => "http://api.preprod.legalib.org/",
        "site_url" => "http://app.preprod.legalib.org/",
        "environnement" => "Preprod",
        "api_key" => "API_PREPROD",
        "db_key" => "DATABASE_PREPROD",
        "front_key" => "FRONT_PREPROD",
        "info_key" => "INFO_PREPROD",
    );
}

switch ($postdata['tile']) {
    case 'API':
        reloadAPI($env);
        break;

    case 'DATABASE':
        reloadDatabase($env);
        break;

    case 'FRONT':
        reloadFront($env);
        break;

    case 'INFO':
        reloadInfo($env);
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

function reloadDatabase($env) {
    $ch = curl_init($env['api_url'] . "ping/db");
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
        "key" => $env['db_key'],
        "data" =>json_encode(
            array(
                "title"=> "Etat de la base de donnée",
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

function reloadInfo($env) {
    $ch = curl_init($env['api_url'] . "info");
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_HEADER, true);
    $result = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpcode !== 200) {
        $result = "No data";
    }
    $data = array(
        "tile" => "text",
        "key" => $env['info_key'],
        "data" =>json_encode(
            array(
                "text" => "<h2 id='INFO-title' class='result big-result fixed-height'>Informations</h2><h3 id='INFO-description' class='result label fixed-height'>" . $env['environnement'] . "</h3>" . $result
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
