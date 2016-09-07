<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class Tweet extends Eloquent
{
    public $timestamps = false;
    protected $dates = ['fulldate'];
}
