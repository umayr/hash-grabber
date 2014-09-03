<?php
header('Content-Type: application/json');

require 'bin/twitter/twitteroauth.php';
include 'bin/config.php';

$hashtag = HASHTAG;
date_default_timezone_set('Asia/Karachi');

$con = new TwitterOAuth(TWkey, TWSecret, TWAccessToken, TWAccessTokenSecret);

if (isset($_GET['LID'])) {
    $id = $_GET['LID'];
    $data = $con->get("https://api.twitter.com/1.1/search/tweets.json",
        array(
            'q' => $hashtag,
            'result_type' => 'recent',
            'count' => '20',
            'since_id' => $id
        ));
}
else{
    $data = $con->get("https://api.twitter.com/1.1/search/tweets.json",
        array(
            'q' => $hashtag,
            'result_type' => 'recent',
            'count' => '20'
        ));
}

$data = $data->statuses;

$data = format_array($data);

echo json_encode($data);

function format_array($array)
{
    $t = array();
    foreach ($array as $o) {
        // add check if only images.
        $t[] = format_object($o);
    }
    return $t;

}

function format_object($raw)
{
    return array(
        'id' => $raw->id,
        'type' => 'twitter',
        'picture_url' => $raw->user->profile_image_url,
        'status' => $raw->text,
        'user_id' => $raw->user->id,
        'user_name' => '@'.$raw->user->screen_name,
        'time' => date("F j, Y, g:i a", strtotime($raw->created_at)),
        'utc' => strtotime($raw->created_at));
}