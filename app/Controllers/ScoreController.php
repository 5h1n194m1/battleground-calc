<?php

namespace App\Controllers;

use App\Models\PotModel;
use App\Models\ScoreModel;
use App\Models\TeamMemberModel;
use App\Models\TeamModel;
use App\Models\TournamentModel;
use App\Services\PotOrderService;
use CodeIgniter\Exceptions\PageNotFoundException;

class ScoreController extends BaseController
{
    private const MAX_RANK = 12;

    private PotModel $potModel;
    private TeamModel $teamModel;
    private ScoreModel $scoreModel;
    private TeamMemberModel $teamMemberModel;
    private TournamentModel $tournamentModel;
    private PotOrderService $potOrderService;

    public function __construct()
    {
        $this->potModel        = new PotModel();
        $this->teamModel       = new TeamModel();
        $this->scoreModel      = new ScoreModel();
        $this->teamMemberModel = new TeamMemberModel();
        $this->tournamentModel = new TournamentModel();
        $this->potOrderService = new PotOrderService($this->potModel);
    }

    public function index(int $potId): string
    {
        $currentPot = $this->potWithTournament($potId);

        if ($currentPot === null) {
            throw PageNotFoundException::forPageNotFound('Pot tidak ditemukan.');
        }

        $pots = $this->potModel
            ->where('tournament_id', (int) $currentPot['tournament_id'])
            ->orderBy('sort_order', 'ASC')
            ->orderBy('name', 'ASC')
            ->findAll();

        $potIds = array_map(static fn (array $pot): int => (int) $pot['id'], $pots);

        $teams = $potIds === []
            ? []
            : $this->teamModel
                ->whereIn('pot_id', $potIds)
                ->orderBy('sort_order', 'ASC')
                ->orderBy('name', 'ASC')
                ->findAll();

        $teamsByPot = [];
        $teamMap = [];
        foreach ($teams as $team) {
            $teamId = (int) $team['id'];
            $teamMap[$teamId] = $team;
            $teamsByPot[(int) $team['pot_id']][] = $team;
        }

        $teamIds = array_keys($teamMap);
        $membersByTeam = [];
        if ($teamIds !== []) {
            $members = $this->teamMemberModel
                ->whereIn('team_id', $teamIds)
                ->orderBy('id', 'ASC')
                ->findAll();

            foreach ($members as $member) {
                $membersByTeam[(int) $member['team_id']][] = $member;
            }
        }

        $scores = $potIds === []
            ? []
            : $this->scoreModel
                ->whereIn('pot_id', $potIds)
                ->orderBy('game_no', 'ASC')
                ->findAll();

        $scoresByPot = [];
        $totalsByPot = [];
        $maxGameByPot = [];
        foreach ($scores as $score) {
            $potKey = (int) $score['pot_id'];
            $teamId = (int) $score['team_id'];
            $gameNo = (int) $score['game_no'];

            $scoresByPot[$potKey][$teamId][$gameNo] = $score;
            $totalsByPot[$potKey][$teamId] = ($totalsByPot[$potKey][$teamId] ?? 0) + (int) $score['total_point'];
            $maxGameByPot[$potKey] = max($maxGameByPot[$potKey] ?? 1, $gameNo);
        }

        $potModules = [];
        foreach ($pots as $pot) {
            $potKey = (int) $pot['id'];
            $potTeams = $teamsByPot[$potKey] ?? [];
            usort($potTeams, function (array $left, array $right) use ($totalsByPot, $potKey): int {
                $leftTotal = (int) ($totalsByPot[$potKey][(int) $left['id']] ?? 0);
                $rightTotal = (int) ($totalsByPot[$potKey][(int) $right['id']] ?? 0);

                if ($leftTotal !== $rightTotal) {
                    return $rightTotal <=> $leftTotal;
                }

                $leftSort = (int) ($left['sort_order'] ?? 0);
                $rightSort = (int) ($right['sort_order'] ?? 0);
                if ($leftSort !== $rightSort) {
                    return $leftSort <=> $rightSort;
                }

                return strcmp((string) ($left['name'] ?? ''), (string) ($right['name'] ?? ''));
            });
            $memberTextByTeam = [];

            foreach ($potTeams as $team) {
                $teamId = (int) $team['id'];
                $teamMembers = $membersByTeam[$teamId] ?? [];
                $memberTextByTeam[$teamId] = implode("\n", array_map(
                    static fn (array $member): string => trim((string) ($member['player_name'] ?? '')),
                    array_filter($teamMembers, static fn (array $member): bool => trim((string) ($member['player_name'] ?? '')) !== '')
                ));
            }

            $potModules[] = [
                'pot'              => $pot,
                'teams'            => $potTeams,
                'scoresByTeam'     => $scoresByPot[$potKey] ?? [],
                'totalsByTeam'     => $totalsByPot[$potKey] ?? [],
                'gameNos'          => range(1, $maxGameByPot[$potKey] ?? 1),
                'membersByTeam'    => $membersByTeam,
                'memberTextByTeam' => $memberTextByTeam,
                'isCurrent'        => $potKey === $potId,
            ];
        }

        $canManage = $this->isEditableStatus((string) ($currentPot['tournament_status'] ?? TournamentModel::STATUS_BELUM_MULAI));
        $tournaments = $this->tournamentModel->orderBy('created_at', 'DESC')->findAll();

        return view('scores/index', [
            'pageTitle'      => 'Input Score',
            'pots'           => $pots,
            'potModules'     => $potModules,
            'currentPotId'   => $potId,
            'tournament'     => [
                'id'     => (int) $currentPot['tournament_id'],
                'name'   => (string) $currentPot['tournament_name'],
                'status' => (string) ($currentPot['tournament_status'] ?? TournamentModel::STATUS_BELUM_MULAI),
            ],
            'placementMap'   => $this->placementMap(),
            'canManage'      => $canManage,
            'statusOptions'  => TournamentModel::statusOptions(),
            'tournaments'    => $tournaments,
            'managerPots'    => $pots,
        ]);
    }

    public function saveBulk()
    {
        $isAjax = $this->request->isAJAX();
        $potId = (int) ($this->request->getPost('pot_id') ?? 0);

        if ($potId <= 0) {
            return $this->saveBulkError('Pot tidak valid.', $isAjax);
        }

        $pot = $this->potWithTournament($potId);

        if ($pot === null) {
            return $this->saveBulkError('Pot tidak ditemukan.', $isAjax);
        }

        if (! $this->isEditableStatus((string) ($pot['tournament_status'] ?? TournamentModel::STATUS_BELUM_MULAI))) {
            return $this->saveBulkError('Tournament sudah finished. Data score tidak bisa diubah.', $isAjax);
        }

        $potName = trim((string) ($this->request->getPost('pot_name') ?? ''));
        $potSortOrder = trim((string) ($this->request->getPost('pot_sort_order') ?? ''));
        $potUpdate = [];
        if ($potName !== '') {
            $potUpdate['name'] = $potName;
        }
        if ($potSortOrder !== '' && ctype_digit($potSortOrder)) {
            $potUpdate['sort_order'] = (int) $potSortOrder;
        }
        if ($potUpdate !== []) {
            $this->potModel->update($potId, $potUpdate);
            if (isset($potUpdate['sort_order'])) {
                $this->potOrderService->movePot((int) $pot['tournament_id'], $potId, (int) $potUpdate['sort_order']);
            }
        }

        $teamNames = $this->request->getPost('team_names');
        $teamMembersText = $this->request->getPost('team_members_text');
        $gameCount = max(1, (int) ($this->request->getPost('game_count') ?? 1));
        $rawScores = $this->request->getPost('scores');
        if (! is_array($rawScores)) {
            $rawScores = [];
        }

        $teams = $this->teamModel
            ->where('pot_id', $potId)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('name', 'ASC')
            ->findAll();
        $existingTeamsById = [];
        $nextSortOrder = 1;
        foreach ($teams as $team) {
            $teamId = (int) $team['id'];
            $existingTeamsById[$teamId] = $team;
                $nextSortOrder = max($nextSortOrder, (int) ($team['sort_order'] ?? 0) + 1);
        }

        $scopeTeams = $this->duplicateScopeTeams((int) ($pot['tournament_id'] ?? 0));
        $scopeTeamsById = [];
        $reservedNames = [];
        foreach ($scopeTeams as $scopeTeam) {
            $scopeTeamId = (int) $scopeTeam['id'];
            $scopeTeamsById[$scopeTeamId] = $scopeTeam;

            $normalizedScopeName = $this->normalizeTeamName((string) ($scopeTeam['name'] ?? ''));
            if ($normalizedScopeName !== '' && ! isset($reservedNames[$normalizedScopeName])) {
                $reservedNames[$normalizedScopeName] = $scopeTeamId;
            }
        }

        $errors  = [];
        $resolvedTeamIds = [];
        $scoreRowsByTeamKey = [];
        $teamMemberPayload = [];
        $teamUpdateOps = [];
        $teamCreateOps = [];
        $resolvedTempTeamKeys = [];
        $attachedTempTargets = [];
        $teamNames = is_array($teamNames) ? $teamNames : [];
        $teamMembersText = is_array($teamMembersText) ? $teamMembersText : [];
        $teamKeys = array_values(array_unique(array_merge(
            array_map('strval', array_keys($teamNames)),
            array_map('strval', array_keys($rawScores)),
            array_map('strval', array_keys($teamMembersText))
        )));

        foreach ($teamKeys as $teamKey) {
            $teamName = trim((string) ($teamNames[$teamKey] ?? ''));
            $normalizedTeamName = $this->normalizeTeamName($teamName);
            $memberText = trim((string) ($teamMembersText[$teamKey] ?? ''));
            $teamScores = $rawScores[$teamKey] ?? [];
            $hasScoreValue = false;
            $parsedScoreRows = [];

            if (! is_array($teamScores)) {
                $teamScores = [];
            }

            for ($gameNo = 1; $gameNo <= $gameCount; $gameNo++) {
                $gameInput = $teamScores[$gameNo] ?? [];
                if (! is_array($gameInput)) {
                    $gameInput = [];
                }

                $rankRaw = trim((string) ($gameInput['rank_no'] ?? ''));
                $killRaw = trim((string) ($gameInput['kill_point'] ?? ''));

                if ($rankRaw === '' && $killRaw === '') {
                    continue;
                }

                $hasScoreValue = true;

                if ($rankRaw === '' || $killRaw === '') {
                    $errors[] = 'Rank dan kill harus diisi bersamaan untuk semua game yang dipakai.';
                    continue;
                }

                if (! ctype_digit($rankRaw) || ! ctype_digit($killRaw)) {
                    $errors[] = 'Rank dan kill hanya boleh berisi angka.';
                    continue;
                }

                $rankNo    = (int) $rankRaw;
                $killPoint = (int) $killRaw;

                if ($rankNo < 1 || $rankNo > self::MAX_RANK) {
                    $errors[] = 'Rank hanya boleh antara 1 sampai 12.';
                    continue;
                }

                $placementPoint = $this->calculatePlacementPoint($rankNo);
                $parsedScoreRows[] = [
                    'game_no'         => $gameNo,
                    'rank_no'         => $rankNo,
                    'kill_point'      => $killPoint,
                    'placement_point' => $placementPoint,
                    'total_point'     => $placementPoint + $killPoint,
                ];
            }

            if (ctype_digit($teamKey) && isset($existingTeamsById[(int) $teamKey])) {
                $teamId = (int) $teamKey;
                $resolvedTeamIds[$teamKey] = $teamId;
                $scoreRowsByTeamKey[$teamKey] = $parsedScoreRows;
                if (array_key_exists($teamKey, $teamMembersText)) {
                    $teamMemberPayload[$teamKey] = $memberText;
                }

                $existingName = trim((string) ($existingTeamsById[$teamId]['name'] ?? ''));
                $existingNormalizedName = $this->normalizeTeamName($existingName);

                if ($teamName !== '' && $teamName !== $existingName) {
                    $conflictRef = $reservedNames[$normalizedTeamName] ?? null;
                    if ($normalizedTeamName !== '' && $conflictRef !== null && (string) $conflictRef !== (string) $teamId) {
                        $errors[] = $this->duplicateNameMessage($teamName, $conflictRef, $scopeTeamsById, $potId);
                        continue;
                    }

                    if ($existingNormalizedName !== '' && (($reservedNames[$existingNormalizedName] ?? null) === $teamId)) {
                        unset($reservedNames[$existingNormalizedName]);
                    }

                    if ($normalizedTeamName !== '') {
                        $reservedNames[$normalizedTeamName] = $teamId;
                    }

                    $teamUpdateOps[$teamId] = array_merge($teamUpdateOps[$teamId] ?? [], [
                        'name' => $teamName,
                    ]);
                    $scopeTeamsById[$teamId]['name'] = $teamName;
                }

                continue;
            }

            if ($teamName === '' && $memberText === '' && ! $hasScoreValue) {
                continue;
            }

            $scoreRowsByTeamKey[$teamKey] = $parsedScoreRows;
            if (array_key_exists($teamKey, $teamMembersText)) {
                $teamMemberPayload[$teamKey] = $memberText;
            }

            if ($teamName !== '' && $normalizedTeamName !== '') {
                $conflictRef = $reservedNames[$normalizedTeamName] ?? null;
                if ($conflictRef !== null) {
                    if (is_int($conflictRef) || ctype_digit((string) $conflictRef)) {
                        $conflictTeamId = (int) $conflictRef;
                        $conflictTeam = $scopeTeamsById[$conflictTeamId] ?? null;

                        if ($conflictTeam !== null && ($conflictTeam['pot_id'] === null || (int) $conflictTeam['pot_id'] === 0)) {
                            if (isset($attachedTempTargets[$conflictTeamId])) {
                                $errors[] = 'Nama team "' . $teamName . '" sudah dipakai oleh row baru lain. Gunakan satu row saja untuk team ini.';
                                continue;
                            }

                            $attachedTempTargets[$conflictTeamId] = $teamKey;
                            $resolvedTeamIds[$teamKey] = $conflictTeamId;
                            $resolvedTempTeamKeys[] = $teamKey;
                            $teamUpdateOps[$conflictTeamId] = array_merge($teamUpdateOps[$conflictTeamId] ?? [], [
                                'tournament_id' => $pot['tournament_id'],
                                'pot_id'     => $potId,
                                'sort_order' => $nextSortOrder,
                            ]);
                            $scopeTeamsById[$conflictTeamId]['pot_id'] = $potId;
                            $scopeTeamsById[$conflictTeamId]['pot_name'] = (string) ($pot['name'] ?? '');
                            $nextSortOrder++;
                            continue;
                        }
                    }

                    $errors[] = $this->duplicateNameMessage($teamName, $conflictRef, $scopeTeamsById, $potId);
                    continue;
                }

                $reservedNames[$normalizedTeamName] = 'temp:' . $teamKey;
                $teamCreateOps[$teamKey] = [
                    'name'       => $teamName,
                    'sort_order' => $nextSortOrder,
                ];
                $nextSortOrder++;
                continue;
            }

            [$generatedTeamName, $generatedSeed] = $this->nextGeneratedTeamName($reservedNames, $nextSortOrder);
            $reservedNames[$this->normalizeTeamName($generatedTeamName)] = 'temp:' . $teamKey;
            $teamCreateOps[$teamKey] = [
                'name'       => $generatedTeamName,
                'sort_order' => $generatedSeed,
            ];
            $nextSortOrder = $generatedSeed + 1;
        }

        if ($errors !== []) {
            return $this->saveBulkError(implode(' ', array_unique($errors)), $isAjax, true);
        }

        $db      = db_connect();
        $builder = $db->table('scores');

        $db->transStart();

        foreach ($teamUpdateOps as $teamId => $teamUpdate) {
            if ($teamUpdate !== []) {
                $this->teamModel->update((int) $teamId, $teamUpdate);
            }
        }

        foreach ($teamCreateOps as $teamKey => $teamCreate) {
            $createdTeamId = (int) $this->teamModel->insert([
                'tournament_id' => (int) ($pot['tournament_id'] ?? 0),
                'pot_id'     => $potId,
                'name'       => (string) $teamCreate['name'],
                'sort_order' => (int) $teamCreate['sort_order'],
            ], true);

            $resolvedTeamIds[(string) $teamKey] = $createdTeamId;
            $resolvedTempTeamKeys[] = (string) $teamKey;
        }

        foreach ($teamMemberPayload as $teamKey => $memberText) {
            $resolvedTeamId = $resolvedTeamIds[(string) $teamKey] ?? null;
            if (! $resolvedTeamId) {
                continue;
            }

            $normalizedMemberText = str_replace([',', ';'], "\n", (string) $memberText);
            $lines = preg_split('/\r\n|\r|\n/', $normalizedMemberText) ?: [];
            $members = [];
            foreach ($lines as $line) {
                $name = trim($line);
                if ($name !== '') {
                    $members[] = $name;
                }
            }

            $this->teamMemberModel->where('team_id', (int) $resolvedTeamId)->delete();
            foreach ($members as $memberName) {
                $this->teamMemberModel->insert([
                    'team_id'     => (int) $resolvedTeamId,
                    'player_name' => $memberName,
                    'player_role' => null,
                ]);
            }
        }

        $builder->where('pot_id', $potId)
            ->where('game_no >', $gameCount)
            ->delete();

        $builder->where('pot_id', $potId)
            ->where('game_no <=', $gameCount)
            ->delete();

        $payload = [];
        foreach ($scoreRowsByTeamKey as $teamKey => $scoreRows) {
            $resolvedTeamId = $resolvedTeamIds[(string) $teamKey] ?? null;
            if (! $resolvedTeamId) {
                continue;
            }

            foreach ($scoreRows as $scoreRow) {
                $payload[] = [
                    'pot_id'          => $potId,
                    'team_id'         => (int) $resolvedTeamId,
                    'game_no'         => (int) $scoreRow['game_no'],
                    'rank_no'         => (int) $scoreRow['rank_no'],
                    'kill_point'      => (int) $scoreRow['kill_point'],
                    'placement_point' => (int) $scoreRow['placement_point'],
                    'total_point'     => (int) $scoreRow['total_point'],
                ];
            }
        }

        if ($payload !== []) {
            $this->scoreModel->insertBatch($payload);
        }

        $db->transComplete();

        if (! $db->transStatus()) {
            return $this->saveBulkError('Terjadi masalah saat menyimpan score.', $isAjax);
        }

        if ($isAjax) {
            $reloadPage = $resolvedTempTeamKeys !== [];
            return $this->response->setJSON([
                'status'        => 'success',
                'message'       => $reloadPage
                    ? 'Team tersimpan. Halaman akan dimuat ulang supaya row baru sinkron dengan database.'
                    : 'Perubahan berhasil disimpan.',
                'reloadPage'    => $reloadPage,
                'csrfTokenName' => csrf_token(),
                'csrfHash'      => csrf_hash(),
            ]);
        }

        return redirect()->back()->with('success', 'Kalkulator score berhasil disimpan.');
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

        $pot  = $this->potWithTournament($potId);
        $team = $this->teamModel->find($teamId);

        if ($pot === null || $team === null || (int) $team['pot_id'] !== $potId) {
            return redirect()->back()->with('error', 'Relasi pot dan team tidak valid.');
        }

        if (! $this->isEditableStatus((string) ($pot['tournament_status'] ?? TournamentModel::STATUS_BELUM_MULAI))) {
            return redirect()->back()->with('error', 'Tournament sudah finished. Score tidak bisa diubah.');
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
        return $this->placementMap()[$rankNo] ?? 0;
    }

    private function placementMap(): array
    {
        return [
            1  => 12,
            2  => 9,
            3  => 8,
            4  => 7,
            5  => 6,
            6  => 5,
            7  => 4,
            8  => 3,
            9  => 2,
            10 => 1,
            11 => 0,
            12 => 0,
        ];
    }

    private function potWithTournament(int $potId): ?array
    {
        return $this->potModel
            ->select('pots.*, tournaments.name AS tournament_name, tournaments.id AS tournament_id, tournaments.status AS tournament_status')
            ->join('tournaments', 'tournaments.id = pots.tournament_id')
            ->where('pots.id', $potId)
            ->first();
    }

    private function isEditableStatus(string $status): bool
    {
        return $status !== TournamentModel::STATUS_SELESAI;
    }

    private function saveBulkError(string $message, bool $isAjax, bool $withInput = false)
    {
        if ($isAjax) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'status'        => 'error',
                    'message'       => $message,
                    'csrfTokenName' => csrf_token(),
                    'csrfHash'      => csrf_hash(),
                ]);
        }

        $redirect = redirect()->back()->with('error', $message);

        return $withInput ? $redirect->withInput() : $redirect;
    }

    private function normalizeTeamName(string $value): string
    {
        $value = strtolower(trim($value));

        return preg_replace('/[^a-z0-9]+/', '', $value) ?? '';
    }

    private function duplicateScopeTeams(int $tournamentId): array
    {
        $builder = $this->teamModel
            ->select('teams.id, teams.pot_id, teams.name, teams.sort_order, pots.name AS pot_name, pots.tournament_id')
            ->join('pots', 'pots.id = teams.pot_id', 'left');

        if ($tournamentId > 0) {
            $builder->groupStart()
                ->where('pots.tournament_id', $tournamentId)
                ->orWhere('teams.pot_id IS NULL', null, false)
                ->groupEnd();
        }

        return $builder
            ->orderBy('teams.sort_order', 'ASC')
            ->orderBy('teams.name', 'ASC')
            ->findAll();
    }

    private function duplicateNameMessage(string $teamName, $conflictRef, array $scopeTeamsById, int $currentPotId): string
    {
        $safeTeamName = trim($teamName) !== '' ? trim($teamName) : 'Team ini';

        if (! is_int($conflictRef) && ! ctype_digit((string) $conflictRef)) {
            return 'Nama team "' . $safeTeamName . '" dipakai lebih dari satu row baru. Gunakan satu row saja supaya tidak duplikat.';
        }

        $conflictTeam = $scopeTeamsById[(int) $conflictRef] ?? null;
        if ($conflictTeam === null) {
            return 'Nama team "' . $safeTeamName . '" sudah ada di database. Gunakan team yang sudah ada supaya tidak duplikat.';
        }

        if ($conflictTeam['pot_id'] === null || (int) $conflictTeam['pot_id'] === 0) {
            return 'Nama team "' . $safeTeamName . '" sudah ada di database tanpa pot. Gunakan team yang sudah ada, jangan buat duplikat baru.';
        }

        if ((int) $conflictTeam['pot_id'] === $currentPotId) {
            return 'Nama team "' . $safeTeamName . '" sudah ada di pot ini. Gunakan row team yang sudah ada.';
        }

        $potName = trim((string) ($conflictTeam['pot_name'] ?? ''));
        if ($potName !== '') {
            return 'Nama team "' . $safeTeamName . '" sudah ada di ' . $potName . '. Pindahkan team yang ada lewat Team Manager agar tidak duplikat.';
        }

        return 'Nama team "' . $safeTeamName . '" sudah ada di pot lain. Pindahkan team yang ada lewat Team Manager agar tidak duplikat.';
    }

    private function nextGeneratedTeamName(array $reservedNames, int $seed): array
    {
        $candidate = max(1, $seed);

        while (true) {
            $generatedName = 'Team ' . $candidate;
            $normalized = $this->normalizeTeamName($generatedName);

            if ($normalized !== '' && ! isset($reservedNames[$normalized])) {
                return [$generatedName, $candidate];
            }

            $candidate++;
        }
    }
}
