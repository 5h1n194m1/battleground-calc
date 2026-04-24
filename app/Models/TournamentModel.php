<?php

namespace App\Models;

use CodeIgniter\Model;

class TournamentModel extends Model
{
    public const STATUS_BELUM_MULAI = 'belum_mulai';
    public const STATUS_START = 'start';
    public const STATUS_SELESAI = 'selesai';

    protected $table         = 'tournaments';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['name', 'status'];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public static function statusOptions(): array
    {
        return [
            self::STATUS_BELUM_MULAI => 'Standby',
            self::STATUS_START       => 'Live',
            self::STATUS_SELESAI     => 'Finished',
        ];
    }
}
