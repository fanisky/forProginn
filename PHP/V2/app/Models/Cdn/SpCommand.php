<?php

namespace App\Models\Cdn;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SpCommand extends Model
{
    use SoftDeletes;

    protected $table = 'cdn_spcommand';
}
