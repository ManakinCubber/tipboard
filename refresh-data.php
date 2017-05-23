<?php
$postdata = json_decode(file_get_contents("php://input"), true);
if ($postdata['env'] === "production") {
    $api_url = "http://api.legalib.org/";
} else {
    $api_url = "http://api.preprod.legalib.org/";
}

switch ($postdata['tile']) {
    case 'API':
        reloadAPI($api_url);
        break;
}

function reloadAPI($api_url) {
    $ch = curl_init($api_url . "ping/api");
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    $result = curl_exec($ch);
    die(print_r($result));
    curl_close($ch);
}
