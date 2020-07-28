<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Book extends Model
{
    protected $fillable = [
        'title','authors','year','isbn','page_count','image_url','description','short_summary','long_summary'
    ];
}
