<?php

namespace App\Models;

use Illuminate\Console\OutputStyle;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader as CsvReader;

class Sale extends Model
{
    public $incrementing = false;
    protected $guarded = [];
    protected $keyType = 'string';

}
