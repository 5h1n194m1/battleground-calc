<?php

namespace App\Controllers;

use App\Models\PotModel;
use App\Models\RegistrationModel;
use App\Models\ScoreModel;
use App\Models\TeamModel;
use App\Models\TournamentModel;

class Dashboard extends BaseController
{
    public function index(): string
    {
        $db = db_connect();

        $stats = [
            'tournaments'   => (new TournamentModel())->countAllResults(),
            'pots'          => (new PotModel())->countAllResults(),
            'teams'         => (new TeamModel())->countAllResults(),
            'scores'        => (new ScoreModel())->countAllResults(),
            'registrations' => (new RegistrationModel())->countAllResults(),
        ];

        $recentTournaments = (new TournamentModel())
            ->orderBy('created_at', 'DESC')
            ->findAll(5);

        $topPots = $db->table('pots')
            ->select('pots.id, pots.name, tournaments.name AS tournament_name, COALESCE(SUM(scores.total_point), 0) AS total_score')
            ->join('tournaments', 'tournaments.id = pots.tournament_id')
            ->join('scores', 'scores.pot_id = pots.id', 'left')
            ->groupBy('pots.id')
            ->orderBy('total_score', 'DESC')
            ->orderBy('pots.name', 'ASC')
            ->limit(5)
            ->get()
            ->getResultArray();

        return view('dashboard/index', [
            'pageTitle'         => 'Dashboard',
            'stats'             => $stats,
            'recentTournaments' => $recentTournaments,
            'topPots'           => $topPots,
        ]);
    }
}
