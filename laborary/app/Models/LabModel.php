namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LabModel extends Model
{
    protected $table = 'model'; // 对应数据库model表
    protected $fillable = ['model_name', 'model_desc'];
}