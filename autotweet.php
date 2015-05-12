<?php
/**
 * Created by PhpStorm.
 * User: Yuki_S
 * Date: 2015/05/12
 * Time: 19:39
 */require_once('./phirehose-master/lib/Phirehose.php');
require_once('./phirehose-master/lib/OauthPhirehose.php');
require_once('./TwitterOauth/autoload.php');
use Abraham\TwitterOAuth\TwitterOAuth;
/**
 * Example of using Phirehose to display a live filtered stream using track words
 */

// The OAuth credentials you received when registering your app at Twitter
define("TWITTER_CONSUMER_KEY", "BoZZeo4GJNy2ZDh5NbCol9p8Y");
define("TWITTER_CONSUMER_SECRET", "i8MakLAuVosAAdozFehfphHz57ZgbLQpF2Ubz8YGGA017gLchC");


// The OAuth data for the twitter account
define("OAUTH_TOKEN", "3187497205-umL3JDIrqeMCrPKImUdwPVY3qzsGn0QdL4sNkYo");
define("OAUTH_SECRET", "bTzLh6XtHjUCYCGfXifxD4PBVV5UVCAYNO8zUDWmJZpuc");


    $twOauth = new TwitterOauth(TWITTER_CONSUMER_KEY,TWITTER_CONSUMER_SECRET,OAUTH_TOKEN,OAUTH_SECRET);
    $redis = new Redis();
    $redis -> connect('127.0.0.1',6379);
    $response = array();
    $result = array();

    $ranking = $redis -> zRevRange('points', 0, 2, true);
    // $ranking2 = $redis -> hGetAll('yuki_99_s');
    $text = "";
    foreach($ranking as $screen_name => $point){
        $text .= "@".$screen_name.", ";
    }
    $text = "本日のランキングの発表です".getface()."\n".$text."詳しいランキングはこちら"."リンク先予定";
$twOauth -> post("statuses/update", array("status" => $text));

function getFace(){
    $faces = json_decode(file_get_contents("face.json"));
    $faceCount = count($faces);
    $key = mt_rand(0, $faceCount - 1);
    return $faces[$key];
}




