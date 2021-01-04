<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class YoutubeChannel extends Model
{
    /**
     * The connection name for the model.
     *
     */
    // protected $connection = 'sqlite';

    protected $fillable = [
        'channel_id'
    ];
}
