<?php

namespace App\Controllers;

use App\Models\TournamentModel;

class Dashboard extends BaseController
{
    public function index(): string
    {
        $tournaments = (new TournamentModel())
            ->select('tournaments.*, COUNT(DISTINCT pots.id) AS pot_count, COUNT(DISTINCT teams.id) AS team_count')
            ->join('pots', 'pots.tournament_id = tournaments.id', 'left')
            ->join('teams', 'teams.pot_id = pots.id', 'left')
            ->groupBy('tournaments.id')
            ->orderBy('tournaments.created_at', 'DESC')
            ->findAll();

        return view('dashboard/index', [
            'pageTitle'     => 'Dashboard',
            'tournaments'   => $tournaments,
            'statusOptions' => TournamentModel::statusOptions(),
        ]);
    }
}
