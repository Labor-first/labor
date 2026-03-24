<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class LabConfig extends Model
{
    use HasFactory;

    protected function serializeDate(\DateTimeInterface $date): string
    {
        return Carbon::instance($date)->setTimezone('Asia/Shanghai')->format('Y-m-d H:i:s');
    }

    protected $table = 'lab_configs';

    protected $fillable = [
        'name',//实验室名称
        'intro',//实验室介绍
        'address',//实验室地址
        'contact',//实验室联系人
    ];
}