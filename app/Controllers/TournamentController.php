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
            'pageTitle'     => 'Tournament',
            'tournaments'   => $tournaments,
            'statusOptions' => TournamentModel::statusOptions(),
        ]);
    }

    public function create(): string
    {
        return view('tournaments/form', [
            'pageTitle'     => 'Tambah Tournament',
            'formTitle'     => 'Tambah Tournament',
            'action'        => site_url('tournaments/store'),
            'tournament'    => null,
            'submitLabel'   => 'Simpan Tournament',
            'statusOptions' => TournamentModel::statusOptions(),
        ]);
    }

    public function store()
    {
        $data = $this->request->getPost(['name', 'status', 'redirect_to']);
        $isAjax = $this->request->isAJAX();

        if (! $this->validateData($data, $this->rules())) {
            return $this->errorResponse('Data tournament belum valid.', $isAjax, $this->validator->getErrors());
        }

        $id = (int) $this->tournamentModel->insert([
            'name'   => trim((string) $data['name']),
            'status' => (string) $data['status'],
        ], true);

        if ($isAjax) {
            return $this->response->setJSON($this->jsonPayload('Tournament berhasil ditambahkan.', [
                'status'      => 'success',
                'tournamentId'=> $id,
                'redirectUrl' => site_url('tournaments'),            ]));
        }

        return redirect()->to(site_url('tournaments'))->with('success', 'Tournament berhasil ditambahkan.');
    }

    public function edit(int $id): string
    {
        $tournament = $this->tournamentModel->find($id);

        if ($tournament === null) {
            throw PageNotFoundException::forPageNotFound('Tournament tidak ditemukan.');
        }

        return view('tournaments/form', [
            'pageTitle'     => 'Edit Tournament',
            'formTitle'     => 'Edit Tournament',
            'action'        => site_url('tournaments/update/' . $id),
            'tournament'    => $tournament,
            'submitLabel'   => 'Update Tournament',
            'statusOptions' => TournamentModel::statusOptions(),
        ]);
    }

    public function update(int $id)
    {
        $isAjax = $this->request->isAJAX();
        $tournament = $this->tournamentModel->find($id);

        if ($tournament === null) {
            if ($isAjax) {
                return $this->response->setStatusCode(404)->setJSON($this->jsonPayload('Tournament tidak ditemukan.', [
                    'status' => 'error',
                ]));
            }

            throw PageNotFoundException::forPageNotFound('Tournament tidak ditemukan.');
        }

        $data = $this->request->getPost(['name', 'status', 'redirect_to']);

        if (! $this->validateData($data, $this->rules())) {
            return $this->errorResponse('Perubahan tournament belum valid.', $isAjax, $this->validator->getErrors());
        }

        $this->tournamentModel->update($id, [
            'name'   => trim((string) $data['name']),
            'status' => (string) $data['status'],
        ]);

        $redirectTo = trim((string) ($data['redirect_to'] ?? ''));

        if ($isAjax) {
            return $this->response->setJSON($this->jsonPayload('Tournament berhasil diperbarui.', [
                'status' => 'success',
                'redirectUrl' => $redirectTo !== '' ? $redirectTo : site_url('tournaments'),
            ]));
        }

        return redirect()->to($redirectTo !== '' ? $redirectTo : site_url('tournaments'))->with('success', 'Tournament berhasil diperbarui.');
    }

    public function updateStatus(int $id)
    {
        $isAjax = $this->request->isAJAX();
        $tournament = $this->tournamentModel->find($id);

        if ($tournament === null) {
            if ($isAjax) {
                return $this->response->setStatusCode(404)->setJSON($this->jsonPayload('Tournament tidak ditemukan.', [
                    'status' => 'error',
                ]));
            }

            throw PageNotFoundException::forPageNotFound('Tournament tidak ditemukan.');
        }

        $data = $this->request->getPost(['status', 'redirect_to']);
        $statusRules = implode(',', array_keys(TournamentModel::statusOptions()));

        if (! $this->validateData($data, [
            'status' => 'required|in_list[' . $statusRules . ']',
        ])) {
            return $this->errorResponse('Status tournament tidak valid.', $isAjax, $this->validator->getErrors());
        }

        $this->tournamentModel->update($id, ['status' => (string) $data['status']]);

        $redirectTo = trim((string) ($data['redirect_to'] ?? ''));

        if ($isAjax) {
            return $this->response->setJSON($this->jsonPayload('Status tournament berhasil diperbarui.', [
                'status'         => 'success',
                'reloadPageHost' => true,
                'redirectUrl'    => $redirectTo !== '' ? $redirectTo : site_url('dashboard'),
            ]));
        }

        if ($redirectTo !== '') {
            return redirect()->to($redirectTo)->with('success', 'Status tournament berhasil diperbarui.');
        }

        return redirect()->back()->with('success', 'Status tournament berhasil diperbarui.');
    }

    public function delete(int $id)
    {
        $isAjax = $this->request->isAJAX();
        $tournament = $this->tournamentModel->find($id);
        $redirectTo = trim((string) ($this->request->getPost('redirect_to') ?? ''));
        $targetUrl = $redirectTo !== '' ? $redirectTo : site_url('tournaments');

        if ($tournament === null) {
            if ($isAjax) {
                return $this->response->setStatusCode(404)->setJSON($this->jsonPayload('Tournament tidak ditemukan.', [
                    'status' => 'error',
                ]));
            }

            throw PageNotFoundException::forPageNotFound('Tournament tidak ditemukan.');
        }

        $db = db_connect();

        try {
            $db->transException(true)->transStart();

            // Cascade delete: scores -> team_members -> teams -> pots -> tournament
            $potIds = array_map(
                static fn (array $pot): int => (int) $pot['id'],
                $db->table('pots')->select('id')->where('tournament_id', $id)->get()->getResultArray()
            );

            if ($potIds !== []) {
                $db->table('scores')->whereIn('pot_id', $potIds)->delete();
            }

            $teamIds = array_map(
                static fn (array $team): int => (int) $team['id'],
                $db->table('teams')
                    ->select('id')
                    ->groupStart()
                        ->whereIn('pot_id', $potIds !== [] ? $potIds : [0])
                        ->orWhere('tournament_id', $id)
                    ->groupEnd()
                    ->get()
                    ->getResultArray()
            );

            if ($teamIds !== []) {
                $db->table('team_members')->whereIn('team_id', $teamIds)->delete();
                $db->table('teams')->whereIn('id', $teamIds)->delete();
            }

            if ($potIds !== []) {
                $db->table('pots')->whereIn('id', $potIds)->delete();
            }

            $this->tournamentModel->delete($id);

            $db->transComplete();
        } catch (\Throwable $e) {
            if ($db->transStatus() !== false) {
                $db->transRollback();
            }

            if ($isAjax) {
                return $this->response->setStatusCode(500)->setJSON($this->jsonPayload('Gagal menghapus tournament: ' . $e->getMessage(), [
                    'status' => 'error',
                ]));
            }

            return redirect()->to($targetUrl)->with('error', 'Gagal menghapus tournament.');
        }

        if ($isAjax) {
            return $this->response->setJSON($this->jsonPayload('Tournament berhasil dihapus.', [
                'status'      => 'success',
                'redirectUrl' => $targetUrl,
            ]));
        }

        return redirect()->to($targetUrl)->with('success', 'Tournament berhasil dihapus.');
    }

    private function rules(): array
    {
        $statusRules = implode(',', array_keys(TournamentModel::statusOptions()));

        return [
            'name' => [
                'label' => 'Nama tournament',
                'rules' => 'required|min_length[3]|max_length[150]',
            ],
            'status' => [
                'label' => 'Status tournament',
                'rules' => 'required|in_list[' . $statusRules . ']',
            ],
        ];
    }

    private function errorResponse(string $message, bool $isAjax, array $validation = [])
    {
        if ($isAjax) {
            return $this->response->setStatusCode(422)->setJSON($this->jsonPayload($message, [
                'status'     => 'error',
                'validation' => $validation,
            ]));
        }

        $redirect = redirect()->back()->withInput()->with('error', $message);

        return $validation === [] ? $redirect : $redirect->with('validation', $validation);
    }

    private function jsonPayload(string $message, array $extra = []): array
    {
        return array_merge($extra, [
            'message'       => $message,
            'csrfTokenName' => csrf_token(),
            'csrfHash'      => csrf_hash(),
        ]);
    }
}
