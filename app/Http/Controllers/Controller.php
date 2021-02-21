<?php

namespace App\Http\Controllers;

use App\Book;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function post_to_facebook(Request $request)
    {
        //generate arandom book
        $book = Book::inRandomOrder()->first();
        if($book){
        $post_text = Str::replaceFirst('1-Sentence-Summary', Str::upper($book->title), $book->short_summary);
        $post_image = $book->image_url;
        $data = [
            'post_text' => "$post_text \r\n \r\n Use the link below 👇 to download the app and read the full book summary
            \r\n https://play.google.com/store/apps/details?id=com.softappsuaganda.booksumaries&hl=en ",
            'post_image_url' => $post_image,
            'link' => "https://play.google.com/store/apps/details?id=com.softappsuaganda.booksumaries&hl=en",
            'link_text' =>""
        ];
        $url = env('NOTIFICATION_API_URL');
        $response = Http::post($url, $data);
        return $response;
        }

    }
}
