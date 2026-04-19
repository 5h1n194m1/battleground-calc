<?php

namespace App\Controllers;

use App\Models\PotModel;
use App\Models\ScoreModel;
use App\Models\TeamModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class ScoreController extends BaseController
{
    private PotModel $potModel;
    private TeamModel $teamModel;
    private ScoreModel $scoreModel;

    public function __construct()
    {
        $this->potModel   = new PotModel();
        $this->teamModel  = new TeamModel();
        $this->scoreModel = new ScoreModel();
    }

    public function index(int $potId): string
    {
        $pot = $this->potModel
            ->select('pots.*, tournaments.name AS tournament_name, tournaments.id AS tournament_id')
            ->join('tournaments', 'tournaments.id = pots.tournament_id')
            ->find($potId);

        if ($pot === null) {
            throw PageNotFoundException::forPageNotFound('Pot tidak ditemukan.');
        }

        $selectedGameNo = max(1, (int) ($this->request->getGet('game_no') ?? 1));

        $teams = $this->teamModel
            ->where('pot_id', $potId)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('name', 'ASC')
            ->findAll();

        $scores = $this->scoreModel
            ->where('pot_id', $potId)
            ->where('game_no', $selectedGameNo)
            ->findAll();

        $scoresByTeam = [];
        foreach ($scores as $score) {
            $scoresByTeam[(int) $score['team_id']] = $score;
        }

        $totals = $this->scoreModel
            ->select('team_id, COALESCE(SUM(total_point), 0) AS total_score')
            ->where('pot_id', $potId)
            ->groupBy('team_id')
            ->findAll();

        $totalsByTeam = [];
        foreach ($totals as $total) {
            $totalsByTeam[(int) $total['team_id']] = (int) $total['total_score'];
        }

        $maxGameRow = $this->scoreModel
            ->selectMax('game_no', 'max_game_no')
            ->where('pot_id', $potId)
            ->first();

        $maxGameNo = max($selectedGameNo, (int) ($maxGameRow['max_game_no'] ?? 0), 1);
        $gameNos   = range(1, $maxGameNo);

        return view('scores/index', [
            'pageTitle'      => 'Input Score',
            'pot'            => $pot,
            'teams'          => $teams,
            'scoresByTeam'   => $scoresByTeam,
            'totalsByTeam'   => $totalsByTeam,
            'selectedGameNo' => $selectedGameNo,
            'gameNos'        => $gameNos,
        ]);
    }

    public function save()
    {
        $data = $this->request->getPost(['pot_id', 'team_id', 'game_no', 'rank_no', 'kill_point']);

        if (! $this->validateData($data, [
            'pot_id' => [
                'label' => 'Pot',
                'rules' => 'required|is_natural_no_zero',
            ],
            'team_id' => [
                'label' => 'Team',
                'rules' => 'required|is_natural_no_zero',
            ],
            'game_no' => [
                'label' => 'Game',
                'rules' => 'required|is_natural_no_zero',
            ],
            'rank_no' => [
                'label' => 'Rank',
                'rules' => 'required|is_natural_no_zero',
            ],
            'kill_point' => [
                'label' => 'Kill point',
                'rules' => 'required|is_natural',
            ],
        ])) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Score belum valid.')
                ->with('validation', $this->validator->getErrors());
        }

        $potId     = (int) $data['pot_id'];
        $teamId    = (int) $data['team_id'];
        $gameNo    = (int) $data['game_no'];
        $rankNo    = (int) $data['rank_no'];
        $killPoint = (int) $data['kill_point'];

        $pot  = $this->potModel->find($potId);
        $team = $this->teamModel->find($teamId);

        if ($pot === null || $team === null || (int) $team['pot_id'] !== $potId) {
            return redirect()->back()->with('error', 'Relasi pot dan team tidak valid.');
        }

        $placementPoint = $this->calculatePlacementPoint($rankNo);
        $totalPoint     = $placementPoint + $killPoint;

        $payload = [
            'pot_id'          => $potId,
            'team_id'         => $teamId,
            'game_no'         => $gameNo,
            'rank_no'         => $rankNo,
            'kill_point'      => $killPoint,
            'placement_point' => $placementPoint,
            'total_point'     => $totalPoint,
        ];

        $existing = $this->scoreModel
            ->where('pot_id', $potId)
            ->where('team_id', $teamId)
            ->where('game_no', $gameNo)
            ->first();

        if ($existing !== null) {
            $this->scoreModel->update($existing['id'], $payload);
            $message = 'Score berhasil diperbarui.';
        } else {
            $this->scoreModel->insert($payload);
            $message = 'Score berhasil disimpan.';
        }

        return redirect()->to(site_url('pots/' . $potId . '/scores?game_no=' . $gameNo))->with('success', $message);
    }

    private function calculatePlacementPoint(int $rankNo): int
    {
        return match ($rankNo) {
            1 => 12,
            2 => 9,
            3 => 8,
            4 => 7,
            5 => 6,
            6 => 5,
            7 => 4,
            8 => 3,
            9 => 2,
            10 => 1,
            default => 0,
        };
    }
}
