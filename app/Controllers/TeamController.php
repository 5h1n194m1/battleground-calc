<?php

namespace App\Controllers;

use App\Models\PotModel;
use App\Models\ScoreModel;
use App\Models\TeamMemberModel;
use App\Models\TeamModel;
use App\Models\TournamentModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class TeamController extends BaseController
{
    private const MAX_MEMBERS = 6;

    private PotModel $potModel;
    private TeamModel $teamModel;
    private TeamMemberModel $teamMemberModel;
    private TournamentModel $tournamentModel;
    private ScoreModel $scoreModel;

    public function __construct()
    {
        $this->potModel          = new PotModel();
        $this->teamModel         = new TeamModel();
        $this->teamMemberModel   = new TeamMemberModel();
        $this->tournamentModel   = new TournamentModel();
        $this->scoreModel        = new ScoreModel();
    }

    public function index(int $potId)
    {
        return redirect()->to(site_url('pots/' . $potId . '/scores'));
    }

    public function rosterIndex()
    {
        $teams = $this->teamModel
            ->select('teams.id, teams.name, teams.sort_order, teams.pot_id, pots.name AS pot_name, tournaments.name AS tournament_name, tournaments.status AS tournament_status')
            ->join('pots', 'pots.id = teams.pot_id', 'left')
            ->join('tournaments', 'tournaments.id = pots.tournament_id', 'left')
            ->orderBy('tournaments.name', 'ASC')
            ->orderBy('pots.sort_order', 'ASC')
            ->orderBy('teams.sort_order', 'ASC')
            ->orderBy('teams.name', 'ASC')
            ->findAll();

        $potOptions = $this->potModel
            ->select('pots.id, pots.name, pots.sort_order, tournaments.name AS tournament_name, tournaments.status AS tournament_status')
            ->join('tournaments', 'tournaments.id = pots.tournament_id')
            ->orderBy('tournaments.name', 'ASC')
            ->orderBy('pots.sort_order', 'ASC')
            ->orderBy('pots.name', 'ASC')
            ->findAll();

        $teamIds = array_map(static fn (array $team): int => (int) $team['id'], $teams);
        $membersByTeam = $this->memberNamesByTeam($teamIds);

        $rows = array_map(function (array $team) use ($membersByTeam): array {
            $teamId = (int) $team['id'];
            $members = array_values(array_filter($membersByTeam[$teamId] ?? [], static fn (string $name): bool => $name !== ''));

            return [
                'id'              => $teamId,
                'pot_id'          => $team['pot_id'] !== null ? (int) $team['pot_id'] : null,
                'name'            => (string) ($team['name'] ?? '-'),
                'members'         => $members,
                'member_text'     => $members !== [] ? implode(', ', $members) : '-',
                'pot_name'        => trim((string) ($team['pot_name'] ?? '')),
                'tournament_name' => trim((string) ($team['tournament_name'] ?? '')),
                'scope_label'     => $team['pot_id'] === null
                    ? 'Tanpa Pot'
                    : trim((string) ($team['pot_name'] ?? '') . (trim((string) ($team['tournament_name'] ?? '')) !== '' ? ' / ' . trim((string) ($team['tournament_name'] ?? '')) : '')),
                'is_locked'       => (string) ($team['tournament_status'] ?? '') === TournamentModel::STATUS_SELESAI,
            ];
        }, $teams);

        return view('teams/roster_index', [
            'pageTitle' => 'Daftar Team',
            'rows'      => $rows,
            'potOptions'=> array_map(static fn (array $pot): array => [
                'id'              => (int) $pot['id'],
                'name'            => (string) ($pot['name'] ?? 'Pot'),
                'sort_order'      => (int) ($pot['sort_order'] ?? 0),
                'tournament_name' => (string) ($pot['tournament_name'] ?? 'Tournament'),
                'status'          => (string) ($pot['tournament_status'] ?? TournamentModel::STATUS_BELUM_MULAI),
                'label'           => trim((string) ($pot['tournament_name'] ?? 'Tournament') . ' / ' . (string) ($pot['name'] ?? 'Pot')),
            ], $potOptions),
        ]);
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

    $potScope = trim((string) ($this->request->getGet('pot_scope') ?? ''));
    $rawPotId = $this->request->getGet('pot_id');
    $selectedPotId = (int) ($rawPotId ?? 0);

    if ($potScope !== 'unassigned' && ($rawPotId === null || $rawPotId === '') && $pots !== []) {
        $selectedPotId = (int) $pots[0]['id'];
    }

    if ($potScope === 'unassigned') {
        $teams = $this->lookupTeamsForManager($selectedTournamentId);
        $selectedPotId = 0;
    } else {
        $teams = $selectedPotId > 0
            ? $this->teamModel->where('pot_id', $selectedPotId)->orderBy('sort_order', 'ASC')->orderBy('name', 'ASC')->findAll()
            : [];
    }

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
            'pot_id' => $team['pot_id'] !== null ? (int) $team['pot_id'] : null,
            'name' => (string) $team['name'],
            'sort_order' => (int) ($team['sort_order'] ?? 0),
            'member_count' => count($members),
            'member_text' => implode(', ', $members),
            'members' => $members,
            'games_played' => (int) $scoreStats['games_played'],
            'total_score' => (int) $scoreStats['total_score'],
            'total_kill' => (int) $scoreStats['total_kill'],
            'pot_name' => trim((string) ($team['pot_name'] ?? '')),
            'tournament_name' => trim((string) ($team['tournament_name'] ?? '')),
            'scope_label' => $team['pot_id'] === null
                ? 'Tanpa Pot'
                : trim((string) ($team['pot_name'] ?? '') . (trim((string) ($team['tournament_name'] ?? '')) !== '' ? ' / ' . trim((string) ($team['tournament_name'] ?? '')) : '')),
        ];
    }

    $lookupRows = $this->lookupTeamsForManager($selectedTournamentId);
    $lookupIds = array_map(static fn (array $team): int => (int) $team['id'], $lookupRows);
    $lookupMembersByTeam = $this->memberNamesByTeam($lookupIds);
    $lookupPayload = [];

    foreach ($lookupRows as $team) {
        $teamId = (int) $team['id'];
        $members = array_values(array_filter($lookupMembersByTeam[$teamId] ?? [], static fn (string $name): bool => $name !== ''));
        $potName = trim((string) ($team['pot_name'] ?? ''));
        $tournamentName = trim((string) ($team['tournament_name'] ?? ''));
        $scopeLabel = $team['pot_id'] === null
            ? 'Tanpa Pot'
            : trim($potName . ($tournamentName !== '' ? ' / ' . $tournamentName : ''));

        $lookupPayload[] = [
            'id' => $teamId,
            'pot_id' => $team['pot_id'] !== null ? (int) $team['pot_id'] : null,
            'tournament_id' => $team['tournament_id'] !== null ? (int) $team['tournament_id'] : null,
            'name' => (string) $team['name'],
            'sort_order' => (int) ($team['sort_order'] ?? 0),
            'pot_name' => $potName,
            'tournament_name' => $tournamentName,
            'scope_label' => $scopeLabel !== '' ? $scopeLabel : 'Tanpa Pot',
            'members' => $members,
            'member_count' => count($members),
            'member_text' => implode(', ', $members),
            'search_text' => strtolower(trim(implode(' ', array_filter([
                (string) $team['name'],
                implode(', ', $members),
                $potName,
                $tournamentName,
            ])))),
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
        'lookupTeams' => $lookupPayload,
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
        $allowsUnassigned = $this->allowsUnassignedTeams();
        $potId = $this->normalizePotId($data['pot_id'] ?? null);

        if (! $this->validateData($data, $this->rules())) {
            return $this->teamErrorResponse('Data team belum valid.', $isAjax, $this->validator->getErrors());
        }

        $pot = $potId !== null ? $this->potWithTournament($potId) : null;

        if ($potId !== null && $pot === null) {
            return $this->teamErrorResponse('Pot tujuan tidak ditemukan.', $isAjax);
        }

        if (! $allowsUnassigned && $potId === null) {
            return $this->teamErrorResponse('Pot tujuan wajib dipilih.', $isAjax);
        }

        if ($pot !== null && ! $this->isPotEditable($pot)) {
            return $this->teamErrorResponse('Tournament sudah finished. Team tidak bisa ditambah lagi.', $isAjax);
        }

        $existingCount = $potId !== null
            ? $this->teamModel->where('pot_id', $potId)->countAllResults()
            : $this->teamModel->where('pot_id IS NULL', null, false)->countAllResults();
        $name = trim((string) ($data['name'] ?? ''));
        $sortOrder = trim((string) ($data['sort_order'] ?? ''));
        $memberText = (string) ($data['member_text'] ?? '');

        if ($name === '') {
            $name = $this->nextAvailableTeamName($potId, $existingCount + 1);
        } elseif (($duplicate = $this->findDuplicateTeamInScope($name, $potId)) !== null) {
            return $this->teamErrorResponse($this->duplicateTeamMessage($name, $duplicate, $potId), $isAjax);
        }

        $teamId = (int) $this->teamModel->insert([
            'pot_id'     => $potId,
            'name'       => $name,
            'sort_order' => $sortOrder === '' ? ($existingCount + 1) : (int) $sortOrder,
        ], true);

        $this->syncMembersFromText($teamId, $memberText);

        $redirectTo = trim((string) ($data['redirect_to'] ?? ''));
        $targetUrl = $redirectTo !== '' ? $redirectTo : ($potId !== null ? site_url('pots/' . $potId . '/scores') : site_url('imports/teams'));

        if ($isAjax) {
            return $this->response->setJSON([
                'status'        => 'success',
                'message'       => 'Team berhasil ditambahkan.',
                'teamId'        => $teamId,
                'potId'         => $potId ?? 0,
                'redirectUrl'   => $targetUrl,
                'csrfTokenName' => csrf_token(),
                'csrfHash'      => csrf_hash(),
            ]);
        }

        if ($redirectTo !== '') {
            return redirect()->to($redirectTo)->with('success', 'Team berhasil ditambahkan.');
        }

        return redirect()->to($potId !== null ? site_url('pots/' . $potId . '/scores') : site_url('imports/teams'))->with('success', 'Team berhasil ditambahkan.');
    }

    public function update(int $id)
    {
        $team = $this->teamModel->find($id);
        $isAjax = $this->request->isAJAX();
        $allowsUnassigned = $this->allowsUnassignedTeams();

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

        $pot = isset($team['pot_id']) && $team['pot_id'] !== null ? $this->potWithTournament((int) $team['pot_id']) : null;
        if ($team['pot_id'] !== null && $pot === null) {
            return $this->teamErrorResponse('Pot team tidak ditemukan.', $isAjax);
        }

        if ($pot !== null && ! $this->isPotEditable($pot)) {
            return $this->teamErrorResponse('Tournament sudah finished. Team tidak bisa diubah.', $isAjax);
        }

        $data = $this->request->getPost(['pot_id', 'name', 'sort_order', 'redirect_to', 'member_text']);

        if (! $this->validateData($data, $this->rules())) {
            return $this->teamErrorResponse('Perubahan team belum valid.', $isAjax, $this->validator->getErrors());
        }

        $targetPotId = $this->normalizePotId($data['pot_id'] ?? $team['pot_id']);
        $targetPot = $targetPotId !== null ? $this->potWithTournament($targetPotId) : null;

        if (! $allowsUnassigned && $targetPotId === null) {
            return $this->teamErrorResponse('Pot tujuan wajib dipilih.', $isAjax);
        }

        if ($targetPotId !== null && $targetPot === null) {
            return $this->teamErrorResponse('Pot tujuan tidak ditemukan.', $isAjax);
        }

        if ($targetPot !== null && ! $this->isPotEditable($targetPot)) {
            return $this->teamErrorResponse('Tournament sudah finished. Team tidak bisa dipindah.', $isAjax);
        }

        $name = trim((string) ($data['name'] ?? ''));
        $sortOrder = trim((string) ($data['sort_order'] ?? ''));
        $finalName = $name === '' ? (string) $team['name'] : $name;

        if (($duplicate = $this->findDuplicateTeamInScope($finalName, $targetPotId, $id)) !== null) {
            return $this->teamErrorResponse($this->duplicateTeamMessage($finalName, $duplicate, $targetPotId), $isAjax);
        }

        $this->teamModel->update($id, [
            'pot_id'     => $targetPotId,
            'name'       => $finalName,
            'sort_order' => $sortOrder === '' ? (int) $team['sort_order'] : (int) $sortOrder,
        ]);

        if ($this->request->getPost('member_text') !== null) {
            $this->syncMembersFromText($id, (string) ($data['member_text'] ?? ''));
        }

        $redirectTo = trim((string) ($data['redirect_to'] ?? ''));
        if ($isAjax) {
            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Team berhasil diperbarui.',
                'teamId' => $id,
                'potId' => $targetPotId ?? 0,
                'previousPotId' => (int) ($team['pot_id'] ?? 0),
                'csrfTokenName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        if ($redirectTo !== '') {
            return redirect()->to($redirectTo)->with('success', 'Team berhasil diperbarui.');
        }

        return redirect()->to($targetPotId !== null ? site_url('pots/' . $targetPotId . '/scores') : site_url('imports/teams'))->with('success', 'Team berhasil diperbarui.');
    }


public function delete(int $id)
{
    return $this->destroyTeam($id, 'delete');
}

public function detach(int $id)
{
    return $this->destroyTeam($id, 'detach');
}

private function destroyTeam(int $id, string $mode)
{
    $team = $this->teamModel->find($id);
    $isAjax = $this->request->isAJAX();
    $deleteMode = $mode === 'detach' ? 'detach' : 'delete';

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

    $potId = isset($team['pot_id']) && $team['pot_id'] !== null ? (int) $team['pot_id'] : null;
    $pot = $potId !== null ? $this->potWithTournament($potId) : null;
    if ($potId !== null && $pot === null) {
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

    if ($pot !== null && ! $this->isPotEditable($pot)) {
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
    $redirectTo = trim((string) ($this->request->getPost('redirect_to') ?? ''));
    $fallbackUrl = $redirectTo !== '' ? $redirectTo : ($potId !== null ? site_url('pots/' . $potId . '/scores') : site_url('imports/teams'));

    try {
        $db->transException(true)->transStart();
        if ($deleteMode === 'detach') {
            if ($potId !== null) {
                $this->scoreModel->where('team_id', $id)->where('pot_id', $potId)->delete();
            }

            $nextSortOrder = $this->teamModel->where('pot_id IS NULL', null, false)->countAllResults() + 1;
            $this->teamModel->update($id, [
                'pot_id' => null,
                'sort_order' => $nextSortOrder,
            ]);
        } else {
            $this->scoreModel->where('team_id', $id)->delete();
            $this->teamMemberModel->where('team_id', $id)->delete();
            $this->teamModel->delete($id);
        }
        $db->transComplete();
    } catch (\Throwable $e) {
        if ($db->transStatus() !== false) {
            $db->transRollback();
        }

        if ($isAjax) {
            return $this->response->setStatusCode(500)->setJSON([
                'status'        => 'error',
                'message'       => $deleteMode === 'detach'
                    ? 'Team gagal dilepas dari pot.'
                    : 'Team gagal dihapus. Data score terkait belum bisa dibersihkan.',
                'csrfTokenName' => csrf_token(),
                'csrfHash'      => csrf_hash(),
            ]);
        }

        return redirect()->to($fallbackUrl)
            ->with('error', $deleteMode === 'detach'
                ? 'Team gagal dilepas dari pot.'
                : 'Team gagal dihapus. Data score terkait belum bisa dibersihkan.');
    }

    if ($isAjax) {
        return $this->response->setJSON([
            'status'        => 'success',
            'message'       => $deleteMode === 'detach'
                ? 'Team berhasil dilepas dari pot. Data team tetap tersimpan.'
                : 'Team berhasil dihapus.',
            'teamId'        => $id,
            'potId'         => $potId ?? 0,
            'csrfTokenName' => csrf_token(),
            'csrfHash'      => csrf_hash(),
        ]);
    }

    return redirect()->to($fallbackUrl)
        ->with('success', $deleteMode === 'detach'
            ? 'Team berhasil dilepas dari pot. Data team tetap tersimpan.'
            : 'Team berhasil dihapus.');
}

    private function rules(): array
    {
        return [
            'pot_id' => [
                'label' => 'Pot',
                'rules' => 'permit_empty|is_natural',
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
                'team_id'     => $teamId,
                'player_name' => $memberName,
                'player_role' => null,
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

    private function memberNamesByTeam(array $teamIds): array
    {
        if ($teamIds === []) {
            return [];
        }

        $membersByTeam = [];
        $members = $this->teamMemberModel
            ->whereIn('team_id', $teamIds)
            ->orderBy('id', 'ASC')
            ->findAll();

        foreach ($members as $member) {
            $name = trim((string) ($member['player_name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $membersByTeam[(int) $member['team_id']][] = $name;
        }

        return $membersByTeam;
    }

    private function lookupTeamsForManager(int $selectedTournamentId): array
    {
        $builder = $this->teamModel
            ->select('teams.id, teams.pot_id, teams.name, teams.sort_order, pots.name AS pot_name, pots.tournament_id AS tournament_id, tournaments.name AS tournament_name')
            ->join('pots', 'pots.id = teams.pot_id', 'left')
            ->join('tournaments', 'tournaments.id = pots.tournament_id', 'left');

        if ($selectedTournamentId > 0) {
            $builder->groupStart()
                ->where('pots.tournament_id', $selectedTournamentId)
                ->orWhere('teams.pot_id IS NULL', null, false)
                ->groupEnd();
        }

        return $builder
            ->orderBy('teams.name', 'ASC')
            ->orderBy('teams.sort_order', 'ASC')
            ->findAll();
    }

    private function normalizePotId($value): ?int
    {
        $value = trim((string) ($value ?? ''));
        if ($value === '' || $value === '0' || ! ctype_digit($value)) {
            return null;
        }

        $potId = (int) $value;
        return $potId > 0 ? $potId : null;
    }

    private function allowsUnassignedTeams(): bool
    {
        return trim((string) ($this->request->getPost('manager_context') ?? '')) === 'import';
    }

    private function findDuplicateTeamInScope(string $teamName, ?int $targetPotId = null, ?int $excludeTeamId = null): ?array
    {
        $normalizedTeamName = $this->normalizeName($teamName);
        if ($normalizedTeamName === '') {
            return null;
        }

        $builder = $this->teamModel
            ->select('teams.id, teams.pot_id, teams.name, pots.name AS pot_name, pots.tournament_id')
            ->join('pots', 'pots.id = teams.pot_id', 'left');

        if ($excludeTeamId !== null) {
            $builder->where('teams.id !=', $excludeTeamId);
        }

        if ($targetPotId !== null) {
            $targetPot = $this->potModel->select('id, tournament_id')->find($targetPotId);
            if ($targetPot !== null) {
                $builder->groupStart()
                    ->where('pots.tournament_id', (int) $targetPot['tournament_id'])
                    ->orWhere('teams.pot_id IS NULL', null, false)
                    ->groupEnd();
            }
        }

        foreach ($builder->findAll() as $team) {
            if ($this->normalizeName((string) ($team['name'] ?? '')) === $normalizedTeamName) {
                return $team;
            }
        }

        return null;
    }

    private function duplicateTeamMessage(string $teamName, array $duplicateTeam, ?int $targetPotId = null): string
    {
        $safeTeamName = trim($teamName) !== '' ? trim($teamName) : 'Team ini';

        if ($duplicateTeam['pot_id'] === null || (int) $duplicateTeam['pot_id'] === 0) {
            return 'Nama team "' . $safeTeamName . '" sudah ada di database tanpa pot. Pilih team yang sudah ada dari dropdown supaya tidak duplikat.';
        }

        if ($targetPotId !== null && (int) $duplicateTeam['pot_id'] === $targetPotId) {
            return 'Nama team "' . $safeTeamName . '" sudah ada di pot ini. Gunakan team yang sudah ada.';
        }

        $potName = trim((string) ($duplicateTeam['pot_name'] ?? ''));
        if ($potName !== '') {
            return 'Nama team "' . $safeTeamName . '" sudah ada di ' . $potName . '. Pindahkan team yang ada, jangan buat duplikat baru.';
        }

        return 'Nama team "' . $safeTeamName . '" sudah ada di database. Gunakan team yang sudah ada supaya tidak duplikat.';
    }

    private function nextAvailableTeamName(?int $targetPotId, int $seed = 1): string
    {
        $candidate = max(1, $seed);

        while (true) {
            $name = 'Team ' . $candidate;
            if ($this->findDuplicateTeamInScope($name, $targetPotId) === null) {
                return $name;
            }

            $candidate++;
        }
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
