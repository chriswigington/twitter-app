<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Tweet;
use App\Http\Requests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Jenssegers\Mongodb\Schema\Blueprint;
use Abraham\TwitterOAuth\TwitterOAuth;

class TweetsController extends Controller
{
    // Retrieve tweets from database
    public function index()
    {
      if (Schema::hasCollection('tweets')) {
        $tweets = DB::collection('tweets')->get();
        return response()->json([
          'data' => $tweets
        ], 200);
      } else {
        return response()->json([
          'error' => "No tweets in database."
        ], 404);
      }
    }

    public function stats()
    {
      if (Schema::hasCollection('tweets')) {
        $tweets = DB::collection('tweets')->get();
        return response()->json([

        ], 200);
      } else {
        return response()->json([
          'error' => "No tweets in database."
        ], 404);
      }
    }

    // Store specified number of tweets from certain handle to database
    public function store(Request $request, $handle, $numTweets)
    {
      $this->dropAndRecreateTweets();

      $statuses = $this->retrieveDataFromTwitter($handle, $numTweets);

      $this->populate($statuses);

      return response()->json([
        'message' => "Retrieval and storage successful."
      ], 200);
    }

    private function dropAndRecreateTweets()
    {
      if (Schema::hasCollection('tweets')) {
        Schema::drop('tweets');
      };

      Schema::create('tweets', function (Blueprint $collection) {
          $collection->index('id');
      });
    }

    private function retrieveDataFromTwitter($handle, $numTweets)
    {
      $connection = new TwitterOAuth(
                          env('CONSUMER_KEY'),
                          env('CONSUMER_SECRET'),
                          env('ACCESS_TOKEN'),
                          env('ACCESS_TOKEN_SECRET')
                        );

      return $connection->get(
                            "statuses/user_timeline",
                            [
                              "screen_name" => $handle,
                              "count" => $numTweets,
                              "exclude_replies" => true,
                              "include_rts" => false
                            ]);
    }

    private function populate($statuses)
    {
      foreach ($statuses as $status)
      {
        $this->convertToTweet($status);
      }
    }

    private function convertToTweet($status)
    {
      $tweet = new Tweet;

      $tweet->id = $status->id;
      $tweet->day = substr($status->created_at, 0, 3);
      $tweet->hour = substr($status->created_at, 11, 2);
      $tweet->text = $status->text;
      $tweet->length = strlen($status->text);
      $tweet->retweets = $status->retweet_count;
      $tweet->favorites = $status->favorite_count;
      $tweet->links = (bool) count($status->entities->urls);

      $tweet->save();
    }
}
