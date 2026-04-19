<?php

namespace App\Controllers;

use App\Models\PotModel;
use App\Models\TournamentModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class PotController extends BaseController
{
    private PotModel $potModel;
    private TournamentModel $tournamentModel;

    public function __construct()
    {
        $this->potModel        = new PotModel();
        $this->tournamentModel = new TournamentModel();
    }

    public function index(int $tournamentId): string
    {
        $tournament = $this->tournamentModel->find($tournamentId);

        if ($tournament === null) {
            throw PageNotFoundException::forPageNotFound('Tournament tidak ditemukan.');
        }

        $pots = $this->potModel
            ->select('pots.*')
            ->select('(SELECT COUNT(*) FROM teams WHERE teams.pot_id = pots.id) AS team_count', false)
            ->select('(SELECT COALESCE(SUM(scores.total_point), 0) FROM scores WHERE scores.pot_id = pots.id) AS total_score', false)
            ->where('pots.tournament_id', $tournamentId)
            ->orderBy('pots.sort_order', 'ASC')
            ->orderBy('pots.name', 'ASC')
            ->findAll();

        return view('pots/index', [
            'pageTitle'  => 'Pot Tournament',
            'tournament' => $tournament,
            'pots'       => $pots,
        ]);
    }

    public function store()
    {
        $data = $this->request->getPost(['tournament_id', 'name', 'sort_order']);

        if (! $this->validateData($data, $this->rules())) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Data pot belum valid.')
                ->with('validation', $this->validator->getErrors());
        }

        $tournamentId = (int) $data['tournament_id'];
        $tournament   = $this->tournamentModel->find($tournamentId);

        if ($tournament === null) {
            return redirect()->back()->with('error', 'Tournament tujuan tidak ditemukan.');
        }

        $this->potModel->insert([
            'tournament_id' => $tournamentId,
            'name'          => trim((string) $data['name']),
            'sort_order'    => (int) ($data['sort_order'] ?: 0),
        ]);

        return redirect()->to(site_url('tournaments/' . $tournamentId . '/pots'))->with('success', 'Pot berhasil ditambahkan.');
    }

    public function update(int $id)
    {
        $pot = $this->potModel->find($id);

        if ($pot === null) {
            throw PageNotFoundException::forPageNotFound('Pot tidak ditemukan.');
        }

        $data = $this->request->getPost(['tournament_id', 'name', 'sort_order']);

        if (! $this->validateData($data, $this->rules())) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Perubahan pot belum valid.')
                ->with('validation', $this->validator->getErrors());
        }

        $this->potModel->update($id, [
            'name'       => trim((string) $data['name']),
            'sort_order' => (int) ($data['sort_order'] ?: 0),
        ]);

        return redirect()->to(site_url('tournaments/' . $pot['tournament_id'] . '/pots'))->with('success', 'Pot berhasil diperbarui.');
    }

    public function delete(int $id)
    {
        $pot = $this->potModel->find($id);

        if ($pot === null) {
            throw PageNotFoundException::forPageNotFound('Pot tidak ditemukan.');
        }

        $this->potModel->delete($id);

        return redirect()->to(site_url('tournaments/' . $pot['tournament_id'] . '/pots'))->with('success', 'Pot berhasil dihapus.');
    }

    private function rules(): array
    {
        return [
            'tournament_id' => [
                'label' => 'Tournament',
                'rules' => 'required|is_natural_no_zero',
            ],
            'name' => [
                'label' => 'Nama pot',
                'rules' => 'required|min_length[2]|max_length[150]',
            ],
            'sort_order' => [
                'label' => 'Urutan',
                'rules' => 'permit_empty|is_natural',
            ],
        ];
    }
}
