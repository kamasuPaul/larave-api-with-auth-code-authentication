<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/register', "AuthController@register");
Route::post('/login', "AuthController@login");
Route::post('/logout', "AuthController@logout");
Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
// Route::middleware('auth:api')->group(function () use ($router) {
    $router->get("books", "BooksController@showAllBooks");
    $router->get("books/{id}/videos", "BooksController@getVideos");
    $router->get("books/{id}", "BooksController@showOneBook");
    $router->post("books/", "BooksController@createBook");
    $router->delete("books/{id}", "BooksController@deleteBook");
    $router->put("books/{id}", "BooksController@updateBook");
    $router->get("sendmessage", "Controller@sendMessage");
// });

Route::middleware('auth:api')->group(function () use ($router) {
    Route::resource('video', 'VideoController');
});
