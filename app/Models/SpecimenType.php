<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpecimenType extends Model
{
    protected $fillable = ['code', 'name', 'container', 'additive', 'handling_instructions'];
}
