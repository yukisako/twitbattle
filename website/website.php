<?php
/**
 * Created by PhpStorm.
 * User: Yuki_S
 * Date: 2015/05/11
 * Time: 17:59
 */
    $screen_name = $_GET['screen_name'];
    $redis = new Redis();
    $redis -> connect('127.0.0.1',6379);
    $point = $redis -> zScore('points', $screen_name);
    $win_count = $redis -> hGet($screen_name, "win_count");
    $battle_count = $redis -> hGet($screen_name, "battle_count");
    $win_rate = $redis -> hGet($screen_name, "win_rate");


$output = array(
    'screen_name' => $screen_name,
    'point' => $point,
    'win_count' => $win_count,
    'battle_count' => $battle_count,
    'win_rate'=> $win_rate,

);

echo json_encode($output);


