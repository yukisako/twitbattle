<?php
/**
 * Created by PhpStorm.
 * User: Yuki_S
 * Date: 2015/04/14
 * Time: 18:59
 */
/*
require_once("TwitterOauth/autoload.php");

use Abraham\TwitterOAuth\TwitterOAuth;
define ("CONSUMER_KEY", "tG8uuTKuvpVEbQRNHBci9gYfl");

define ("CONSUMER_SECRET", "ERqdw2N1hNHKwtPUZqHAhuzM3KOZek5EPCOMTP4DgwgtSq6iGk");

define ("ACCESS_TOKEN_SECRET", "SoGkPNluwJyo0Kym6Mwc3Gwfxm73jeYMLapFWk6B1McJf");

define ("ACCESS_TOKEN", "1003250335-pURU26X5R4K1UojCWz8qzcNrzh5tjP7jFIadeI3");

//オブジェクト作成

$to = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, ACCESS_TOKEN, ACCESS_TOKEN_SECRET);

$req = $to->get("search/tweets", array(
    "q"=>"#全国もう帰りたい協会",
    "count"=>"50"
));

var_dump($req);*/
//$result = json_decode($req);

// foreachで呟きの分だけループする
/*
foreach($req as $status){
    $status_id = $status->id_str; // 呟きのステータスID
    $text = $status->text; // 呟き
    $user_id = $status->user->id_str; // ID（数字）
    $screen_name = $status->user->screen_name; // ユーザーID（いわゆる普通のTwitterのID）
    $name = $status->user->name; // ユーザーの名前（HNなど）
    echo "<p><b>".$screen_name." / ".$name."</b> <a href=\"http://twitter.com/".$screen_name."/status/".$status_id."\">この呟きのパーマリンク</a><br />\n".$text."</p>\n";
}*/

require_once ("./filter-track.php");

function WinLose($player1, $player2)
{
    $battleTable = array(
        '1' => array(
            '1' => false,
            '2' => true,
            '3' => false
        ),
        '2' => array(
            '1' => false,
            '2' => false,
            '3' => true
        ),
        '3' => array(
            '1' => true,
            '2' => false,
            '3' => false
        )
    );
    $Player1CharType = PlayerCharType($player1);
    $Player2CharType = PlayerCharType($player2);

    if($battleTable[$Player1CharType][$Player2CharType])
    {
        $winlose = true;
    } else {
        $winlose = false;
    }
    return $winlose;
}

function PlayerCharType($player)
{
    $kanji = preg_match("/([\x{3005}\x{3007}\x{303b}\x{3400}-\x{9FFF}\x{F900}-\x{FAFF}\x{20000}-\x{2FFFF}])(.*|)/u", $str);
    $kana =  preg_match("/^([ぁ-ゞ]|[ァ-ヾ])+$/u", $str);
    //charTypeに漢字なら1,仮名なら2,その他なら3を格納
    if($kanji){
        $charType = 1;
    } else if($kana){
        $charType = 2;
    } else {
        $charType = 3;
    }
    return $charType;
}

