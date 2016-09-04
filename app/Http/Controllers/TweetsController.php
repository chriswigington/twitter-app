<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

use Abraham\TwitterOAuth\TwitterOAuth;

class TweetsController extends Controller
{
    public function index()
    {
      return "This worked";
    }
}
