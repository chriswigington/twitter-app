<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class Tweet extends Eloquent
{
    //
    //public $primaryKey = 'id';
    //public $incrementing = false;
    public $timestamps = false;

    // protected $hidden = [‘id_’];
}
