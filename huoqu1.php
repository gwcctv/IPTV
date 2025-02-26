<?php
header("Content-type:application/json;charset=utf-8");
error_reporting(0);
$url = "http://cms.testipmedia.xyz/newtv/itv/index2.php";
$specialKey = "p2p20210223";
$key = substr(md5($specialKey), 0, 16);
$iv = substr(md5($specialKey), 16, 16);

$deviceId = strtoupper(randomString(32));
$Ip = json_decode(curl_get("http://g3.le.com/r?format=1"), true)['host'];
$getIp = curl_post($url, "getIP=" . $deviceId);

$body = array(
    "mac" => "5b8a4c0a",
    "model" => "HD1910",
    "androidid" => "775D2672A66CC2BF37676E9999510BA7",
    "deviceid" => $deviceId,
    "ip" => $Ip,
    "app_name" => "Focus TV",
    "package_name" => "com.newtvbaichuan.iptv",
    

   "token" => "ADA8bC27b3E03AB57E2E15DA9eb8C9",
    "province" => ""
);
$b = gzcompress(json_encode($body, true));
$enc2 = openssl_encrypt($b, "AES-128-CBC", $key, OPENSSL_RAW_DATA, $iv);
$res = curl_post($url, "login=" . base64_encode($enc2));

$str = openssl_decrypt(base64_decode($res), "AES-128-CBC", $key, OPENSSL_RAW_DATA, $iv);
$json = json_decode(gzuncompress($str), true);
file_put_contents("orginal.txt", json_encode($json, 64 | 128 | 256));

$cate = $json['data'];
$fp = fopen("iptv.txt", "w");
$txt = "";
foreach ($cate as $ct) {
    if ($ct == '收藏') continue;
    $txt = $txt . $ct['category'] . ",#genre#\n";
    $channel = $ct['channels'];
    for ($i = 0; $i < count($channel); $i++) {
        $name = trim(str_replace(".", "", $channel[$i]['channelName']));
        $url = $channel[$i]['channelUrl'][0];
        $txt = $txt . $name . "," . $url . "\n";
    }
}
fwrite($fp, $txt);
fclose($fp);
echo $txt;
exit();


function randomString($length)
{
    $characters = '0123456789abcdef';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[random_int(0, $charactersLength - 1)];
    }
    return $randomString;
}

function curl_get($url)
{
    $header = array(
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/94.0.4606.54 Safari/537.36'
    );
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_TIMEOUT, 20);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
    $data = curl_exec($curl);
    if (curl_error($curl)) {
        return "Error: " . curl_error($curl);
    } else {
        curl_close($curl);
        return $data;
    }
}

function curl_post($url, $postdata)
{
    $header = [
        "User-Agent: Mozilla/5.0(WindowsNT10.0;Win64;x64)AppleWebKit/537.36(KHTML,likeGecko)Chrome/94.0.4606.54Safari/537.36",
    ];

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_TIMEOUT, 20);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
    curl_setopt($curl, CURLOPT_POST, 1);
    if ($postdata != null) curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
    $data = curl_exec($curl);
    if (curl_error($curl)) {
        return "Error:" . curl_error($curl);
    } else {
        curl_close($curl);
        return $data;
    }
}

?>
