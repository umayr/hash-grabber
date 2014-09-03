<?php
    header('Content-Type: application/json');

    require 'vendor/fb/facebook.php';
    include 'bin/config.php';

    $fb = new Facebook(
        array(
            'appId'  => FBAppID,
            'secret' => FBAppSecret
        ));

    $hashtag = HASHTAG;
    date_default_timezone_set('Asia/Karachi');

    $r = $fb->api('/search?q=' . urlencode($hashtag) . '&type=post&limit=100&fields=id,from.id,from.name,message,type,created_time');
    $r = $r['data'];

    $statuses = array();
    foreach ($r as $i) {
        if ($i['type'] == 'status') {
            //$i['created_time'] = date("F j, Y, g:i a", strtotime($i['created_time']));
            $statuses[] = format_object($i);
        }
    }
    if (isset($_GET['LID'])) {
        $id = $_GET['LID'];
        $latest = array();
        for ($i = 0; $i < count($statuses); $i++) {
            if ($statuses[$i]['id'] == $id) {
                break;
            } else {
                $latest[] = $statuses[$i];
            }
        }
        $statuses = $latest;
    }
    echo json_encode($statuses);

    function format_object($raw)
    {
        return array(
            'id'          => $raw['id'],
            'type'        => 'facebook',
            'picture_url' => 'http://graph.facebook.com/' . $raw['from']['id'] . '/picture',
            'status'      => $raw['message'],
            'user_id'     => $raw['from']['id'],
            'user_name'   => $raw['from']['name'],
            'time'        => date("F j, Y, g:i a", strtotime($raw['created_time'])),
            'utc'         => strtotime($raw['created_time']));
    }