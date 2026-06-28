<?php

namespace App\Models;

use CodeIgniter\Model;

class LeaderboardModel extends Model
{
    protected $table      = 'teams';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    /**
     * Ambil klasemen berdasarkan Pot ID dan urutkan berdasarkan poin tertinggi.
     * Menggunakan query builder karena view v_leaderboard_pot tidak ada di database.
     */
    public function getLeaderboardByPot(int $potId): array
    {
        return $this->select('teams.id AS team_id, teams.name AS team_name, teams.sort_order, COALESCE(SUM(scores.total_point), 0) AS total_score, COALESCE(SUM(scores.kill_point), 0) AS total_kill, COALESCE(SUM(scores.placement_point), 0) AS total_placement, COUNT(DISTINCT scores.game_no) AS games_played')
            ->join('scores', 'scores.team_id = teams.id', 'left')
            ->where('teams.pot_id', $potId)
            ->groupBy('teams.id')
            ->orderBy('total_score', 'DESC')
            ->orderBy('teams.name', 'ASC')
            ->findAll();
    }
}