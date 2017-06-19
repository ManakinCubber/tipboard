<?php
$postdata = json_decode(file_get_contents("php://input"), true);
$info = array(
    "commit" => $postdata['commit'] ? $postdata['commit'] : "Inconnu",
    "branch" => $postdata['branch'] ? $postdata['branch'] : "Inconnu",
    "user" => $postdata['user'] ? $postdata['user'] : "Inconnu",
);
if ($postdata['env'] === "production") {
    $env = array(
        "api_url" => "https://api.legalib.org",
        "api_document_url" => "http://document.manakin.fr",
        "s3_container" => "legalib",
        "site_url" => "https://legalib.org",
        "environnement" => "Production",
        "api_key" => "API",
        "db_key" => "DATABASE",
        "front_key" => "FRONT",
        "info_key" => "INFO",
    );
} else {
    $env = array(
        "api_url" => "https://api.preprod.legalib.org",
        "api_document_url" => "http://document.preprod.manakin.fr",
        "s3_container" => "legalib-preprod",
        "site_url" => "https://app.preprod.legalib.org",
        "environnement" => "Preprod",
        "api_key" => "API_PREPROD",
        "db_key" => "DATABASE_PREPROD",
        "front_key" => "FRONT_PREPROD",
        "info_key" => "INFO_PREPROD",
    );
}

switch ($postdata['tile']) {
    case 'API':
        $env['key'] = $env['api_key'];
        reloadAPI($env);
        break;

    case 'DATABASE':
        $env['key'] = $env['db_key'];
        reloadDatabase($env);
        break;

    case 'FRONT':
        $env['key'] = $env['front_key'];
        reloadFront($env);
        break;

    case 'INFO':
        $env['key'] = $env['info_key'];
        reloadInfo($env, $info);
        break;
}

function reloadAPI($env) {
    $ch = curl_init($env['api_url'] . "/ping/api");
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
        "key" => $env['key'],
        "data" =>json_encode(
            array(
                "title"=> "Etat de l'API",
                "description"=> $env['environnement'],
                "just-value" => $state
            )
        )
    );
    updateData($data);
    setConfig("just_value", array($state), $env);
}

function reloadDatabase($env) {
    $ch = curl_init($env['api_url'] . "/ping/db");
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
        "key" => $env['key'],
        "data" =>json_encode(
            array(
                "title"=> "Etat de la base de donnée",
                "description"=> $env['environnement'],
                "just-value" => $state
            )
        )
    );
    updateData($data);
    setConfig("just_value", array($state), $env);
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
        $state = true;
    } else {
        $state = false;
    }

    $ch = curl_init($env['site_url'] . '/env.json');
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_HEADER, false);
    $result = curl_exec($ch);
    curl_close($ch);
    $current_env = json_decode($result);

    if ($current_env->apiBaseUrl === $env['api_url']){
        $api_state = true;
    } else {
        $api_state = false;
    }

    if ($current_env->apiDocumentUrl === $env['api_document_url']){
        $api_document_state = true;
    } else {
        $api_document_state = false;
    }

    if ($current_env->filestackContainer === $env['s3_container']){
        $s3_container = true;
    } else {
        $s3_container = false;
    }

    $data = array(
        "tile" => "fancy_listing",
        "key" => $env['key'],
        "data" =>json_encode(
            array(
                array(
                    "label" => "Etat du front: ",
                    "text" => $state ? "OK" : "Not OK"
                ),
                array(
                    "label" => "apiBaseUrl: ",
                    "text" => $current_env->apiBaseUrl
                ),
                array(
                    "label" => "apiDocumentUrl: ",
                    "text" => $current_env->apiDocumentUrl
                ),
                array(
                    "label" => "s3_container: ",
                    "text" => $current_env->filestackContainer
                )
            )
        )
    );

    $states = array($state, $api_state, $api_document_state, $s3_container);

    updateData($data);
    setConfig("fancy_listing", $states, $env);
}

function reloadInfo($env, $info) {
    $ch = curl_init($env['api_url'] . "/info");
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_HEADER, false);
    $result = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpcode !== 200) {
        $result = "No data";
    }
    $data = array(
        "tile" => "fancy_listing",
        "key" => $env['key'],
        "data" =>json_encode(
            array(
                array(

                    "label" => "Commit: ",
                    "text" => $info['commit']
                ),
                array(
                    "label" => "Utilisateur: ",
                    "text" => $info['user']
                ),
                array(
                    "label" => "Branche: ",
                    "text" => $info['branch']
                ),
                array(
                    "label" => "Déploiement: ",
                    "text" => date("d/m/Y H:i:s")
                )
            )
        )
    );

    $states = array(true, true, true, true);

    updateData($data);
    setConfig("fancy_listing", $states, $env);
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

function setConfig($template, $states, $env) {
    if ($template === "just_value") {
        if($states[0] === "DOWN") {
            $color = "red";
            $fade = true;
        } elseif ($states[0] === "UP") {
            $color = "green";
            $fade = false;
        }

        $data = array(
            "value" =>json_encode(
                array(
                    "just-value-color" => $color,
                    "fading_background"=> $fade
                )
            )
        );
    } elseif ($template === "fancy_listing") {

        $value = array(
            "vertical_center" => true
        );
        if ($env['key'] === "INFO" || $env['key'] === "INFO_PREPROD") {
            foreach ($states as $key => $state) {
                $value[$key + 1] = array("label_color"=> "blue", "center"=> true);
            }
        } else {
            foreach ($states as $key => $state) {
                $value[$key + 1] = $state ? array("label_color"=> "green", "center"=> true) : array("label_color"=> "red", "center"=> true);
            }
        }

        $value = json_encode($value);
        $data = array(
            "value" => $value
        );
    }

    $ch = curl_init("http://dash.legalib.org:7272/api/v0.1/1523223fdfa24d6489b0d9b623e697a7/tileconfig/" . $env['key']);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_HEADER, true);
    $result = curl_exec($ch);
    curl_close($ch);
}
