<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Models\LeaderboardModel;

class LeaderboardController extends ResourceController
{
    protected $format = 'json';

    /**
     * Endpoint: GET /api/leaderboard/(:segment)
     */
    public function show($potId = null)
    {
        if (!$potId || !ctype_digit((string) $potId)) {
            return $this->failValidationError('Pot ID wajib disertakan dan harus berupa angka.');
        }

        $model = new LeaderboardModel();
        $leaderboard = $model->getLeaderboardByPot((int) $potId);

        if (empty($leaderboard)) {
            return $this->failNotFound('Data klasemen tidak ditemukan untuk Pot ini.');
        }

        return $this->respond([
            'status'  => 200,
            'message' => 'Klasemen berhasil diambil',
            'data'    => $leaderboard,
        ]);
    }
}