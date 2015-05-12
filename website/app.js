$(document).ready(function() {
    var serverName= "http://localhost:8000";
    $.getJSON(serverName+"/ranking.php", function(ranking ){

        $.each(ranking['point'],function(i, v) {
            //var str = "<li>"+i+": "+v+" point<li>";
            console.log(v);
            var str = "<li>"+i+":　レート "+v['point']+"　対戦回数"+v['battle_count']+"回　勝利回数"+v['win_count']+"回　勝率"+v['win_rate']+"%</li><br>";
            $(".ranking").prepend(str);
        });
        $.each(ranking['battle_count'],function(i, v) {
            //var str = "<li>"+i+": "+v+" point<li>";
            console.log(v);
            var str = "<li>"+i+":　レート "+v['point']+"　対戦回数"+v['battle_count']+"回　勝利回数"+v['win_count']+"回　勝率"+v['win_rate']+"%</li><br>";
            $(".ranking_battlecount").prepend(str);
        });
        $.each(ranking['win_count'],function(i, v) {
            //var str = "<li>"+i+": "+v+" point<li>";
            console.log(v);
            var str = "<li>"+i+":　レート "+v['point']+"　対戦回数"+v['battle_count']+"回　勝利回数"+v['win_count']+"回　勝率"+v['win_rate']+"%</li><br>";
            $(".ranking_wincount").prepend(str);
        });
        $.each(ranking['win_rate'],function(i, v) {
            //var str = "<li>"+i+": "+v+" point<li>";
            console.log(v);
            var str = "<li>"+i+":　レート "+v['point']+"　対戦回数"+v['battle_count']+"回　勝利回数"+v['win_count']+"回　勝率"+v['win_rate']+"%</li><br>";
            $(".ranking_winrate").prepend(str);
        });
    });
    $(".button").on('click', function () {

        var screen_name = $(".screen_name").val();
        $.getJSON(serverName+"/website.php",{"screen_name":screen_name},function(data){
            console.log("test");
            //追加
            $(".user_result").text(data['screen_name']+"さんの情報です。");
            $(".user_point").text("レート: "+data['point']);
            $(".user_battle_count").text("バトル回数: "+data['battle_count']);
            $(".user_win_count").text("勝利回数: "+data['win_count']);
            $(".user_win_rate").text("勝率: "+data['win_rate']+"%");

           // "現在のポイントは"+data['point']+"です。\n");
            //追加終わり

            //$(".user_result").prepend(data['screen_name']+"さんの現在のポイントは"+data['point']+"です。\n");
        });
    });
});
