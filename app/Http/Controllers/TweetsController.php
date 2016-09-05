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
    public function stats()
    {
      return "these are stats";
    }

    // This is where we're going to store the tweets to mongo
    public function store(Request $request, $handle, $numTweets)
    {

      $connection = new TwitterOAuth(
                          env('CONSUMER_KEY'),
                          env('CONSUMER_SECRET'),
                          env('ACCESS_TOKEN'),
                          env('ACCESS_TOKEN_SECRET')
                        );

      $statuses = $connection->get(
                                "statuses/user_timeline",
                                [
                                  "screen_name" => $handle,
                                  "count" => $numTweets,
                                  "exclude_replies" => true
                                ]);

      $firstStatus = ($statuses[0])->created_at;
      return $statuses;

    }
}
