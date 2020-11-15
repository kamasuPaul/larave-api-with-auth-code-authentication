<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Mail\NewVideoUploaded;
use App\Notifications\VideoUpdated;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Alaouy\Youtube\Facades\Youtube;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ProcessUploadedVideo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $video;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($video1)
    {
        $this->video = $video1;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //get other details of the video from youtube api

        $video_info = Youtube::getVideoInfo($this->video->video_id);
        $vid_title = $video_info->snippet->title;
        $vid_description = $video_info->snippet->description;
        $vid_image_url = $video_info->snippet->thumbnails->high->url;
        $vid_duration = $this->convtime($video_info->contentDetails->duration);
        $current_timestamp = Carbon::now()->timestamp;
        $current_timestamp = $current_timestamp * 1000;

        // $url = "http://localhost/admin_project/services/insertOneVideo";
        $url = "https://video-book-summaries.kamasupaul.com/services/insertOneVideo";
        $data = (object)[];
        $data->name = Str::limit($vid_title, 100);
        $data->url = $this->video->video_id;
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
        //send notification
        Notification::route('mail', 'kamasupaul1@gmail.com')
            ->notify(new VideoUpdated($this->video));
    }
    /**
     * convert youtube v3 api duration e.g. PT1M3S to HH:MM:SS
     */
    function convtime($yt)
    {
        $yt = str_replace(['P', 'T'], '', $yt);
        foreach (['D', 'H', 'M', 'S'] as $a) {
            $pos = strpos($yt, $a);
            if ($pos !== false) {
                ${$a} = substr($yt, 0, $pos);
            } else {
                ${$a} = 0;
                continue;
            }
            $yt = substr($yt, $pos + 1);
        }
        if ($D > 0) {
            $M = str_pad($M, 2, '0', STR_PAD_LEFT);
            $S = str_pad($S, 2, '0', STR_PAD_LEFT);
            return ($H + (24 * $D)) . ":$M:$S"; // add days to hours
        } elseif ($H > 0) {
            $M = str_pad($M, 2, '0', STR_PAD_LEFT);
            $S = str_pad($S, 2, '0', STR_PAD_LEFT);
            return "$H:$M:$S";
        } else {
            $S = str_pad($S, 2, '0', STR_PAD_LEFT);
            return "$M:$S";
        }
    }
}
