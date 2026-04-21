<?php

namespace App\Controllers;

use App\Models\PlayerModel;
use App\Models\PotModel;
use App\Models\RegistrationModel;
use App\Models\TeamMemberModel;
use App\Models\TeamModel;
use App\Models\ScoreModel;
use App\Models\TournamentModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class TeamController extends BaseController
{
    private const MAX_MEMBERS = 6;

    private PotModel $potModel;
    private TeamModel $teamModel;
    private RegistrationModel $registrationModel;
    private PlayerModel $playerModel;
    private TeamMemberModel $teamMemberModel;
    private TournamentModel $tournamentModel;
    private ScoreModel $scoreModel;

    public function __construct()
    {
        $this->potModel          = new PotModel();
        $this->teamModel         = new TeamModel();
        $this->registrationModel = new RegistrationModel();
        $this->playerModel       = new PlayerModel();
        $this->teamMemberModel   = new TeamMemberModel();
        $this->tournamentModel   = new TournamentModel();
        $this->scoreModel        = new ScoreModel();
    }

    public function index(int $potId)
    {
        return redirect()->to(site_url('pots/' . $potId . '/scores'));
    }

        public function managerData()
    {
        $tournaments = $this->tournamentModel
            ->orderBy('created_at', 'DESC')
            ->findAll();

        $selectedTournamentId = (int) ($this->request->getGet('tournament_id') ?? 0);
        if ($selectedTournamentId <= 0 && $tournaments !== []) {
            $selectedTournamentId = (int) $tournaments[0]['id'];
        }

        $pots = $selectedTournamentId > 0
            ? $this->potModel->where('tournament_id', $selectedTournamentId)->orderBy('sort_order', 'ASC')->orderBy('name', 'ASC')->findAll()
            : [];

        $selectedPotId = (int) ($this->request->getGet('pot_id') ?? 0);
        if ($selectedPotId <= 0 && $pots !== []) {
            $selectedPotId = (int) $pots[0]['id'];
        }

        $teams = $selectedPotId > 0
            ? $this->teamModel->where('pot_id', $selectedPotId)->orderBy('sort_order', 'ASC')->orderBy('name', 'ASC')->findAll()
            : [];

        $teamIds = array_map(static fn (array $team): int => (int) $team['id'], $teams);
        $membersByTeam = [];
        $scoreStatsByTeam = [];

        if ($teamIds !== []) {
            $members = $this->teamMemberModel
                ->whereIn('team_id', $teamIds)
                ->orderBy('id', 'ASC')
                ->findAll();

            foreach ($members as $member) {
                $membersByTeam[(int) $member['team_id']][] = trim((string) ($member['player_name'] ?? ''));
            }

            $scoreRows = $this->scoreModel
                ->select('team_id, COUNT(DISTINCT game_no) AS games_played, COALESCE(SUM(total_point), 0) AS total_score, COALESCE(SUM(kill_point), 0) AS total_kill')
                ->whereIn('team_id', $teamIds)
                ->groupBy('team_id')
                ->findAll();

            foreach ($scoreRows as $scoreRow) {
                $scoreStatsByTeam[(int) $scoreRow['team_id']] = [
                    'games_played' => (int) ($scoreRow['games_played'] ?? 0),
                    'total_score'  => (int) ($scoreRow['total_score'] ?? 0),
                    'total_kill'   => (int) ($scoreRow['total_kill'] ?? 0),
                ];
            }
        }

        $teamPayload = [];
        foreach ($teams as $team) {
            $teamId = (int) $team['id'];
            $members = array_values(array_filter($membersByTeam[$teamId] ?? [], static fn (string $name): bool => $name !== ''));
            $scoreStats = $scoreStatsByTeam[$teamId] ?? [
                'games_played' => 0,
                'total_score'  => 0,
                'total_kill'   => 0,
            ];

            $teamPayload[] = [
                'id' => $teamId,
                'pot_id' => (int) $team['pot_id'],
                'name' => (string) $team['name'],
                'sort_order' => (int) ($team['sort_order'] ?? 0),
                'member_count' => count($members),
                'member_text' => implode(', ', $members),
                'members' => $members,
                'games_played' => (int) $scoreStats['games_played'],
                'total_score' => (int) $scoreStats['total_score'],
                'total_kill' => (int) $scoreStats['total_kill'],
            ];
        }

        return $this->response->setJSON([
            'status' => 'success',
            'tournaments' => array_map(static fn (array $t): array => [
                'id' => (int) $t['id'],
                'name' => (string) $t['name'],
                'status' => (string) ($t['status'] ?? TournamentModel::STATUS_BELUM_MULAI),
            ], $tournaments),
            'pots' => array_map(static fn (array $pot): array => [
                'id' => (int) $pot['id'],
                'tournament_id' => (int) $pot['tournament_id'],
                'name' => (string) $pot['name'],
                'sort_order' => (int) ($pot['sort_order'] ?? 0),
            ], $pots),
            'teams' => $teamPayload,
            'selectedTournamentId' => $selectedTournamentId,
            'selectedPotId' => $selectedPotId,
            'csrfTokenName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function store()
    {
        $isAjax = $this->request->isAJAX();
        $data = $this->request->getPost(['pot_id', 'name', 'sort_order', 'redirect_to', 'member_text']);

        if (! $this->validateData($data, $this->rules())) {
            return $this->teamErrorResponse('Data team belum valid.', $isAjax, $this->validator->getErrors());
        }

        $potId = (int) $data['pot_id'];
        $pot   = $this->potWithTournament($potId);

        if ($pot === null) {
            return $this->teamErrorResponse('Pot tujuan tidak ditemukan.', $isAjax);
        }

        if (! $this->isPotEditable($pot)) {
            return $this->teamErrorResponse('Tournament sudah finished. Team tidak bisa ditambah lagi.', $isAjax);
        }

        $existingCount = $this->teamModel->where('pot_id', $potId)->countAllResults();
        $name = trim((string) ($data['name'] ?? ''));
        $sortOrder = trim((string) ($data['sort_order'] ?? ''));
        $memberText = (string) ($data['member_text'] ?? '');

        if ($name === '') {
            $name = 'Team ' . ($existingCount + 1);
        }

        $teamId = (int) $this->teamModel->insert([
            'pot_id'     => $potId,
            'name'       => $name,
            'sort_order' => $sortOrder === '' ? ($existingCount + 1) : (int) $sortOrder,
        ], true);

        if ($this->request->getPost('member_text') !== null) {
            $this->syncMembersFromText($teamId, $memberText);
        } else {
            $this->syncMembersFromRegistration($teamId, $name);
        }

        $redirectTo = trim((string) ($data['redirect_to'] ?? ''));
        $targetUrl = $redirectTo !== '' ? $redirectTo : site_url('pots/' . $potId . '/scores');

        if ($isAjax) {
            return $this->response->setJSON([
                'status'        => 'success',
                'message'       => 'Team berhasil ditambahkan.',
                'teamId'        => $teamId,
                'potId'         => $potId,
                'redirectUrl'   => $targetUrl,
                'csrfTokenName' => csrf_token(),
                'csrfHash'      => csrf_hash(),
            ]);
        }

        if ($redirectTo !== '') {
            return redirect()->to($redirectTo)->with('success', 'Team berhasil ditambahkan.');
        }

        return redirect()->to(site_url('pots/' . $potId . '/scores'))->with('success', 'Team berhasil ditambahkan.');
    }

    public function update(int $id)
    {
        $team = $this->teamModel->find($id);
        $isAjax = $this->request->isAJAX();

        if ($team === null) {
            if ($isAjax) {
                return $this->response->setStatusCode(404)->setJSON([
                    'status' => 'error',
                    'message' => 'Team tidak ditemukan.',
                    'csrfTokenName' => csrf_token(),
                    'csrfHash' => csrf_hash(),
                ]);
            }

            throw PageNotFoundException::forPageNotFound('Team tidak ditemukan.');
        }

        $pot = $this->potWithTournament((int) $team['pot_id']);
        if ($pot === null) {
            return $this->teamErrorResponse('Pot team tidak ditemukan.', $isAjax);
        }

        if (! $this->isPotEditable($pot)) {
            return $this->teamErrorResponse('Tournament sudah finished. Team tidak bisa diubah.', $isAjax);
        }

        $data = $this->request->getPost(['pot_id', 'name', 'sort_order', 'redirect_to', 'member_text']);

        if (! $this->validateData($data, $this->rules())) {
            return $this->teamErrorResponse('Perubahan team belum valid.', $isAjax, $this->validator->getErrors());
        }

        $targetPotId = (int) ($data['pot_id'] ?? $team['pot_id']);
        $targetPot = $this->potWithTournament($targetPotId);
        if ($targetPot === null) {
            return $this->teamErrorResponse('Pot tujuan tidak ditemukan.', $isAjax);
        }

        if (! $this->isPotEditable($targetPot)) {
            return $this->teamErrorResponse('Tournament sudah finished. Team tidak bisa dipindah.', $isAjax);
        }

        $name = trim((string) ($data['name'] ?? ''));
        $sortOrder = trim((string) ($data['sort_order'] ?? ''));

        $this->teamModel->update($id, [
            'pot_id'     => $targetPotId,
            'name'       => $name === '' ? (string) $team['name'] : $name,
            'sort_order' => $sortOrder === '' ? (int) $team['sort_order'] : (int) $sortOrder,
        ]);

        if ($this->request->getPost('member_text') !== null) {
            $this->syncMembersFromText($id, (string) ($data['member_text'] ?? ''));
        } else {
            $this->syncMembersFromRegistration($id, $name === '' ? (string) $team['name'] : $name);
        }

        $redirectTo = trim((string) ($data['redirect_to'] ?? ''));
        if ($isAjax) {
            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Team berhasil diperbarui.',
                'teamId' => $id,
                'potId' => $targetPotId,
                'previousPotId' => (int) $team['pot_id'],
                'csrfTokenName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        if ($redirectTo !== '') {
            return redirect()->to($redirectTo)->with('success', 'Team berhasil diperbarui.');
        }

        return redirect()->to(site_url('pots/' . $targetPotId . '/scores'))->with('success', 'Team berhasil diperbarui.');
    }

    public function delete(int $id)
    {
        $team = $this->teamModel->find($id);
        $isAjax = $this->request->isAJAX();

        if ($team === null) {
            if ($isAjax) {
                return $this->response->setStatusCode(404)->setJSON([
                    'status'        => 'error',
                    'message'       => 'Team tidak ditemukan.',
                    'csrfTokenName' => csrf_token(),
                    'csrfHash'      => csrf_hash(),
                ]);
            }

            throw PageNotFoundException::forPageNotFound('Team tidak ditemukan.');
        }

        $pot = $this->potWithTournament((int) $team['pot_id']);
        if ($pot === null) {
            if ($isAjax) {
                return $this->response->setStatusCode(404)->setJSON([
                    'status'        => 'error',
                    'message'       => 'Pot team tidak ditemukan.',
                    'csrfTokenName' => csrf_token(),
                    'csrfHash'      => csrf_hash(),
                ]);
            }

            return redirect()->back()->with('error', 'Pot team tidak ditemukan.');
        }

        if (! $this->isPotEditable($pot)) {
            if ($isAjax) {
                return $this->response->setStatusCode(422)->setJSON([
                    'status'        => 'error',
                    'message'       => 'Tournament sudah finished. Team tidak bisa dihapus.',
                    'csrfTokenName' => csrf_token(),
                    'csrfHash'      => csrf_hash(),
                ]);
            }

            return redirect()->back()->with('error', 'Tournament sudah finished. Team tidak bisa dihapus.');
        }

        $db = db_connect();

        try {
            $db->transException(true)->transStart();

            $this->scoreModel->where('team_id', $id)->delete();
            $this->teamMemberModel->where('team_id', $id)->delete();
            $this->teamModel->delete($id);

            $db->transComplete();
        } catch (\Throwable $e) {
            if ($db->transStatus() !== false) {
                $db->transRollback();
            }

            if ($isAjax) {
                return $this->response->setStatusCode(500)->setJSON([
                    'status'        => 'error',
                    'message'       => 'Team gagal dihapus. Pastikan data score terkait bisa dibersihkan.',
                    'csrfTokenName' => csrf_token(),
                    'csrfHash'      => csrf_hash(),
                ]);
            }

            return redirect()->to(site_url('pots/' . $team['pot_id'] . '/scores'))
                ->with('error', 'Team gagal dihapus. Pastikan data score terkait bisa dibersihkan.');
        }

        if ($isAjax) {
            return $this->response->setJSON([
                'status'        => 'success',
                'message'       => 'Team berhasil dihapus.',
                'teamId'        => $id,
                'potId'         => (int) $team['pot_id'],
                'csrfTokenName' => csrf_token(),
                'csrfHash'      => csrf_hash(),
            ]);
        }

        return redirect()->to(site_url('pots/' . $team['pot_id'] . '/scores'))
            ->with('success', 'Team berhasil dihapus.');
    }

    public function syncMembers(int $id)
    {
        $team = $this->teamModel->find($id);

        if ($team === null) {
            throw PageNotFoundException::forPageNotFound('Team tidak ditemukan.');
        }

        $pot = $this->potWithTournament((int) $team['pot_id']);
        if ($pot === null) {
            return redirect()->back()->with('error', 'Pot team tidak ditemukan.');
        }

        if (! $this->isPotEditable($pot)) {
            return redirect()->back()->with('error', 'Tournament sudah finished. Sinkron anggota dinonaktifkan.');
        }

        $synced = $this->syncMembersFromRegistration($id, (string) $team['name']);

        if (! $synced) {
            return redirect()->to(site_url('pots/' . $team['pot_id'] . '/scores'))->with('error', 'Belum ada data registrasi yang cocok untuk team ini.');
        }

        return redirect()->to(site_url('pots/' . $team['pot_id'] . '/scores'))->with('success', 'Anggota team berhasil disinkronkan dari data registrasi.');
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
                'rules' => 'permit_empty|max_length[150]',
            ],
            'sort_order' => [
                'label' => 'Urutan',
                'rules' => 'permit_empty|is_natural',
            ],
        ];
    }

    private function syncMembersFromRegistration(int $teamId, string $teamName): bool
    {
        $normalizedTeamName = $this->normalizeName($teamName);

        if ($normalizedTeamName === '') {
            return false;
        }

        $registrations = $this->registrationModel
            ->orderBy('created_at', 'DESC')
            ->findAll();

        $matchedRegistration = null;

        foreach ($registrations as $registration) {
            if ($this->normalizeName((string) $registration['team_name']) === $normalizedTeamName) {
                $matchedRegistration = $registration;
                break;
            }
        }

        if ($matchedRegistration === null) {
            return false;
        }

        $players = $this->playerModel
            ->where('registration_id', (int) $matchedRegistration['id'])
            ->orderBy('id', 'ASC')
            ->findAll();

        $this->teamMemberModel->where('team_id', $teamId)->delete();

        foreach (array_slice($players, 0, self::MAX_MEMBERS) as $player) {
            $this->teamMemberModel->insert([
                'team_id'         => $teamId,
                'registration_id' => (int) $matchedRegistration['id'],
                'player_name'     => (string) $player['player_name'],
                'player_role'     => $player['player_role'] ?: null,
            ]);
        }

        return true;
    }

    private function syncMembersFromText(int $teamId, string $memberText): void
    {
        $normalizedMemberText = str_replace([';', "
", "
"], [',', "
", "
"], $memberText);
        $chunks = preg_split('/
|,/', $normalizedMemberText) ?: [];
        $members = [];

        foreach ($chunks as $chunk) {
            $name = trim($chunk);
            if ($name === '') {
                continue;
            }
            $members[] = $name;
        }

        $members = array_slice(array_values(array_unique($members)), 0, self::MAX_MEMBERS);

        $this->teamMemberModel->where('team_id', $teamId)->delete();
        foreach ($members as $memberName) {
            $this->teamMemberModel->insert([
                'team_id'         => $teamId,
                'registration_id' => null,
                'player_name'     => $memberName,
                'player_role'     => null,
            ]);
        }
    }

    private function normalizeName(string $value): string
    {
        $value = strtolower(trim($value));

        return preg_replace('/[^a-z0-9]+/', '', $value) ?? '';
    }

    private function potWithTournament(int $potId): ?array
    {
        return $this->potModel
            ->select('pots.*, tournaments.status AS tournament_status')
            ->join('tournaments', 'tournaments.id = pots.tournament_id')
            ->where('pots.id', $potId)
            ->first();
    }

    private function isPotEditable(array $pot): bool
    {
        return (string) ($pot['tournament_status'] ?? TournamentModel::STATUS_BELUM_MULAI) !== TournamentModel::STATUS_SELESAI;
    }

    private function teamErrorResponse(string $message, bool $isAjax, array $validation = [])
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
