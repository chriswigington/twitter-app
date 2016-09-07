# Basic Twitter API Laravel App

## Environment Configuration

In order to be able to use this API, you'll need to acquire a Twitter developer account and an access token. When you have that information, you'll need to go into your local .env file in the Laravel project (you can just rename .env.example to .env), and add these fields in with the relevant values:

CONSUMER_KEY=
CONSUMER_SECRET=
ACCESS_TOKEN=
ACCESS_TOKEN_SECRET=

We'll also be using Mongodb for our database, so you'll want to go ahead and update these values in the .env file as well:

DB_CONNECTION=mongodb
DB_HOST=localhost
DB_PORT=27017
DB_DATABASE=twitterapp
DB_USERNAME=
DB_PASSWORD=

The DB_USERNAME and DB_PASSWORD can be any user info you set up to be an authenticated user in mongo for the database we'll be using (here just called 'twitterapp').

## Using the API

To run this locally on your machine, you should be able to navigate to the twitter-app folder and run 'php artisan serve', which should then be running the API on 'http://localhost:8000' by default, so this will prefix all of our API calls, as will 'api/v1/', since this is an API.

To store a certain number of tweets by a certain handle to our database, the path would be:

POST http://localhost:8000/api/v1/tweets/{handle}/{tweets}

where {handle} and {tweets} are replaced by a valid Twitter username and the number of tweets you'd like to capture, respectively. Performing this action first is necessary for any of the other actions to work.
