<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Phoneorganizationmapping extends Model
{
    protected $table = 'numbers_organizations';
    protected $timestamp = false;
}
