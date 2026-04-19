<?php

namespace App\Models;

use CodeIgniter\Model;

class PotModel extends Model
{
    protected $table         = 'pots';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['tournament_id', 'name', 'sort_order'];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
