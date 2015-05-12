<?php
/**
 * Created by PhpStorm.
 * User: Yuki_S
 * Date: 2015/05/11
 * Time: 18:30
 */
    $redis = new Redis();
    $redis -> connect('127.0.0.1',6379);
    $redis -> zScore('points', $screen_name);
    $response = array();
    $result = array();

    $ranking = $redis -> zRange('points', 0, 30, true);
   // $ranking2 = $redis -> hGetAll('yuki_99_s');
    foreach($ranking as $screen_name => $point){
        $win_count = $redis -> hGet($screen_name, "win_count");
        $battle_count = $redis -> hGet($screen_name, "battle_count");
        $win_rate = $redis -> hGet($screen_name, "win_rate");

        $result[$screen_name] = array();
        $result[$screen_name]['point'] = $point;
        $result[$screen_name]['win_count'] = $win_count;
        $result[$screen_name]['battle_count'] = $battle_count;
        $result[$screen_name]['win_rate'] = $win_rate;
    }
    $response['point'] = $result;
    //echo json_encode($result);
    $ranking_battlecount = $redis -> sort('points', array(
        'sort' => 'asc',
        'limit' => '0, 30',
        'by' => '*->battle_count'
    ));
    $result = array();
    foreach($ranking_battlecount as $screen_name){
        $win_count = $redis -> hGet($screen_name, "win_count");
        $battle_count = $redis -> hGet($screen_name, "battle_count");
        $win_rate = $redis -> hGet($screen_name, "win_rate");
        $point = $redis -> zScore("points",$screen_name);

        $result[$screen_name] = array();
        $result[$screen_name]['point'] = $point;
        $result[$screen_name]['win_count'] = $win_count;
        $result[$screen_name]['battle_count'] = $battle_count;
        $result[$screen_name]['win_rate'] = $win_rate;
    }
    $response['battle_count'] = $result;
    echo json_encode($response);
/*
  //  echo json_encode($ranking);

    $point = $redis -> zScore('points', $screen_name);
    $win_count = $redis -> hGet($screen_name, "win_count");
    $battle_count = $redis -> hGet($screen_name, "battle_count");
    $win_rate = $redis -> hGet($screen_name, "win_rate");


    $ranking3 = array(
        'screen_name' => $screen_name,
        'point' => $point,
        'win_count' => $win_count,
        'battle_count' => $battle_count,
        'win_rate'=> $win_rate,
    );
    echo json_encode($ranking3);

*/