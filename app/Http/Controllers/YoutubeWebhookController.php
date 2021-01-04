<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessUploadedVideo;
use App\Mail\NewVideoUploaded;
use App\Notifications\VideoUpdated;
use Illuminate\Http\Request;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use PhpParser\Node\Expr\Cast\Object_;
use Alaouy\Youtube\Facades\Youtube;
use App\YoutubeChannel;
use Carbon\Traits\Date;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class YoutubeWebhookController extends Controller
{
    public  $video;
    public function subscribe(Request $request, $channel_id)
    {
        //save this channel inorder to resubscribe later
        $cha = new YoutubeChannel();
        $cha->channel_id = $channel_id;
        $cha->save();
        //get videos for this channel
        $videoList = Youtube::listChannelVideos($channel_id, 40);
        foreach ($videoList as $vid) {
            $video_info = $vid;
            $vid_title = $video_info->snippet->title;
            $vid_description = $video_info->snippet->description;
            $vid_id = $video_info->id->videoId;
            $vid_image_url = $video_info->snippet->thumbnails->high->url;
            $vid_duration = "07:00";
            $current_timestamp = Carbon::now()->timestamp;
            $current_timestamp = $current_timestamp * 1000;

            $url = "https://video-book-summaries.kamasupaul.com/services/insertOneVideo";
            $data = (object)[];
            $data->name = Str::limit($vid_title, 100);
            $data->url = $vid_id;
            $data->duration = $vid_duration;
            $data->draft = "1";
            $data->featured = "0";
            $data->description = $vid_description;
            // $data->image = $vid_image_url;
            $data->created_at = $current_timestamp;
            $data->last_update = $current_timestamp;

            //add video to the database
            $response = Http::post($url, (array)$data);
            Log::debug($response);
        }
        // return response((array)$videoList, 200);
        //finally subscribe to the channel using publish subscribe
        return $this->subscribeYoutubeChannel($request, $channel_id);
    }

    public function subscribeYoutubeChannel(Request $request, $channel_id = null, $subscribe = true)
    {
        $subscribe_url = 'https://pubsubhubbub.appspot.com/subscribe';
        $topic_url = 'https://www.youtube.com/xml/feeds/videos.xml?channel_id={CHANNEL_ID}';
        // $callback_url = 'http://' . $_SERVER['SERVER_NAME'] . '/api/callback';
        $callback_url = 'https://book-summaries-api.kamasupaul.com/api/callback';
        $data = array(
            'hub.mode' => $subscribe ? 'subscribe' : 'unsubscribe',
            'hub.callback' => $callback_url,
            'hub.lease_seconds' => 60 * 60 * 24 * 365,
            'hub.topic' => str_replace(array('{CHANNEL_ID}'), array($channel_id), $topic_url)
        );


        $response = Http::asForm()->retry(3, 100)->post($subscribe_url, $data);

        //TODO get previous videos and add them 
        // List videos in a given channel, return an array of PHP objects
        // $videoList = Youtube::listChannelVideos($channel_id, 40);
        return $response;
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
            ProcessUploadedVideo::dispatch($video)
                ->delay(now()->addMinute(3));

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
    public function cron(Request $request)
    {

       // resubscribe only every friday
        $date = Carbon::now();
        if ($date->isFriday()) {
            $channels = YoutubeChannel::all();
            foreach ($channels as $channel) {
                // resubscribe to every channel
                $this->subscribeYoutubeChannel($request, $channel->channel_id);
            }
        }


        return response("okay", 200);
    }
}
