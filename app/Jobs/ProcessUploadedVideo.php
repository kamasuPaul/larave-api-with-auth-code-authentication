<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Mail\NewVideoUploaded;
use App\Notifications\VideoUpdated;
use Illuminate\Support\Facades\Notification;

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

        Notification::route('mail', 'kamasupaul1@gmail.com')
            ->notify(new VideoUpdated($this->video));
    }
}
