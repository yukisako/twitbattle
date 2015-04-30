<?php
require_once('./phirehose-master/lib/Phirehose.php');
require_once('./phirehose-master/lib/OauthPhirehose.php');

/**
 * Example of using Phirehose to display a live filtered stream using track words
 */


class FilterTrackConsumer extends OauthPhirehose
{
    public $tweets = array();
    public $redis;

    public function __construct() {
        $this -> redis = new Redis();
        $this -> redis = $this -> redis -> connect('127.0.0.1',6379);

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
            $data['text'] = urldecode($data['text']);
            $data['text'] = trim(str_replace("#twitbattle", "",urldecode($data['text'] )));

            // check invalid tweet
            $textdata = explode(" ", urldecode($data['text']));
            if(count($textdata) !== 3){
                return;
            }
            if(!((mb_strlen($textdata[0]) == 5) && (mb_strlen($textdata[1]) == 7)&& (mb_strlen($textdata[2]) == 5)))
                return ;

            if(count($this -> tweets) >= 1){
                if($data['user']['screen_name'] == $this->tweets[0]['user']['screen_name']){
                    $this -> tweets[0] = $data;
                    print("この人はすでにDataに入っているので更新処理を行います。¥n");
                } else {
                    print($data['user']['screen_name']."と".$this->tweets[0]['user']['screen_name']."が対戦します。¥n");
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
        while($player1_hp <= 16 || $player2_hp <= 16){
            $player1win = WinLose(mb_substr($player1['text'], $player1_hp,1), mb_substr($player2['text'], $player1_hp, 1));
            $player2win = WinLose(mb_substr($player2['text'], $player2_hp,1), mb_substr($player1['text'], $player2_hp, 1));
            if($player1win == false) {
                $player1_hp++;
            }
            if($player2win == false){
                $player2_hp++;
            }
        }

        $player1_point = $this -> getUserData($player1["screen_name"]);
        $player2_point = $this -> getUserData($player2["screen_name"]);

        $player1_point = $player1_point['point'];
        $player2_point = $player2_point['point'];

        $remain1 = 16 - $player1_hp;
        $remain2 = 16 - $player2_hp;
        if($remain1 > $remain2){
            //プレイヤ1勝利時の処理
            $plusminus = 0.5 * ($player2_point - $player1_point) + $remain1 * 100;
            $this->redis->zIncrBy('points', $plusminus, $player1['screen_name']);
            $this -> redis -> zIncrBy('points', 0-$plusminus, $player2['screen_name']);
            print("player1の勝ちです¥n");

        } else if($remain2 > $remain1) {
            //プレイヤ2勝利時の処理
            $plusminus = 0.5 * ($player1_point - $player2_point) + $remain2 * 100;
            $this -> redis -> zIncrBy('points', $plusminus, $player2['screen_name']);
            $this -> redis -> zIncrBy('points', 0-$plusminus, $player1['screen_name']);
            print("player2の勝ちです¥n");
        }
        print("player1の残り文字数は".$remain1."です。¥player2の残り文字数は".$remain2."です。¥n");
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
        $point = $this->redis->zrank($screen_name);
        if($point == null){
            //新規
            $rate = 1000;
            $this -> redis -> zAdd('points',1000, $screen_name);
            print($screen_name."さんのデータを作ります。¥n");
        } else {
            //更新
            $rate = $this -> redis-> zScore('points', $screen_name);
        }

        return array('screen_name' => $screen_name, 'point' => $rate);
    }
}


// The OAuth credentials you received when registering your app at Twitter
define("TWITTER_CONSUMER_KEY", "3cmPhfsazDRlxAF6BoVWXTtzO");
define("TWITTER_CONSUMER_SECRET", "FscnbGsKYPZsljN8urD1UhnSHDkKXT9n05V6drtubbbzg0wH71");


// The OAuth data for the twitter account
define("OAUTH_TOKEN", "1003250335-pURU26X5R4K1UojCWz8qzcNrzh5tjP7jFIadeI3");
define("OAUTH_SECRET", "SoGkPNluwJyo0Kym6Mwc3Gwfxm73jeYMLapFWk6B1McJf");

// Start streaming
$sc = new FilterTrackConsumer(OAUTH_TOKEN, OAUTH_SECRET, Phirehose::METHOD_FILTER);
$sc->setTrack(array('#twitbattle'));
$sc->consume();