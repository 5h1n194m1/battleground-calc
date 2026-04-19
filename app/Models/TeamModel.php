<?php

namespace App\Models;

use CodeIgniter\Model;

class TeamModel extends Model
{
    protected $table         = 'teams';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['pot_id', 'name', 'sort_order'];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
