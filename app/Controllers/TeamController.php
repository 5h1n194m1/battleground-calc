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
        $tournaments = $this->tournamentModel->orderBy('created_at', 'DESC')->findAll();
        $selectedTournamentKey = trim((string) ($this->request->getGet('tournament_id') ?? 'all'));
        if ($selectedTournamentKey === '') {
            $selectedTournamentKey = 'all';
        }

        $selectedTournamentId = ctype_digit($selectedTournamentKey) ? (int) $selectedTournamentKey : 0;
        $showOnlyWithoutTournament = $selectedTournamentKey === 'none';
        $showOnlyWithoutPot = $selectedTournamentKey === 'unassigned';

        $teamsBuilder = $this->teamModel
            ->select('teams.id, teams.tournament_id, teams.name, teams.sort_order, teams.pot_id, pots.name AS pot_name, COALESCE(pot_tournaments.name, team_tournaments.name) AS tournament_name, COALESCE(pot_tournaments.status, team_tournaments.status) AS tournament_status')
            ->join('pots', 'pots.id = teams.pot_id', 'left')
            ->join('tournaments AS pot_tournaments', 'pot_tournaments.id = pots.tournament_id', 'left')
            ->join('tournaments AS team_tournaments', 'team_tournaments.id = teams.tournament_id', 'left');

        if ($selectedTournamentId > 0) {
            $teamsBuilder->groupStart()
                ->where('pots.tournament_id', $selectedTournamentId)
                ->orGroupStart()
                    ->where('teams.pot_id IS NULL', null, false)
                    ->where('teams.tournament_id', $selectedTournamentId)
                ->groupEnd()
                ->groupEnd();
        } elseif ($showOnlyWithoutPot) {
            $teamsBuilder->where('teams.pot_id IS NULL', null, false);
        } elseif ($showOnlyWithoutTournament) {
            $teamsBuilder->where('teams.pot_id IS NULL', null, false)
                ->groupStart()
                    ->where('teams.tournament_id IS NULL', null, false)
                    ->orWhere('teams.tournament_id', 0)
                ->groupEnd();
        }

        $teams = $teamsBuilder
            ->orderBy('COALESCE(pot_tournaments.name, team_tournaments.name)', 'ASC', false)
            ->orderBy('pots.sort_order', 'ASC')
            ->orderBy('teams.sort_order', 'ASC')
            ->orderBy('teams.name', 'ASC')
            ->findAll();

        $potOptionsBuilder = $this->potModel
            ->select('pots.id, pots.name, pots.sort_order, tournaments.id AS tournament_id, tournaments.name AS tournament_name, tournaments.status AS tournament_status')
            ->join('tournaments', 'tournaments.id = pots.tournament_id');

        if ($selectedTournamentId > 0) {
            $potOptionsBuilder->where('pots.tournament_id', $selectedTournamentId);
        }

        $potOptions = $potOptionsBuilder
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
                'tournament_id'   => $team['tournament_id'] !== null ? (int) $team['tournament_id'] : null,
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
            'pageTitle'            => 'Daftar Team',
            'rows'                 => $rows,
            'tournaments'          => $tournaments,
            'selectedTournamentKey'=> $selectedTournamentKey,
            'selectedTournamentId' => $selectedTournamentId,
            'potOptions'           => array_map(static fn (array $pot): array => [
                'id'              => (int) $pot['id'],
                'tournament_id'   => (int) ($pot['tournament_id'] ?? 0),
                'name'            => (string) ($pot['name'] ?? 'Pot'),
                'sort_order'      => (int) ($pot['sort_order'] ?? 0),
                'tournament_name' => (string) ($pot['tournament_name'] ?? 'Tournament'),
                'status'          => (string) ($pot['tournament_status'] ?? TournamentModel::STATUS_BELUM_MULAI),
                'label'           => (string) ($pot['name'] ?? 'Pot'),
            ], $potOptions),
        ]);
    }

    public function exportTemplate()
    {
        $tournamentId = (int) ($this->request->getGet('tournament_id') ?? 0);
        if ($tournamentId <= 0) {
            return redirect()->to(site_url('teams/roster'))->with('error', 'Pilih tournament terlebih dahulu sebelum export.');
        }

        $tournament = $this->tournamentModel->find($tournamentId);
        if ($tournament === null) {
            return redirect()->to(site_url('teams/roster'))->with('error', 'Tournament tidak ditemukan.');
        }

        $payload = $this->buildTournamentTemplateExport($tournamentId);

        return view('teams/export_template', [
            'pageTitle'       => 'Export Template CSV',
            'tournament'      => $tournament,
            'exports'         => $payload['exports'],
            'unassignedTeams' => $payload['unassignedTeams'],
        ]);
    }

    public function downloadTemplateCsv(int $potId)
    {
        $pot = $this->potWithTournament($potId);
        if ($pot === null) {
            throw PageNotFoundException::forPageNotFound('Pot tidak ditemukan.');
        }

        $payload = $this->buildPotTemplateExport($potId);
        $filename = url_title((string) ($pot['tournament_name'] ?? 'tournament'), '-', true)
            . '-' . url_title((string) ($pot['name'] ?? 'pot'), '-', true) . '.csv';

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody("\xEF\xBB\xBF" . $payload['csvText']);
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
        $data = $this->request->getPost(['tournament_id', 'pot_id', 'name', 'sort_order', 'redirect_to', 'member_text']);
        $allowsUnassigned = $this->allowsUnassignedTeams();
        $potId = $this->normalizePotId($data['pot_id'] ?? null);
        $requestedTournamentId = $this->normalizeTournamentId($data['tournament_id'] ?? null);

        if (! $this->validateData($data, $this->rules())) {
            return $this->teamErrorResponse('Data team belum valid.', $isAjax, $this->validator->getErrors());
        }

        $pot = $potId !== null ? $this->potWithTournament($potId) : null;

        if ($potId !== null && $pot === null) {
            return $this->teamErrorResponse('Pot tujuan tidak ditemukan.', $isAjax);
        }

        $targetTournamentId = $pot !== null
            ? (int) ($pot['tournament_id'] ?? 0)
            : $requestedTournamentId;

        if (! $allowsUnassigned && $potId === null) {
            return $this->teamErrorResponse('Pot tujuan wajib dipilih.', $isAjax);
        }

        if ($allowsUnassigned && $targetTournamentId <= 0) {
            return $this->teamErrorResponse('Tournament wajib dipilih terlebih dahulu.', $isAjax);
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
        } elseif (($duplicate = $this->findDuplicateTeamInScope($name, $potId, null, $targetTournamentId)) !== null) {
            return $this->teamErrorResponse($this->duplicateTeamMessage($name, $duplicate, $potId), $isAjax);
        }

        $teamId = (int) $this->teamModel->insert([
            'tournament_id' => $targetTournamentId > 0 ? $targetTournamentId : null,
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
        $isAjax = $this->request->isAJAX();
        $data = $this->request->getPost(['tournament_id', 'pot_id', 'name', 'sort_order', 'redirect_to', 'member_text']);
        $redirectTo = trim((string) ($data['redirect_to'] ?? ''));
        $result = $this->applyTeamUpdate($id, $data, $this->allowsUnassignedTeams());

        if (! $result['ok']) {
            return $this->teamErrorResponse($result['message'], $isAjax, $result['validation']);
        }

        if ($isAjax) {
            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Team berhasil diperbarui.',
                'teamId' => $id,
                'potId' => $result['potId'] ?? 0,
                'previousPotId' => $result['previousPotId'] ?? 0,
                'csrfTokenName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        if ($redirectTo !== '') {
            return redirect()->to($redirectTo)->with('success', 'Team berhasil diperbarui.');
        }

        return redirect()->to($redirectTo !== '' ? $redirectTo : site_url('teams/roster'))->with('success', 'Team berhasil diperbarui.');
    }

    public function bulkUpdate()
    {
        $redirectTo = trim((string) ($this->request->getPost('redirect_to') ?? current_url()));
        $allowsUnassigned = true;
        $teamsPayload = $this->request->getPost('teams');

        if (! is_array($teamsPayload) || $teamsPayload === []) {
            return redirect()->to($redirectTo)->with('error', 'Belum ada perubahan team yang bisa disimpan.');
        }

        $singleTeamId = $this->normalizeBulkTeamId($this->request->getPost('save_single_team_id'));
        if ($singleTeamId === null) {
            $teamsPayload = $this->filterChangedBulkPayload($teamsPayload);
            if ($teamsPayload === []) {
                return redirect()->to($redirectTo)->with('info', 'Tidak ada perubahan baru untuk disimpan.');
            }
        }

        $targetTeamIds = $singleTeamId !== null ? [$singleTeamId] : array_map('intval', array_keys($teamsPayload));
        $updatedCount = 0;
        $db = db_connect();

        try {
            $db->transException(true)->transStart();

            foreach ($targetTeamIds as $teamId) {
                if (! isset($teamsPayload[$teamId]) || ! is_array($teamsPayload[$teamId])) {
                    continue;
                }

                $rowData = $teamsPayload[$teamId];
                $rowData['redirect_to'] = $redirectTo;
                $result = $this->applyTeamUpdate((int) $teamId, $rowData, $allowsUnassigned);

                if (! $result['ok']) {
                    throw new \RuntimeException($result['message']);
                }

                $updatedCount++;
            }

            $db->transComplete();
        } catch (\Throwable $e) {
            if ($db->transStatus() !== false) {
                $db->transRollback();
            }

            return redirect()->to($redirectTo)->withInput()->with('error', $e->getMessage());
        }

        if ($updatedCount <= 0) {
            return redirect()->to($redirectTo)->with('error', 'Tidak ada team yang diperbarui.');
        }

        $successMessage = $singleTeamId !== null
            ? 'Team berhasil diperbarui.'
            : $updatedCount . ' team berhasil diperbarui.';

        return redirect()->to($redirectTo)->with('success', $successMessage);
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
                'tournament_id' => $pot !== null ? (int) ($pot['tournament_id'] ?? 0) : null,
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
            'tournament_id' => [
                'label' => 'Tournament',
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

    private function buildTournamentTemplateExport(int $tournamentId): array
    {
        $pots = $this->potModel
            ->where('tournament_id', $tournamentId)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('name', 'ASC')
            ->findAll();

        $exports = [];
        foreach ($pots as $pot) {
            $exports[] = $this->buildPotTemplateExport((int) $pot['id']);
        }

        $unassignedTeams = $this->teamModel
            ->where('tournament_id', $tournamentId)
            ->where('pot_id IS NULL', null, false)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('name', 'ASC')
            ->findAll();

        return [
            'exports' => $exports,
            'unassignedTeams' => $unassignedTeams,
        ];
    }

    private function buildPotTemplateExport(int $potId): array
    {
        $pot = $this->potWithTournament($potId);
        if ($pot === null) {
            throw PageNotFoundException::forPageNotFound('Pot tidak ditemukan.');
        }

        $teams = $this->teamModel
            ->where('pot_id', $potId)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('name', 'ASC')
            ->findAll();

        $teamIds = array_map(static fn (array $team): int => (int) $team['id'], $teams);
        $scores = $teamIds === []
            ? []
            : $this->scoreModel
                ->where('pot_id', $potId)
                ->whereIn('team_id', $teamIds)
                ->orderBy('game_no', 'ASC')
                ->findAll();

        $scoresByTeam = [];
        $gameCount = 2;
        foreach ($scores as $score) {
            $teamId = (int) $score['team_id'];
            $gameNo = (int) $score['game_no'];
            $scoresByTeam[$teamId][$gameNo] = $score;
            $gameCount = max($gameCount, $gameNo);
        }

        $gameCount = max(2, $gameCount);
        $matrix = $this->buildTemplateMatrix($teams, $scoresByTeam, $gameCount, (string) ($pot['name'] ?? 'POT'));

        return [
            'pot' => $pot,
            'teams' => $teams,
            'gameCount' => $gameCount,
            'matrix' => $matrix,
            'csvText' => $this->matrixToCsv($matrix),
            'clipboardText' => $this->matrixToTsv($matrix),
        ];
    }

    private function buildTemplateMatrix(array $teams, array $scoresByTeam, int $gameCount, string $potName): array
    {
        $headerRow1 = ['No', 'Nama Tim', '', ''];
        $headerRow2 = ['', '', '', ''];
        $headerRow3 = ['', '', '', ''];

        for ($gameNo = 1; $gameNo <= $gameCount; $gameNo++) {
            $headerRow1 = array_merge($headerRow1, [$gameNo === 1 ? strtoupper($potName) : '', '', '']);
            $headerRow2 = array_merge($headerRow2, ['GAME ' . $gameNo, '', '']);
            $headerRow3 = array_merge($headerRow3, ['Rank', 'P.Rank', 'P.Kill']);
        }

        $headerRow1[] = 'Total Point';
        $headerRow2[] = '';
        $headerRow3[] = '';

        $rows = [$headerRow1, $headerRow2, $headerRow3];

        foreach ($teams as $index => $team) {
            $excelRow = $index + 4;
            $row = [
                $index + 1,
                (string) ($team['name'] ?? ''),
                '',
                '',
            ];

            $totalRefs = [];
            for ($gameNo = 1; $gameNo <= $gameCount; $gameNo++) {
                $rankColumn = $this->excelColumnName(5 + (($gameNo - 1) * 3));
                $placementColumn = $this->excelColumnName(6 + (($gameNo - 1) * 3));
                $killColumn = $this->excelColumnName(7 + (($gameNo - 1) * 3));
                $rankValue = $scoresByTeam[(int) $team['id']][$gameNo]['rank_no'] ?? '';
                $killValue = $scoresByTeam[(int) $team['id']][$gameNo]['kill_point'] ?? '';

                $row[] = $rankValue !== '' ? (int) $rankValue : '';
                $row[] = '=IF(' . $rankColumn . $excelRow . '="","",IFERROR(IFS(VALUE(' . $rankColumn . $excelRow . ')=0,0,VALUE(' . $rankColumn . $excelRow . ')=1,12,VALUE(' . $rankColumn . $excelRow . ')=2,9,VALUE(' . $rankColumn . $excelRow . ')=3,8,VALUE(' . $rankColumn . $excelRow . ')=4,7,VALUE(' . $rankColumn . $excelRow . ')=5,6,VALUE(' . $rankColumn . $excelRow . ')=6,5,VALUE(' . $rankColumn . $excelRow . ')=7,4,VALUE(' . $rankColumn . $excelRow . ')=8,3,VALUE(' . $rankColumn . $excelRow . ')=9,2,VALUE(' . $rankColumn . $excelRow . ')=10,1,VALUE(' . $rankColumn . $excelRow . ')>=11,0),0))';
                $row[] = $killValue !== '' ? (int) $killValue : '';

                $totalRefs[] = $placementColumn . $excelRow;
                $totalRefs[] = $killColumn . $excelRow;
            }

            $row[] = '=IF(B' . $excelRow . '="","",SUM(' . implode(',', $totalRefs) . '))';
            $rows[] = $row;
        }

        return $rows;
    }

    private function matrixToCsv(array $matrix): string
    {
        $lines = array_map(function (array $row): string {
            $escaped = array_map(function ($value): string {
                $string = str_replace(["\r", "\n"], ' ', (string) ($value ?? ''));
                $string = str_replace('"', '""', $string);

                return '"' . $string . '"';
            }, $row);

            return implode(',', $escaped);
        }, $matrix);

        return implode("\r\n", $lines) . "\r\n";
    }

    private function matrixToTsv(array $matrix): string
    {
        $lines = array_map(static function (array $row): string {
            $escaped = array_map(static function ($value): string {
                return str_replace(["\t", "\r", "\n"], [' ', ' ', ' '], (string) ($value ?? ''));
            }, $row);

            return implode("\t", $escaped);
        }, $matrix);

        return implode("\r\n", $lines) . "\r\n";
    }

    private function excelColumnName(int $columnNumber): string
    {
        $name = '';
        $index = $columnNumber;

        while ($index > 0) {
            $index--;
            $name = chr(65 + ($index % 26)) . $name;
            $index = intdiv($index, 26);
        }

        return $name;
    }

    private function syncMembersFromText(int $teamId, string $memberText): void
    {
        $members = $this->memberListFromText($memberText);

        $this->teamMemberModel->where('team_id', $teamId)->delete();
        foreach ($members as $memberName) {
            $this->teamMemberModel->insert([
                'team_id'     => $teamId,
                'player_name' => $memberName,
                'player_role' => null,
            ]);
        }
    }

    private function normalizeMemberText(string $memberText): string
    {
        return implode(', ', $this->memberListFromText($memberText));
    }

    private function memberListFromText(string $memberText): array
    {
        $normalizedMemberText = str_replace([";", "
", "
"], [",", "
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

            $members[] = preg_replace('/\s+/', ' ', $name) ?? $name;
        }

        return array_slice(array_values(array_unique($members)), 0, self::MAX_MEMBERS);
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
            ->select('teams.id, teams.tournament_id, teams.pot_id, teams.name, teams.sort_order, pots.name AS pot_name, COALESCE(pots.tournament_id, teams.tournament_id) AS tournament_id, COALESCE(pot_tournaments.name, team_tournaments.name) AS tournament_name')
            ->join('pots', 'pots.id = teams.pot_id', 'left')
            ->join('tournaments AS pot_tournaments', 'pot_tournaments.id = pots.tournament_id', 'left')
            ->join('tournaments AS team_tournaments', 'team_tournaments.id = teams.tournament_id', 'left');

        if ($selectedTournamentId > 0) {
            $builder->groupStart()
                ->where('pots.tournament_id', $selectedTournamentId)
                ->orGroupStart()
                    ->where('teams.pot_id IS NULL', null, false)
                    ->where('teams.tournament_id', $selectedTournamentId)
                ->groupEnd()
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

    private function normalizeTournamentId($value): ?int
    {
        $value = trim((string) ($value ?? ''));
        if ($value === '' || $value === '0' || ! ctype_digit($value)) {
            return null;
        }

        $tournamentId = (int) $value;
        return $tournamentId > 0 ? $tournamentId : null;
    }

    private function allowsUnassignedTeams(): bool
    {
        return trim((string) ($this->request->getPost('manager_context') ?? '')) === 'import';
    }

    private function normalizeBulkTeamId($value): ?int
    {
        $value = trim((string) ($value ?? ''));
        if ($value === '' || ! ctype_digit($value)) {
            return null;
        }

        $teamId = (int) $value;
        return $teamId > 0 ? $teamId : null;
    }

    private function filterChangedBulkPayload(array $teamsPayload): array
    {
        $changed = [];

        foreach ($teamsPayload as $teamId => $rowData) {
            if (! is_array($rowData)) {
                continue;
            }

            if ($this->bulkRowHasChanges($rowData)) {
                $changed[$teamId] = $rowData;
            }
        }

        return $changed;
    }

    private function bulkRowHasChanges(array $rowData): bool
    {
        return trim((string) ($rowData['__changed'] ?? '')) === '1';
    }

    private function applyTeamUpdate(int $id, array $data, bool $allowsUnassigned): array
    {
        $team = $this->teamModel->find($id);
        if ($team === null) {
            return [
                'ok' => false,
                'message' => 'Team tidak ditemukan.',
                'validation' => [],
            ];
        }

        $pot = isset($team['pot_id']) && $team['pot_id'] !== null ? $this->potWithTournament((int) $team['pot_id']) : null;
        if ($team['pot_id'] !== null && $pot === null) {
            return [
                'ok' => false,
                'message' => 'Pot team tidak ditemukan.',
                'validation' => [],
            ];
        }

        if ($pot !== null && ! $this->isPotEditable($pot)) {
            return [
                'ok' => false,
                'message' => 'Tournament sudah finished. Team tidak bisa diubah.',
                'validation' => [],
            ];
        }

        if (! $this->validateData($data, $this->rules())) {
            return [
                'ok' => false,
                'message' => 'Perubahan team belum valid.',
                'validation' => $this->validator->getErrors(),
            ];
        }

        $targetPotId = $this->normalizePotId($data['pot_id'] ?? $team['pot_id']);
        $requestedTournamentId = $this->normalizeTournamentId($data['tournament_id'] ?? $team['tournament_id'] ?? null);
        $targetPot = $targetPotId !== null ? $this->potWithTournament($targetPotId) : null;

        if ($targetPot !== null && $requestedTournamentId !== null && (int) ($targetPot['tournament_id'] ?? 0) !== $requestedTournamentId) {
            $targetPotId = null;
            $targetPot = null;
        }

        $targetTournamentId = $targetPot !== null
            ? (int) ($targetPot['tournament_id'] ?? 0)
            : $requestedTournamentId;

        if (! $allowsUnassigned && $targetPotId === null) {
            return [
                'ok' => false,
                'message' => 'Pot tujuan wajib dipilih.',
                'validation' => [],
            ];
        }

        if ($allowsUnassigned && $targetTournamentId <= 0 && $targetPotId !== null) {
            return [
                'ok' => false,
                'message' => 'Tournament team wajib dipilih.',
                'validation' => [],
            ];
        }

        if ($targetPotId !== null && $targetPot === null) {
            return [
                'ok' => false,
                'message' => 'Pot tujuan tidak ditemukan.',
                'validation' => [],
            ];
        }

        if ($targetPot !== null && ! $this->isPotEditable($targetPot)) {
            return [
                'ok' => false,
                'message' => 'Tournament sudah finished. Team tidak bisa dipindah.',
                'validation' => [],
            ];
        }

        $name = trim((string) ($data['name'] ?? ''));
        $sortOrder = trim((string) ($data['sort_order'] ?? ''));
        $finalName = $name === '' ? (string) $team['name'] : $name;

        if (($duplicate = $this->findDuplicateTeamInScope($finalName, $targetPotId, $id, $targetTournamentId)) !== null) {
            return [
                'ok' => false,
                'message' => $this->duplicateTeamMessage($finalName, $duplicate, $targetPotId),
                'validation' => [],
            ];
        }

        $this->teamModel->update($id, [
            'tournament_id' => $targetTournamentId > 0 ? $targetTournamentId : null,
            'pot_id' => $targetPotId,
            'name' => $finalName,
            'sort_order' => $sortOrder === '' ? (int) $team['sort_order'] : (int) $sortOrder,
        ]);

        if (array_key_exists('member_text', $data)) {
            $this->syncMembersFromText($id, (string) ($data['member_text'] ?? ''));
        }

        return [
            'ok' => true,
            'message' => 'Team berhasil diperbarui.',
            'validation' => [],
            'potId' => $targetPotId ?? 0,
            'previousPotId' => (int) ($team['pot_id'] ?? 0),
        ];
    }

    private function findDuplicateTeamInScope(string $teamName, ?int $targetPotId = null, ?int $excludeTeamId = null, ?int $targetTournamentId = null): ?array
    {
        $normalizedTeamName = $this->normalizeName($teamName);
        if ($normalizedTeamName === '') {
            return null;
        }

        $builder = $this->teamModel
            ->select('teams.id, teams.tournament_id, teams.pot_id, teams.name, pots.name AS pot_name, COALESCE(pots.tournament_id, teams.tournament_id) AS tournament_id')
            ->join('pots', 'pots.id = teams.pot_id', 'left');

        if ($excludeTeamId !== null) {
            $builder->where('teams.id !=', $excludeTeamId);
        }

        if ($targetPotId !== null) {
            $targetPot = $this->potModel->select('id, tournament_id')->find($targetPotId);
            if ($targetPot !== null) {
                $builder->groupStart()
                    ->where('pots.tournament_id', (int) $targetPot['tournament_id'])
                    ->orGroupStart()
                        ->where('teams.pot_id IS NULL', null, false)
                        ->where('teams.tournament_id', (int) $targetPot['tournament_id'])
                    ->groupEnd()
                    ->groupEnd();
            }
        } elseif ($targetTournamentId !== null && $targetTournamentId > 0) {
            $builder->groupStart()
                ->where('pots.tournament_id', $targetTournamentId)
                ->orGroupStart()
                    ->where('teams.pot_id IS NULL', null, false)
                    ->where('teams.tournament_id', $targetTournamentId)
                ->groupEnd()
                ->groupEnd();
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
