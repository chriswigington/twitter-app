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
          'optimalTime' => $this->optimalTime()
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
     * @return string
     */
    private function optimalTime() {
      $daysOfWeek = [
        'Monday' => 'Mon', 'Tuesday' => 'Tue',
        'Wednesday' => 'Wed', 'Thursday' => 'Thu',
        'Friday' => 'Fri', 'Saturday' => 'Sat',
        'Sunday' => 'Sun'
      ];

      $hoursOfTheDay = [
        'midnight' => '00', '1AM' => '01', '2AM' => '02',
        '3AM' => '03', '4AM' => '04', '5AM' => '05',
        '6AM' => '06', '7AM' => '07', '8AM' => '08',
        '9AM' => '09', '10AM' => '10', '11AM' => '11',
        'noon' => '12', '1PM' => '13', '2PM' => '14',
        '3PM' => '15', '4PM' => '16', '5PM' => '17',
        '6PM' => '18', '7PM' => '19', '8PM' => '20',
        '9PM' => '21', '10PM' => '22', '11PM' => '23',
      ];

      $days = [];
      $hours = [];
      $days_hours =[];

      foreach ($daysOfWeek as $key => $value) {
        $days[$key] = DB::collection('tweets')->where('day', $value)->avg('retweets');
      }

      foreach ($hoursOfTheDay as $key => $value) {
        $hours[$key] = DB::collection('tweets')->where('hour', $value)->avg('retweets');
      }

      foreach ($daysOfWeek as $weekday => $day) {
        foreach($hoursOfTheDay as $time => $hour) {
         $days_hours[$weekday][$time] = DB::collection('tweets')
                                        ->where('day', $day)
                                        ->where('hour', $hour)
                                        ->avg('retweets');
        }
      }

      foreach ($days_hours as $weekday => $timesArray) {
        $days_hours[$weekday] = $this->firstKey($timesArray);
      }

      $day = $this->firstKey($days);
      $hour = $this->firstKey($hours);


      return var_dump($days_hours);

      //"$day was the day of the week with the highest number of retweets, "
      //        ."while $hour was the time of day with the most retweets.";
    }

    /**
     * Return the first key from an array
     *
     * @param array $array
     * @return string
     */
    private function firstKey(array $array)
    {
      arsort($array);
      reset($array);
      return [key($array), current($array)];
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
