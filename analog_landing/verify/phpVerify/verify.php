<?php
/**
 * Created by PhpStorm.
 * User: lxp1055
 * Date: 2018/5/1
 * Time: 12:29
 */
require_once 'AipOcr.php';

// 你的 APPID AK SK
const APP_ID = '11176242';
const API_KEY = 'pI2s6hKBcf5I4lzzs29gZUPv';
const SECRET_KEY = '7h4IouyOQZmjOmptLpnMmVQ23HvKmCgN ';


function VerifyCode()
{
    $client = new AipOcr(APP_ID, API_KEY, SECRET_KEY);
    $image = file_get_contents('verifyCode.png');
    $options = array();
    $options["language_type"] = "CHN_ENG";
    $options["detect_direction"] = "true";
    $options["detect_language"] = "true";
    $options["probability"] = "true";

    $words_result = 'words_result';
    $words = 'words';
// 调用通用文字识别, 图片参数为本地图片
    $verify = $client->basicGeneral($image, $options)[$words_result][0][$words];
    return $verify;
}
?>