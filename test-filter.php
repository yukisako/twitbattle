<?php
require_once('./phirehose-master/lib/Phirehose.php');
require_once('./phirehose-master/lib/OauthPhirehose.php');
require_once('./TwitterOauth/autoload.php');
use Abraham\TwitterOAuth\TwitterOAuth;
/**
 * Example of using Phirehose to display a live filtered stream using track words
 */


class FilterTrackConsumer extends OauthPhirehose
{
    public $tweets = array();
    public $redis;
    public $twOauth;
    public $faces;

    public function __construct($oauth_token, $oauth_secret, $filter_method) {
        parent::__construct($oauth_token, $oauth_secret, $filter_method);
        $this->redis = new Redis();
        $this -> redis -> connect('127.0.0.1',6379);
        $this -> twOauth = new TwitterOauth(TWITTER_CONSUMER_KEY,TWITTER_CONSUMER_SECRET,OAUTH_TOKEN,OAUTH_SECRET);
        $this->faces = json_decode(file_get_contents("face.json"));
//        $res = $this -> twOauth -> post("statuses/update", array("status" => "@musou1500 テスト"));
//        var_dump($res);

    }

    /**
    * Enqueue each status
    *
    * @param string $status
    */
    public function enqueueStatus($status)
    {
        /*
         * In this simple example, we will just display to STDOUT rather than enqueue.
         * NOTE: You should NOT be processing tweets at this point in a real application, instead they should be being
         *       enqueued and processed asyncronously from the collection process.
         */
        $data = json_decode($status, true);

        if (is_array($data) && isset($data['user']['screen_name'])) {
            echo $data['user']['screen_name']."さんが投稿しました\n";
            $data['text'] = urldecode($data['text']);
            $data['text'] = trim(str_replace("#twitbattle", "",urldecode($data['text'] )));

            // check invalid tweet
            $textdata = explode(" ", urldecode($data['text']));
            if(count($textdata) !== 3){
                echo "不正なフォーマットです\n";



                $this -> twOauth -> post("statuses/update", array("status" => "@".$data['user']['screen_name']."\n 不正なフォーマットです".$this->getface()."\n5 7 5で投稿してください".$this->getface()));



                return;
            }

            if(!((mb_strlen($textdata[0],'UTF-8') == 5) && (mb_strlen($textdata[1],'UTF-8') == 7)&& (mb_strlen($textdata[2],'UTF-8') == 5))) {
                echo mb_strlen($textdata[0],'UTF-8').",";
                echo mb_strlen($textdata[1],'UTF-8').",";
                echo mb_strlen($textdata[2],'UTF-8')."\n";
                return ;
                $this -> twOauth -> post("statuses/update", array("status" => "@".$data['user']['screen_name']."\n 不正なフォーマットです".$this->getface()."\n5 7 5で投稿してください".$this->getface()));
            }


            echo "エントリーOK\n";
            $this -> twOauth -> post("statuses/update", array("status" => "@".$data['user']['screen_name']."\n TwitBattleにエントリーしました".$this->getface()));



            if(count($this -> tweets) >= 1){
                if($data['user']['screen_name'] == $this->tweets[0]['user']['screen_name']){
                    $this -> tweets[0] = $data;



                    $this -> twOauth -> post("statuses/update", array("status" => "@".$data['user']['screen_name']."\n すでにエントリー済みですヾ\n".$this->getface()."既存のエントリーを削除し、更新処理を行いました".$this->getface()));



                } else {
                    print($data['user']['screen_name']."と".$this->tweets[0]['user']['screen_name']."が対戦します\n");
                    $this->battledata($this->tweets[0], $data);
                    $this->tweets = array();
                }
            } else {
                $this -> tweets[] = $data;
            }
            $textdata = explode(" ", urldecode($data['text']));
            if(count($textdata) !== 3){
                return;
            }
 //           print $data['user']['screen_name'] . ': ' . urldecode($data['text']) . "\n";
        }
    }
    function battledata($player1, $player2){
        $player1["text"] = str_replace(" ", "", $player1["text"] );
        $player2["text"] = str_replace(" ", "", $player2["text"] );
        $player1_hp = 0;
        $player2_hp = 0;
        while($player1_hp <= 16 && $player2_hp <= 16){
            $player1win = $this->WinLose(mb_substr($player1['text'], $player1_hp,1), mb_substr($player2['text'], $player2_hp, 1));
            $player2win = $this->WinLose(mb_substr($player2['text'], $player2_hp,1), mb_substr($player1['text'], $player1_hp, 1));
            if($player1win == false) {
                $player1_hp++;
                echo "player1の体力は".$player1_hp."\n";

            }
            if($player2win == false){
                $player2_hp++;
                echo "player2の体力は".$player2_hp."\n";
            }
        }
        $player1_point = $this -> getUserData($player1['user']["screen_name"]);
        $player2_point = $this -> getUserData($player2['user']["screen_name"]);

        $player1_point = $player1_point['point'];
        $player2_point = $player2_point['point'];

        $remain1 = 17 - $player1_hp;
        $remain2 = 17 - $player2_hp;
        if($remain1 > $remain2){
            //プレイヤ1勝利時の処理
            $plusminus = 5 * ($player2_point - $player1_point) + $remain1 * 50;
            $this->redis->zIncrBy('points', $plusminus, $player1['user']['screen_name']);
            $this->redis->zIncrBy('points', 0-$plusminus, $player2['user']['screen_name']);
            print("player1の勝ちです\n");


            $this -> twOauth -> post("statuses/update", array("status" => "@".$player1['user']['screen_name']."さんが"."@".$player2['user']['screen_name']."さんに勝ちました".$this->getface()));



        } else if($remain2 > $remain1) {
            //プレイヤ2勝利時の処理
            $plusminus = 5 * ($player1_point - $player2_point) + $remain2 * 100;
            $this->redis->zIncrBy('points', $plusminus, $player2['user']['screen_name']);
            $this->redis->zIncrBy('points', 0-$plusminus, $player1['user']['screen_name']);
            print("player2の勝ちです\n");


            $this -> twOauth -> post("statuses/update", array("status" => "@".$player2['user']['screen_name']."さんが"."@".$player1['user']['screen_name']."さんに勝ちました".$this->getface()));


        } else {
            $this -> twOauth -> post("statuses/update", array("status" => "@".$player1['user']['screen_name']."さんと"."@".$player2['user']['screen_name']."さんのバトルは引き分けでした".$this->getface()));
        }
        print("player1の残り文字数は".$remain1."です。\n player2の残り文字数は".$remain2."です。\n");
        // redisにデータを入れる
    }
    function userdata($id, $text, $point){

    }

    /**
     * @param $player1 player1の文字
     * @param $player2 player2の文字
     * @return bool
     */
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
        $Player1CharType = $this->PlayerCharType($player1);
        $Player2CharType = $this->PlayerCharType($player2);

        if($battleTable[$Player1CharType][$Player2CharType])
        {
            $winlose = true;
        } else {
            $winlose = false;
        }
        return $winlose;
    }

    function PlayerCharType($str)
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
    function getUserData($screen_name){
        //ユーザーデータがあれば、更新
        //ユーザーデータがなければ作る。
        $point = $this->redis->zRank('points', $screen_name);
        if($point == null){
            //新規
            $rate = 5000;
            $this -> redis -> zAdd('points',$rate, $screen_name);
            print($screen_name."さんのデータを作ります。\n");
        } else {
            //更新
            $rate = $this -> redis-> zScore('points', $screen_name);
        }

        return array('screen_name' => $screen_name, 'point' => $rate);
    }
    function getFace(){
        $faceCount = count($this -> faces);
        $key = mt_rand(0, $faceCount - 1);
        return $this -> faces[$key];
    }
}


// The OAuth credentials you received when registering your app at Twitter
define("TWITTER_CONSUMER_KEY", "BoZZeo4GJNy2ZDh5NbCol9p8Y");
define("TWITTER_CONSUMER_SECRET", "i8MakLAuVosAAdozFehfphHz57ZgbLQpF2Ubz8YGGA017gLchC");


// The OAuth data for the twitter account
define("OAUTH_TOKEN", "3187497205-umL3JDIrqeMCrPKImUdwPVY3qzsGn0QdL4sNkYo");
define("OAUTH_SECRET", "bTzLh6XtHjUCYCGfXifxD4PBVV5UVCAYNO8zUDWmJZpuc");

// Start streaming
$sc = new FilterTrackConsumer(OAUTH_TOKEN, OAUTH_SECRET, Phirehose::METHOD_FILTER);
$sc->setTrack(array('#twitbattle'));
$sc->consume();
