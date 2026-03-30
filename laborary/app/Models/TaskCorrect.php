namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskCorrect extends Model
{
    protected $table = 'task_correct'; // 对应数据库task_correct表
    protected $fillable = [
        'task_id', 'correct_status', 'score', 'comment', 'corrector_id'
    ];
    protected $casts = [
        'created_at' => 'datetime'
    ];
}