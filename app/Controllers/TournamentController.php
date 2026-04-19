<?php

namespace App\Controllers;

use App\Models\TournamentModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class TournamentController extends BaseController
{
    private TournamentModel $tournamentModel;

    public function __construct()
    {
        $this->tournamentModel = new TournamentModel();
    }

    public function index(): string
    {
        $tournaments = $this->tournamentModel
            ->select('tournaments.*, COUNT(DISTINCT pots.id) AS pot_count, COUNT(DISTINCT teams.id) AS team_count')
            ->join('pots', 'pots.tournament_id = tournaments.id', 'left')
            ->join('teams', 'teams.pot_id = pots.id', 'left')
            ->groupBy('tournaments.id')
            ->orderBy('tournaments.created_at', 'DESC')
            ->findAll();

        return view('tournaments/index', [
            'pageTitle'   => 'Tournament',
            'tournaments' => $tournaments,
        ]);
    }

    public function create(): string
    {
        return view('tournaments/form', [
            'pageTitle'   => 'Tambah Tournament',
            'formTitle'   => 'Tambah Tournament',
            'action'      => site_url('tournaments/store'),
            'tournament'  => null,
            'submitLabel' => 'Simpan Tournament',
        ]);
    }

    public function store()
    {
        $data = $this->request->getPost(['name']);

        if (! $this->validateData($data, $this->rules())) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Data tournament belum valid.')
                ->with('validation', $this->validator->getErrors());
        }

        $this->tournamentModel->insert([
            'name' => trim((string) $data['name']),
        ]);

        return redirect()->to(site_url('tournaments'))->with('success', 'Tournament berhasil ditambahkan.');
    }

    public function edit(int $id): string
    {
        $tournament = $this->tournamentModel->find($id);

        if ($tournament === null) {
            throw PageNotFoundException::forPageNotFound('Tournament tidak ditemukan.');
        }

        return view('tournaments/form', [
            'pageTitle'   => 'Edit Tournament',
            'formTitle'   => 'Edit Tournament',
            'action'      => site_url('tournaments/update/' . $id),
            'tournament'  => $tournament,
            'submitLabel' => 'Update Tournament',
        ]);
    }

    public function update(int $id)
    {
        $tournament = $this->tournamentModel->find($id);

        if ($tournament === null) {
            throw PageNotFoundException::forPageNotFound('Tournament tidak ditemukan.');
        }

        $data = $this->request->getPost(['name']);

        if (! $this->validateData($data, $this->rules())) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Perubahan tournament belum valid.')
                ->with('validation', $this->validator->getErrors());
        }

        $this->tournamentModel->update($id, [
            'name' => trim((string) $data['name']),
        ]);

        return redirect()->to(site_url('tournaments'))->with('success', 'Tournament berhasil diperbarui.');
    }

    public function delete(int $id)
    {
        $tournament = $this->tournamentModel->find($id);

        if ($tournament === null) {
            throw PageNotFoundException::forPageNotFound('Tournament tidak ditemukan.');
        }

        $this->tournamentModel->delete($id);

        return redirect()->to(site_url('tournaments'))->with('success', 'Tournament berhasil dihapus.');
    }

    private function rules(): array
    {
        return [
            'name' => [
                'label' => 'Nama tournament',
                'rules' => 'required|min_length[3]|max_length[150]',
            ],
        ];
    }
}
