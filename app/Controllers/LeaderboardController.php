<?php

namespace App\Controllers;

use App\Models\PotModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class LeaderboardController extends BaseController
{
    private PotModel $potModel;

    public function __construct()
    {
        $this->potModel = new PotModel();
    }

    public function pot(string $potId): string
    {
        $pot = $this->potModel
            ->select('pots.*, tournaments.name AS tournament_name, tournaments.id AS tournament_id')
            ->join('tournaments', 'tournaments.id = pots.tournament_id')
            ->find($potId);

        if ($pot === null) {
            throw PageNotFoundException::forPageNotFound('Pot tidak ditemukan.');
        }

        $rows = db_connect()->table('teams')
            ->select('teams.id, teams.name, teams.sort_order, COALESCE(SUM(scores.total_point), 0) AS total_score, COALESCE(SUM(scores.kill_point), 0) AS total_kill, COALESCE(SUM(scores.placement_point), 0) AS total_placement, COUNT(DISTINCT scores.game_no) AS games_played')
            ->join('scores', 'scores.team_id = teams.id', 'left')
            ->where('teams.pot_id', $potId)
            ->groupBy('teams.id')
            ->orderBy('total_score', 'DESC')
            ->orderBy('teams.name', 'ASC')
            ->get()
            ->getResultArray();

        return view('leaderboard/pot', [
            'pageTitle' => 'Leaderboard Pot',
            'pot' => $pot,
            'leaderboard' => $rows,
        ]);
    }
}
