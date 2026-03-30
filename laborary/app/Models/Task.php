<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\LabModel;

class Task extends Model
{
    protected $table = 'task'; // 对应数据库task表
    protected $fillable = [
        'task_name', 'model_id', 'train_params', 'data_set_id',
        'submit_desc', 'start_time', 'end_time', 'task_status',
        'progress', 'compute_resource'
    ];
    protected $casts = [
        'train_params' => 'json', // 自动解析JSON字段
        'start_time' => 'datetime',
        'end_time' => 'datetime'
    ];

    // 关联模型表
    public function model(): BelongsTo
    {
        return $this->belongsTo(LabModel::class, 'model_id', 'id');
    }

    // 关联数据集表
    public function dataSet(): BelongsTo
    {
        return $this->belongsTo(DataSet::class, 'data_set_id', 'id');
    }

    // 关联批改表
    public function correct(): BelongsTo
    {
        return $this->belongsTo(TaskCorrect::class, 'id', 'task_id');
    }
}
?>