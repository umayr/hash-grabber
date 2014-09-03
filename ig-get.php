<?php
//header('Content-Type: application/json');
include 'bin/config.php';
include 'bin/instagram.class.php';


$hashtag = substr(HASHTAG, 1);
date_default_timezone_set('Asia/Karachi');

$instagram = new Instagram(array(
    'apiKey' => 'f31c2ee097d64105a1e3166e19b563b5',
    'apiSecret' => '6ae2ee0b897440fea46d8f97e7c234ce',
    'apiCallback' => ''
));


$data = $instagram->getTagMedia('fml');

$data = $data['data'];

if (isset($_GET['LID'])) {
    $id = $_GET['LID'];
    $latest = array();
    for ($i = 0; $i < count($data); $i++) {
        if ($data[$i]['id'] == $id) {
            break;
        } else {
            $latest[] = $data[$i];
        }
    }
    $data = $latest;
}

echo(json_encode(format_array($data)));

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
        'id' => $raw['id'],
        'type' => 'instagram',
        'picture_url' => $raw['user']['profile_picture'],
        'status' => $raw['caption']['text'],
        'image' => $raw['images']['standard_resolution']['url'],
        'user_id' => $raw['user']['id'],
        'user_name' => $raw['user']['full_name'],
        'time' => date("F j, Y, g:i a", $raw['created_time']),
        'utc' => intval($raw['created_time'], 0));
}

