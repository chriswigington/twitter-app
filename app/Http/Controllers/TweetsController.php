<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

use Illuminate\Support\Facades\DB;

use Abraham\TwitterOAuth\TwitterOAuth;

class TweetsController extends Controller
{
    // This is where we're going to retrieve the tweets from mongo
    public function index()
    {
      $tweets = DB::collection('tweets')->get();
      // $tweets = array(env('DB_DATABASE'), env('DB_USERNAME'), env('DB_PASSWORD'));
      return $tweets;
    }

    // Show a specific tweet and its stats
    public function show()
    {
      return "these are stats";
    }

    // This is where we're going to store the tweets to mongo
    public function store()
    {

      $connection = new TwitterOAuth(
                          '9dOVWu3EUGnZYs9zcIaGYGbLt',
                          'QM8faHe5pVhNKDBJiwCy7S78wOcPJTX9lnCcLGKr31mhDt82ro',
                          '87588001-nlRnMIC7EFNiCsYq5p0yPw77zAvcKRbaWQhFNRtKM',
                          'f2z4OGvRhhwljPLE4V8HRD41JNRX8MDdY3RffSEiudQjD'
                        );
      $statuses = $connection->get(
                                "statuses/user_timeline",
                                [
                                  "screen_name" => "chriswigington",
                                  "count" => 25,
                                  "exclude_replies" => true
                                ]);

    }
}
