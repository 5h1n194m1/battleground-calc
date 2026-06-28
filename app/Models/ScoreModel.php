<?php

namespace App\Models;

use CodeIgniter\Model;

class ScoreModel extends Model
{
    protected $table            = 'scores';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $allowedFields    = [
        'pot_id', 'team_id', 'game_no', 'rank_no',
        'kill_point', 'placement_point', 'total_point',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}