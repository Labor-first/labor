<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataSet extends Model
{
    protected $table = 'data_set'; // 对应数据库data_set表
    protected $fillable = ['data_set_name', 'data_set_desc'];
}
?>