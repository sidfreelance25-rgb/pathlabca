<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferringDoctor extends Model
{
    protected $fillable = ['lab_id', 'name', 'qualification', 'phone', 'registration_number'];
}
