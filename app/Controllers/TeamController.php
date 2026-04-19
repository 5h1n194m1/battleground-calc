<?php

namespace App\Controllers;

use App\Models\PotModel;
use App\Models\TeamModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class TeamController extends BaseController
{
    private PotModel $potModel;
    private TeamModel $teamModel;

    public function __construct()
    {
        $this->potModel  = new PotModel();
        $this->teamModel = new TeamModel();
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

        $teams = $this->teamModel
            ->select('teams.*, COALESCE(SUM(scores.total_point), 0) AS total_score, COUNT(DISTINCT scores.game_no) AS games_played')
            ->join('scores', 'scores.team_id = teams.id', 'left')
            ->where('teams.pot_id', $potId)
            ->groupBy('teams.id')
            ->orderBy('teams.sort_order', 'ASC')
            ->orderBy('teams.name', 'ASC')
            ->findAll();

        return view('teams/index', [
            'pageTitle' => 'Team Pot',
            'pot'       => $pot,
            'teams'     => $teams,
        ]);
    }

    public function store()
    {
        $data = $this->request->getPost(['pot_id', 'name', 'sort_order']);

        if (! $this->validateData($data, $this->rules())) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Data team belum valid.')
                ->with('validation', $this->validator->getErrors());
        }

        $potId = (int) $data['pot_id'];
        $pot   = $this->potModel->find($potId);

        if ($pot === null) {
            return redirect()->back()->with('error', 'Pot tujuan tidak ditemukan.');
        }

        $this->teamModel->insert([
            'pot_id'     => $potId,
            'name'       => trim((string) $data['name']),
            'sort_order' => (int) ($data['sort_order'] ?: 0),
        ]);

        return redirect()->to(site_url('pots/' . $potId . '/teams'))->with('success', 'Team berhasil ditambahkan.');
    }

    public function update(int $id)
    {
        $team = $this->teamModel->find($id);

        if ($team === null) {
            throw PageNotFoundException::forPageNotFound('Team tidak ditemukan.');
        }

        $data = $this->request->getPost(['pot_id', 'name', 'sort_order']);

        if (! $this->validateData($data, $this->rules())) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Perubahan team belum valid.')
                ->with('validation', $this->validator->getErrors());
        }

        $this->teamModel->update($id, [
            'name'       => trim((string) $data['name']),
            'sort_order' => (int) ($data['sort_order'] ?: 0),
        ]);

        return redirect()->to(site_url('pots/' . $team['pot_id'] . '/teams'))->with('success', 'Team berhasil diperbarui.');
    }

    public function delete(int $id)
    {
        $team = $this->teamModel->find($id);

        if ($team === null) {
            throw PageNotFoundException::forPageNotFound('Team tidak ditemukan.');
        }

        $this->teamModel->delete($id);

        return redirect()->to(site_url('pots/' . $team['pot_id'] . '/teams'))->with('success', 'Team berhasil dihapus.');
    }

    private function rules(): array
    {
        return [
            'pot_id' => [
                'label' => 'Pot',
                'rules' => 'required|is_natural_no_zero',
            ],
            'name' => [
                'label' => 'Nama team',
                'rules' => 'required|min_length[2]|max_length[150]',
            ],
            'sort_order' => [
                'label' => 'Urutan',
                'rules' => 'permit_empty|is_natural',
            ],
        ];
    }
}
