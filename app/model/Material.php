<?php
// app/model/Material.php
namespace app\model;

use think\Model;

class Material extends Model
{
    protected $table = 'material';
    protected $pk = 'id';
    protected $autoWriteTimestamp = false;
}
