<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Schema;

use Jenssegers\Mongodb\Schema\Blueprint;

use Abraham\TwitterOAuth\TwitterOAuth;

use App\Tweet;

class TweetsController extends Controller
{

    /**
     * Show all tweets
     *
     * @return response
     */
    public function index()
    {
      if (Schema::hasCollection('tweets')) {
        return response()->json([
          'data' => DB::collection('tweets')->get()
        ], 200);
      } else {
        return response()->json([
          'error' => "No tweets in database."
        ], 404);
      }
    }

    /**
     * Show stats for all tweets
     *
     * @return response
     */
    public function stats()
    {
      if (Schema::hasCollection('tweets')) {
        return response()->json([
          'stats' => $this->getStats(),
          'optimalTime' => Tweet::where('links', true)->count()
        ], 200);
      } else {
        return response()->json([
          'error' => "No tweets in database."
        ], 404);
      }
    }

    /**
     * Store a certain number of tweets for a certain handle
     *
     * @param Illuminate\Http\Request $request
     * @param string $handle
     * @param string $numTweets
     * @return response
     */
    public function store(Request $request, $handle, $numTweets)
    {
      $this->dropAndRecreateTweets();

      $statuses = $this->retrieveDataFromTwitter($handle, $numTweets);

      $this->populateDbWithTweets($statuses);

      return response()->json([
        'message' => "Retrieval and storage successful."
      ], 200);
    }

    /**
     * Calculate the stats for a given user
     *
     * @return array
     */
    private function getStats()
    {
      return [
        'numberOfTweets' => Tweet::count(),
        'tweetsWithLinks' => Tweet::where('links', true)->count(),
        'numberOfRetweets' => Tweet::sum('retweets'),
        'avgCharsPerTweet' => round(Tweet::avg('length'))
      ];
    }

    /**
     * Find the optimal time for the user to post
     *
     * @return ??
     */
    private function optimalTime() {
      $mondays = DB::collection('tweets')->where('day', 'Mon')->avg('retweets');
      $tuesdays = DB::collection('tweets')->where('day', 'Tue')->avg('retweets');
      $wednesdays = DB::collection('tweets')->where('day', 'Wed')->avg('retweets');
      $thursdays = DB::collection('tweets')->where('day', 'Thu')->avg('retweets');
      $fridays = DB::collection('tweets')->where('day', 'Fri')->avg('retweets');
      $saturdays = DB::collection('tweets')->where('day', 'Sat')->avg('retweets');
      $sundays = DB::collection('tweets')->where('day', 'Sun')->avg('retweets');
    }

    /**
     * Drop the existing 'tweets' table and create an empty table
     *
     * @return void
     */
    private function dropAndRecreateTweets()
    {
      if (Schema::hasCollection('tweets')) {
        Schema::drop('tweets');
      };

      Schema::create('tweets', function (Blueprint $collection) {
          $collection->index('id');
      });
    }

    /**
     * Retrieve statuses from Twitter of a certain number for a handle
     *
     * @param string $handle
     * @param string $numTweets
     * @return array
     */
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

    /**
     * Iterate through statuses and convert them to tweets
     *
     * @return none
     */
    private function populateDbWithTweets($statuses)
    {
      foreach ($statuses as $status)
      {
        $this->convertToTweet($status);
      }
    }

    /**
     * Convert a status to a Tweet and save it to the database
     *
     * @return none
     */
    private function convertToTweet($status)
    {
      $tweet = new Tweet;

      $tweet->_id = $status->id;
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
