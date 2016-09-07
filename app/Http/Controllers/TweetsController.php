<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Http\Requests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Jenssegers\Mongodb\Schema\Blueprint;
use Abraham\TwitterOAuth\TwitterOAuth;
use App\Tweet;
use DateTime;
use DateInterval;

class TweetsController extends Controller
{
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
        'message' => "Retrieval and storage was successful."
      ], 200);
    }

    /**
     * Show all tweets
     *
     * @return response
     */
    public function index()
    {
      if (Schema::hasCollection('tweets')) {
        return response()->json([
          'data' => DB::collection('tweets')->get(['text', 'date', 'retweets', 'favorites'])
        ], 200);
      } else {
        return response()->json([
          'error' => "No tweets in database."
        ], 404);
      }
    }

    /**
     * Show stats for all tweets in database
     *
     * @return response
     */
    public function stats()
    {
      if (Schema::hasCollection('tweets')) {
        return response()->json([
          'stats' => $this->getStats()
        ], 200);
      } else {
        return response()->json([
          'error' => "No tweets in database."
        ], 404);
      }
    }

    /**
     * Find the amount associated with a given field for a given amount of time
     *
     * @param Request $request
     * @param string $field
     * @param string $startDate
     * @param string $endDate
     * @param string $scale="day"
     * @return response
     */
    public function fieldCount(Request $request, string $field, string $startDate, string $endDate, string $scale="day")
    {
      $results = [];

      $dates = [];

      if ($scale == 'year') {
        $interval = DateInterval::createFromDateString('1 year');
      } elseif ($scale == 'month') {
        $interval = DateInterval::createFromDateString('1 month');
      } else {
        $interval = DateInterval::createFromDateString('1 day');
      }

      $rangeStart = DateTime::createFromFormat('m-d-Y h:i:s', "$startDate 00:00:00");

      $rangeEnd = DateTime::createFromFormat('m-d-Y h:i:s', "$endDate 00:00:00")->add($interval);

      while ($rangeStart <= $rangeEnd)
      {
        $dates[] = $rangeStart->format('m-d-Y');
        $rangeStart->add($interval);
      }

      for ($i = 0; $i < count($dates)-1; $i++)
      {
        $firstDate = $dates[$i];
        $nextDate = $dates[$i+1];
        
        $value = Tweet::where('fulldate', '>=', DateTime::createFromFormat('m-d-Y h:i:s', "$firstDate 00:00:00"))
                        ->where('fulldate', '<', DateTime::createFromFormat('m-d-Y h:i:s', "$nextDate 00:00:00"))
                        ->sum($field);

        if ($value == null) {
          $value = 0;
        }

        $results[] =
        [
          'value' => $value,
          'date' => $firstDate,
        ];
      }

      return response()->json([
        $field => $results
      ], 200);
    }

    /**
     * Find the optimal time for the user to post
     *
     * @param Request $request
     * @param string $field
     * @return response
     */
    public function optimalTime(Request $request, string $field='retweets') {
      $days = [];
      foreach (DB::collection('tweets')->groupBy('weekday')->get() as $object) {
        $days[$object['weekday']] = DB::collection('tweets')->where('weekday', $object['weekday'])->avg($field);
      }

      $hours = [];
      foreach (DB::collection('tweets')->groupBy('hour')->get() as $object) {
        $hours[$object['hour']] = DB::collection('tweets')->where('hour', $object['hour'])->avg($field);
      }

      $day = $this->firstKey($days);
      $hour = $this->firstKey($hours);

      return response()->json([
        'message' => "$day was the day of the week with the highest number of $field, "
               ."while $hour:00 was the time of day with the most $field."
      ], 200);
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
      foreach ($statuses as $status) { $this->convertToTweet($status); }
    }

    /**
     * Convert a status to a Tweet and save it to the database
     *
     * @return none
     */
    private function convertToTweet($status)
    {
      $tweet = new Tweet;

      $date = $this->convertDateFromTweet($status->created_at);

      $tweet->_id = $status->id;
      $tweet->weekday = $date->format('l');
      $tweet->hour = $date->format('h');
      $tweet->day = $date->format('d');
      $tweet->month = $date->format('F');
      $tweet->year = $date->format('Y');
      $tweet->date = $date->format('m-d-Y');
      $tweet->fulldate = $date;
      $tweet->text = $status->text;
      $tweet->length = strlen($status->text);
      $tweet->retweets = $status->retweet_count;
      $tweet->favorites = $status->favorite_count;
      $tweet->links = (bool) count($status->entities->urls);

      $tweet->save();
    }

    /**
     * Convert the incoming date info to PHP DateTime object
     *
     * @return none
     */
    private function convertDateFromTweet($date)
    {
      return DateTime::createFromFormat('D M d H:i:s O Y', $date);
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
     * Return the first key from an array
     *
     * @param array $array
     * @return string
     */
    private function firstKey(array $array)
    {
      arsort($array);
      reset($array);
      return key($array);
    }
}
