<?php

namespace App\Http\Controllers;

use App\Mail\NewVideoUploaded;
use App\Notifications\VideoUpdated;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use PhpParser\Node\Expr\Cast\Object_;

class YoutubeWebhookController extends Controller
{
    public  $video;
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
    public function youtube_subscribe_callback(Request $request)
    {
        if ($request->has('hub_challenge')) {

            $video = (object)[];
            $video->title = "Test";
            $video->video_id = "Test Id";
            $video->channel_id = "122324";
            $video->channel_title = "Test channel";
            $video->published = "2015-03-06T21:40:57+00:00";
            $video->updated = "2015-03-06T21:40:57+00:00";
            foreach (['kamasupaul1@gmail.com'] as $recipient) {
                Mail::to($recipient)->queue(new NewVideoUploaded($video));
            }
            $hub_challenge = $request->input('hub_challenge');
            return $hub_challenge;
        } else {

            $video = (object) $this->parseYoutubeUpdate(file_get_contents('php://input'));
            Notification::route('mail', 'kamasupaul1@gmail.com')
                ->notify(new VideoUpdated($video));
            return response("okay", 204);
        }
    }
    function parseYoutubeUpdate($data)
    {
        $xml = simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOCDATA);
        $video_id = substr((string)$xml->entry->id, 9);
        $channel_id = substr((string)$xml->entry->author->uri, 32);
        $channel_title = (string)$xml->entry->author->name;
        $published = (string)$xml->entry->published;
        $updated = (string)$xml->entry->updated;
        $title = (string)$xml->entry->title;

        try {
            $dt_p = Carbon::parse($published);
            $published = $dt_p->toDayDateTimeString();
            $dt_u = Carbon::parse($updated);
            $updated = $dt_p->toDayDateTimeString();
        } catch (\Throwable $th) {
        }


        return array(
            'video_id' => $video_id,
            'channel_id' => $channel_id,
            'channel_title' => $channel_title,
            'published' => $published,
            'updated' => $updated,
            'title' => $title
        );
    }
}
