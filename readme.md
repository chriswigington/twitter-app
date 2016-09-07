# Basic Twitter API Laravel App

## Environment Configuration

The application uses the TwitterOAuth library in order to communicate with the Twitter REST API, so in order to use it, you'll need to acquire a Twitter developer account and an access token. When you have that information, you'll need to go into your local .env file in the Laravel project (you can just rename .env.example to .env), and add these fields with the relevant values filled in:

CONSUMER_KEY=

CONSUMER_SECRET=

ACCESS_TOKEN=

ACCESS_TOKEN_SECRET=

We'll also be using Mongodb for our database (using the Moloquent library), so you'll need to have the MongoDB PHP driver installed, and you'll want to update these values in the .env file as well:

DB_CONNECTION=mongodb

DB_HOST=localhost

DB_PORT=27017

DB_DATABASE=twitterapp

DB_USERNAME=

DB_PASSWORD=

The DB_USERNAME and DB_PASSWORD can be any user info you set up to be an authenticated user in mongo for the database we'll be using (here just called 'twitterapp').

## Using the API

To run this locally on your machine, you should be able to navigate to the twitter-app folder and run 'php artisan serve', which should then be running the API on 'http://localhost:8000' by default. As this is an API, all of our HTTP requests will be prefixed with 'api/v1/'.

### Storing Tweets in the Database

Before we can use any of the other features, we first have to populate our database with some tweets. To store a certain number of tweets by a certain handle to our database, the path is:

POST http://localhost:8000/api/v1/tweets/{handle}/{tweets}

where {handle} and {tweets} are replaced by a valid Twitter username and the number of tweets you'd like to capture, respectively. Performing this action first is necessary for any of the other actions to work.

### Seeing that the tweets were retrieved

To retrieve the collection of all of the tweets and some basic information on each of them, the call is:

GET http://localhost:8000/api/v1/tweets

alone, which returns a JSON object with a single field called "data" with points to an array of objects containing the information on each tweet.

To get basic stats on the collection of tweets that we've stored in the database, you can call:

GET http://localhost:8000/api/v1/stats

You can dig in a little deeper by specifying a field that you'd like to look at, as well as a date range, like so:

GET http://localhost:8000/api/v1/stats/{field}/{startDate}/{endDate}

Here {field} can currently be either "retweets" or "favorites" (if we added any other integer field to our tweet data as it gets passed in, we could use that as well), and the dates need to be in the format of "MM-DD-YYYY". So, for instance, http://localhost:8000/api/v1/stats/retweets/09-05-2016/09-07-2016 would return information on the number of retweets for each day between the 5th and 7th of September, 2016.

If we want to specify even further, we can add an additional parameter:

GET http://localhost:8000/api/v1/stats/{field}/{startDate}/{endDate}/{scale}

{scale} (for lack of a better term) simply refers to the granularity you'd like to group the results by. The options are "year", "month", and "day". If you don't include a parameter, or put in anything aside from those options, it will simply act as if "day" had been chosen.

Last but not least, if you'd like to see what the optimal time is to tweet, I've created an additional endpoint:

GET http://localhost:8000/api/v1/optimaltime/{field}

{field} defaults to "retweets", but you can replace that with "favorites" (or anything else, if there were more numeric fields we were tracking), and it will return a string letting you know which day had the highest average stats in that field, as well as which time of day had the highest average in that field.

## Difficulties and Further Refinements

This was my very first attempt at using PHP, Laravel, or MongoDB, and there are definitely some improvements I'd like to make to the project when I have more time. First and foremost is that currently all of my logic is living in my controller, so one of the first things I'd like to do is some further refactoring, and move methods over to models or service containers when I learn a little more about the Laravel architecture.

The limitations of the
