<?php

namespace App\Models;

use CodeIgniter\Model;

class TeamMemberModel extends Model
{
    protected $table         = 'team_members';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['team_id', 'registration_id', 'player_name', 'player_role'];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
