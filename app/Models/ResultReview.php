<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResultReview extends Model
{
    protected $fillable = ['result_id', 'user_id', 'decision', 'reason'];
}
