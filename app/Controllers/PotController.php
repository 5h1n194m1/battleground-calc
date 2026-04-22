<?php

namespace App\Controllers;

use App\Models\PotModel;
use App\Models\TeamModel;
use App\Models\TournamentModel;
use App\Services\PotOrderService;
use CodeIgniter\Exceptions\PageNotFoundException;

class PotController extends BaseController
{
    private PotModel $potModel;
    private TeamModel $teamModel;
    private TournamentModel $tournamentModel;
    private PotOrderService $potOrderService;

    public function __construct()
    {
        $this->potModel        = new PotModel();
        $this->teamModel       = new TeamModel();
        $this->tournamentModel = new TournamentModel();
        $this->potOrderService = new PotOrderService($this->potModel);
    }

    public function index(int $tournamentId)
    {
        $tournament = $this->tournamentModel->find($tournamentId);

        if ($tournament === null) {
            throw PageNotFoundException::forPageNotFound('Tournament tidak ditemukan.');
        }

        $pot = $this->potModel
            ->where('pots.tournament_id', $tournamentId)
            ->orderBy('pots.sort_order', 'ASC')
            ->orderBy('pots.name', 'ASC')
            ->first();

        if ($pot === null) {
            if (! $this->isTournamentEditable($tournament)) {
                return redirect()->to(site_url('dashboard'))->with('error', 'Tournament sudah finished. Pot baru tidak bisa dibuat.');
            }

            $potId = (int) $this->potModel->insert([
                'tournament_id' => $tournamentId,
                'name'          => 'POT 1',
                'sort_order'    => 1,
            ], true);

            return redirect()
                ->to(site_url('pots/' . $potId . '/scores'))
                ->with('success', 'POT 1 otomatis dibuat agar Anda bisa langsung masuk ke kalkulator.');
        }

        return redirect()->to(site_url('pots/' . $pot['id'] . '/scores'));
    }

    public function store()
    {
        $isAjax = $this->request->isAJAX();
        $data = $this->request->getPost(['tournament_id', 'name', 'sort_order', 'redirect_to']);

        if (! $this->validateData($data, $this->rules())) {
            return $this->potErrorResponse('Data pot belum valid.', $isAjax, $this->validator->getErrors());
        }

        $tournamentId = (int) $data['tournament_id'];
        $tournament   = $this->tournamentModel->find($tournamentId);

        if ($tournament === null) {
            return $this->potErrorResponse('Tournament tujuan tidak ditemukan.', $isAjax);
        }

        if (! $this->isTournamentEditable($tournament)) {
            return $this->potErrorResponse('Tournament sudah finished. Pot tidak bisa ditambah lagi.', $isAjax);
        }

        $existingCount = $this->potModel->where('tournament_id', $tournamentId)->countAllResults();
        $name = trim((string) ($data['name'] ?? ''));
        $sortOrder = trim((string) ($data['sort_order'] ?? ''));

        if ($name === '') {
            $name = 'POT ' . ($existingCount + 1);
        }

        $potId = (int) $this->potModel->insert([
            'tournament_id' => $tournamentId,
            'name'          => $name,
            'sort_order'    => $sortOrder === '' ? ($existingCount + 1) : (int) $sortOrder,
        ], true);

        $desiredOrder = $sortOrder === '' ? null : (int) $sortOrder;
        $this->potOrderService->movePot($tournamentId, $potId, $desiredOrder);

        $redirectTo = trim((string) ($data['redirect_to'] ?? ''));
        $targetUrl = $redirectTo !== '' ? $redirectTo : site_url('pots/' . $potId . '/scores');

        if ($isAjax) {
            return $this->response->setJSON([
                'status'        => 'success',
                'message'       => 'Pot berhasil ditambahkan.',
                'redirectUrl'   => $targetUrl,
                'potId'         => $potId,
                'csrfTokenName' => csrf_token(),
                'csrfHash'      => csrf_hash(),
            ]);
        }

        if ($redirectTo !== '') {
            if ($redirectTo === '__new_pot_scores__') {
                return redirect()->to(site_url('pots/' . $potId . '/scores'))->with('success', 'Pot berhasil ditambahkan.');
            }

            return redirect()->to($redirectTo)->with('success', 'Pot berhasil ditambahkan.');
        }

        return redirect()->back()->with('success', 'Pot berhasil ditambahkan.');
    }

    public function update(int $id)
    {
        $isAjax = $this->request->isAJAX();
        $pot = $this->potModel->find($id);

        if ($pot === null) {
            throw PageNotFoundException::forPageNotFound('Pot tidak ditemukan.');
        }

        $tournament = $this->tournamentModel->find((int) $pot['tournament_id']);
        if ($tournament === null) {
            return $this->potErrorResponse('Tournament pot tidak ditemukan.', $isAjax);
        }

        if (! $this->isTournamentEditable($tournament)) {
            return $this->potErrorResponse('Tournament sudah finished. Pot tidak bisa diubah.', $isAjax);
        }

        $data = $this->request->getPost(['tournament_id', 'name', 'sort_order', 'redirect_to']);

        if (! $this->validateData($data, $this->rules())) {
            return $this->potErrorResponse('Perubahan pot belum valid.', $isAjax, $this->validator->getErrors());
        }

        $name = trim((string) ($data['name'] ?? ''));
        $sortOrder = trim((string) ($data['sort_order'] ?? ''));

        $this->potModel->update($id, [
            'name'       => $name === '' ? (string) $pot['name'] : $name,
            'sort_order' => $sortOrder === '' ? (int) $pot['sort_order'] : (int) $sortOrder,
        ]);
        $this->potOrderService->movePot((int) $pot['tournament_id'], $id, $sortOrder === '' ? null : (int) $sortOrder);

        $redirectTo = trim((string) ($data['redirect_to'] ?? ''));
        if ($isAjax) {
            return $this->response->setJSON([
                'status'        => 'success',
                'message'       => 'Pot berhasil diperbarui.',
                'redirectUrl'   => $redirectTo !== '' ? $redirectTo : site_url('pots/' . $id . '/scores'),
                'csrfTokenName' => csrf_token(),
                'csrfHash'      => csrf_hash(),
            ]);
        }

        if ($redirectTo !== '') {
            return redirect()->to($redirectTo)->with('success', 'Pot berhasil diperbarui.');
        }

        return redirect()->back()->with('success', 'Pot berhasil diperbarui.');
    }

    public function updateImages(int $id)
    {
        $isAjax = $this->request->isAJAX();
        $pot = $this->potModel->find($id);

        if ($pot === null) {
            throw PageNotFoundException::forPageNotFound('Pot tidak ditemukan.');
        }

        $tournament = $this->tournamentModel->find((int) $pot['tournament_id']);
        if ($tournament === null) {
            return $this->potErrorResponse('Tournament pot tidak ditemukan.', $isAjax);
        }

        if (! $this->isTournamentEditable($tournament)) {
            return $this->potErrorResponse('Tournament sudah finished. Screenshot tidak bisa diubah.', $isAjax);
        }

        $rules = [
            'reference_image_1' => 'if_exist|uploaded[reference_image_1]|is_image[reference_image_1]|mime_in[reference_image_1,image/jpg,image/jpeg,image/png,image/webp,image/gif]|ext_in[reference_image_1,jpg,jpeg,png,webp,gif]|max_size[reference_image_1,4096]',
            'reference_image_2' => 'if_exist|uploaded[reference_image_2]|is_image[reference_image_2]|mime_in[reference_image_2,image/jpg,image/jpeg,image/png,image/webp,image/gif]|ext_in[reference_image_2,jpg,jpeg,png,webp,gif]|max_size[reference_image_2,4096]',
        ];

        $requestFiles = $this->request->getFiles();
        $hasUpload = false;
        foreach (['reference_image_1', 'reference_image_2'] as $field) {
            if (isset($requestFiles[$field]) && $requestFiles[$field]->isValid() && ! $requestFiles[$field]->hasMoved()) {
                $hasUpload = true;
            }
        }

        if (! $hasUpload) {
            return $this->potErrorResponse('Pilih minimal satu screenshot untuk diupload.', $isAjax);
        }

        if (! $this->validate($rules)) {
            return $this->potErrorResponse('File screenshot belum valid.', $isAjax, $this->validator->getErrors());
        }

        $uploadPath = FCPATH . 'uploads/pot-references';
        if (! is_dir($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }

        $payload = [];

        foreach (['reference_image_1', 'reference_image_2'] as $field) {
            $file = $this->request->getFile($field);

            if ($file === null || ! $file->isValid() || $file->hasMoved()) {
                continue;
            }

            $newName = 'pot_' . $id . '_' . $field . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $file->getExtension();
            $file->move($uploadPath, $newName, true);
            $payload[$field] = 'uploads/pot-references/' . $newName;
        }

        if ($payload !== []) {
            $this->potModel->update($id, $payload);
        }

        if ($isAjax) {
            return $this->response->setJSON([
                'status'        => 'success',
                'message'       => 'Screenshot referensi pot berhasil diperbarui.',
                'csrfTokenName' => csrf_token(),
                'csrfHash'      => csrf_hash(),
            ]);
        }

        return redirect()->back()->with('success', 'Screenshot referensi pot berhasil diperbarui.');
    }

    public function delete(int $id)
    {
        $isAjax = $this->request->isAJAX();
        $pot = $this->potModel->find($id);

        if ($pot === null) {
            throw PageNotFoundException::forPageNotFound('Pot tidak ditemukan.');
        }

        $tournament = $this->tournamentModel->find((int) $pot['tournament_id']);
        if ($tournament === null) {
            return $this->potErrorResponse('Tournament pot tidak ditemukan.', $isAjax);
        }

        if (! $this->isTournamentEditable($tournament)) {
            return $this->potErrorResponse('Tournament sudah finished. Pot tidak bisa dihapus.', $isAjax);
        }

        $nextPot = $this->potModel
            ->where('tournament_id', (int) $pot['tournament_id'])
            ->where('id !=', $id)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('name', 'ASC')
            ->first();

        $this->potModel->delete($id);
        $this->potOrderService->normalizeTournament((int) $pot['tournament_id']);

        if ($isAjax) {
            return $this->response->setJSON([
                'status'        => 'success',
                'message'       => 'Pot berhasil dihapus.',
                'redirectUrl'   => $nextPot !== null ? site_url('pots/' . $nextPot['id'] . '/scores') : site_url('dashboard'),
                'csrfTokenName' => csrf_token(),
                'csrfHash'      => csrf_hash(),
            ]);
        }

        if ($nextPot !== null) {
            return redirect()->to(site_url('pots/' . $nextPot['id'] . '/scores'))->with('success', 'Pot berhasil dihapus.');
        }

        return redirect()->to(site_url('dashboard'))->with('success', 'Pot berhasil dihapus.');
    }

    public function advanceSelected(int $id)
    {
        $isAjax = $this->request->isAJAX();
        $pot = $this->potModel->find($id);

        if ($pot === null) {
            throw PageNotFoundException::forPageNotFound('Pot tidak ditemukan.');
        }

        $tournament = $this->tournamentModel->find((int) $pot['tournament_id']);
        if ($tournament === null) {
            return $this->potErrorResponse('Tournament pot tidak ditemukan.', $isAjax);
        }

        if (! $this->isTournamentEditable($tournament)) {
            return $this->potErrorResponse('Tournament sudah finished. Team tidak bisa dipindah ke pot baru.', $isAjax);
        }

        $rawTeamIds = $this->request->getPost('team_ids');
        $selectedTeamIds = array_values(array_unique(array_map(
            static fn ($value): int => (int) $value,
            array_filter(is_array($rawTeamIds) ? $rawTeamIds : [], static fn ($value): bool => ctype_digit((string) $value) && (int) $value > 0)
        )));

        if ($selectedTeamIds === []) {
            return $this->potErrorResponse('Pilih minimal satu team yang lolos terlebih dahulu.', $isAjax);
        }

        $selectedTeams = $this->teamModel
            ->where('pot_id', $id)
            ->whereIn('id', $selectedTeamIds)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('name', 'ASC')
            ->findAll();

        if (count($selectedTeams) !== count($selectedTeamIds)) {
            return $this->potErrorResponse('Sebagian team yang dipilih tidak valid untuk pot ini.', $isAjax);
        }

        $existingCount = $this->potModel->where('tournament_id', (int) $pot['tournament_id'])->countAllResults();
        $requestedName = trim((string) ($this->request->getPost('target_pot_name') ?? ''));
        $targetPotName = $requestedName !== '' ? $requestedName : 'POT ' . ($existingCount + 1);

        $db = db_connect();
        $targetPotId = 0;

        try {
            $db->transException(true)->transStart();

            $targetPotId = (int) $this->potModel->insert([
                'tournament_id' => (int) $pot['tournament_id'],
                'name'          => $targetPotName,
                'sort_order'    => $existingCount + 1,
            ], true);
            $this->potOrderService->movePot((int) $pot['tournament_id'], $targetPotId, $existingCount + 1);

            foreach ($selectedTeamIds as $index => $teamId) {
                $this->teamModel->update($teamId, [
                    'pot_id'     => $targetPotId,
                    'sort_order' => $index + 1,
                ]);
            }

            $this->normalizeTeamSortOrder($id);
            $this->normalizeTeamSortOrder($targetPotId);

            $db->transComplete();
        } catch (\Throwable $e) {
            if ($db->transStatus() !== false) {
                $db->transRollback();
            }

            return $this->potErrorResponse('Gagal membuat pot baru dari team yang dipilih.', $isAjax);
        }

        $message = count($selectedTeamIds) . ' team berhasil dipindahkan ke ' . $targetPotName . '.';
        $redirectUrl = site_url('pots/' . $targetPotId . '/scores');

        if ($isAjax) {
            return $this->response->setJSON([
                'status'        => 'success',
                'message'       => $message,
                'redirectUrl'   => $redirectUrl,
                'potId'         => $targetPotId,
                'csrfTokenName' => csrf_token(),
                'csrfHash'      => csrf_hash(),
            ]);
        }

        return redirect()->to($redirectUrl)->with('success', $message);
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
                'rules' => 'permit_empty|max_length[150]',
            ],
            'sort_order' => [
                'label' => 'Urutan',
                'rules' => 'permit_empty|is_natural',
            ],
        ];
    }

    private function isTournamentEditable(array $tournament): bool
    {
        return (string) ($tournament['status'] ?? TournamentModel::STATUS_BELUM_MULAI) !== TournamentModel::STATUS_SELESAI;
    }

    private function normalizeTeamSortOrder(int $potId): void
    {
        $teams = $this->teamModel
            ->where('pot_id', $potId)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();

        foreach ($teams as $index => $team) {
            $order = $index + 1;
            if ((int) ($team['sort_order'] ?? 0) !== $order) {
                $this->teamModel->update((int) $team['id'], ['sort_order' => $order]);
            }
        }
    }

    private function potErrorResponse(string $message, bool $isAjax, array $validation = [])
    {
        if ($isAjax) {
            return $this->response->setStatusCode(422)->setJSON([
                'status'        => 'error',
                'message'       => $message,
                'validation'    => $validation,
                'csrfTokenName' => csrf_token(),
                'csrfHash'      => csrf_hash(),
            ]);
        }

        $redirect = redirect()->back()->with('error', $message);

        return $validation === [] ? $redirect : $redirect->with('validation', $validation);
    }
}
