<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VideoUpdated extends Notification
{
    use Queueable;

    public $video;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($video1)
    {
        $this->video = $video1;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $video = $this->video;
        $url = "http://www.youtube.com/watch?v={$video->video_id}";
        return (new MailMessage)
            ->greeting("Hello,")
            ->line('A new video  has been uploaded on youtube channel ' . $video->channel_title)
            ->line('with title ')
            ->line($video->title)
            ->line('Click the button below to watch the video')
            ->action('Watch video', $url)
            ->line('If you did not request to receive notifications for this channel please ignore this email')
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
