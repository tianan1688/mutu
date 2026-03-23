<?php
// app/model/MaterialFolder.php
namespace app\model;

use think\Model;

class MaterialFolder extends Model
{
    // 表名（如果表名和模型名一致，可省略）
    protected $table = 'material_folder';
    // 主键
    protected $pk = 'id';
    // 自动时间戳（用int类型）
    protected $autoWriteTimestamp = false;
}