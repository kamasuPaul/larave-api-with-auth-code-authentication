<?php

namespace App\Http\Controllers;

use App\Mail\NewVideoUploaded;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class YoutubeWebhookController extends Controller
{
    public $video;
    public function subscribe(Request $request, $channel_id)
    {
        // var_dump(openssl_get_cert_locations());

        return $this->subscribeYoutubeChannel($request, $channel_id);
    }

    public function subscribeYoutubeChannel(Request $request, $channel_id = null, $subscribe = true)
    {
        $subscribe_url = 'https://pubsubhubbub.appspot.com/subscribe';
        $topic_url = 'https://www.youtube.com/xml/feeds/videos.xml?channel_id={CHANNEL_ID}';
        $callback_url = 'http://' . $_SERVER['SERVER_NAME'] . '/api/callback';
        $data = array(
            'hub.mode' => $subscribe ? 'subscribe' : 'unsubscribe',
            'hub.callback' => $callback_url,
            'hub.lease_seconds' => 60 * 60 * 24 * 365,
            'hub.topic' => str_replace(array('{CHANNEL_ID}'), array($channel_id), $topic_url)
        );

        $response = Http::asForm()->retry(3, 100)->post($subscribe_url, $data);
        return $response;

        // $opts = array('http' =>
        // array(
        //     'method'  => 'POST',
        //     'header'  => 'Content-type: application/x-www-form-urlencoded',
        //     'content' => http_build_query($data)
        // ));

        // $context  = stream_context_create($opts);

        // return @file_get_contents($subscribe_url, false, $context);

        // return  preg_match('200', $request->headers_list[0]) === 1;
    }
    public function youtube_subscribe_callback()
    {
        if (isset($_GET['hub_challenge'])) {
            echo $_REQUEST['hub_challenge'];
        } else {

            $video = $this->parseYoutubeUpdate(file_get_contents('php://input'));
            foreach (['keypaul.kp@gmail.com'] as $recipient) {
                Mail::to($recipient)->send(new NewVideoUploaded($video));
            }
        }
    }
    function parseYoutubeUpdate($data)
    {
        $xml = simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOCDATA);
        $video_id = substr((string)$xml->entry->id, 9);
        $channel_id = substr((string)$xml->entry->author->uri, 32);
        $published = (string)$xml->entry->published;
        $title = (string)$xml->entry->title;

        return array(
            'video_id' => $video_id,
            'channel_id' => $channel_id,
            'published' => $published,
            'title' => $title
        );
    }
}
