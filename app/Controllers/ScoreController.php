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
        if (is_array($teamNames)) {
            $teamsForRename = $this->teamModel->where('pot_id', $potId)->findAll();
            $teamMap = [];
            foreach ($teamsForRename as $team) {
                $teamMap[(int) $team['id']] = $team;
            }

            foreach ($teamNames as $teamId => $teamName) {
                $teamId = (int) $teamId;
                $teamName = trim((string) $teamName);

                if ($teamName === '' || ! isset($teamMap[$teamId])) {
                    continue;
                }

                $this->teamModel->update($teamId, ['name' => $teamName]);
            }
        }

        if (is_array($teamMembersText)) {
            $teamsForMembers = $this->teamModel->where('pot_id', $potId)->findAll();
            $teamIdsForMembers = array_map(static fn (array $team): int => (int) $team['id'], $teamsForMembers);
            $teamIdMap = array_fill_keys($teamIdsForMembers, true);

            foreach ($teamMembersText as $teamId => $memberText) {
                $teamId = (int) $teamId;

                if (! isset($teamIdMap[$teamId])) {
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
        }

        $gameCount = max(1, (int) ($this->request->getPost('game_count') ?? 1));
        $rawScores = $this->request->getPost('scores');

        if (! is_array($rawScores)) {
            return $this->saveBulkError('Data score tidak ditemukan.', $isAjax);
        }

        $teams = $this->teamModel
            ->where('pot_id', $potId)
            ->findAll();

        $teamIds = array_map(static fn (array $team): int => (int) $team['id'], $teams);
        $teamMap = array_fill_keys($teamIds, true);

        $errors  = [];
        $payload = [];

        foreach ($rawScores as $teamId => $teamScores) {
            $teamId = (int) $teamId;

            if (! isset($teamMap[$teamId]) || ! is_array($teamScores)) {
                continue;
            }

            for ($gameNo = 1; $gameNo <= $gameCount; $gameNo++) {
                $gameInput = $teamScores[$gameNo] ?? [];
                $rankRaw   = trim((string) ($gameInput['rank_no'] ?? ''));
                $killRaw   = trim((string) ($gameInput['kill_point'] ?? ''));

                if ($rankRaw === '' && $killRaw === '') {
                    continue;
                }

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

                $payload[] = [
                    'pot_id'          => $potId,
                    'team_id'         => $teamId,
                    'game_no'         => $gameNo,
                    'rank_no'         => $rankNo,
                    'kill_point'      => $killPoint,
                    'placement_point' => $placementPoint,
                    'total_point'     => $placementPoint + $killPoint,
                ];
            }
        }

        if ($errors !== []) {
            return $this->saveBulkError(implode(' ', array_unique($errors)), $isAjax, true);
        }

        $db      = db_connect();
        $builder = $db->table('scores');

        $db->transStart();

        $builder->where('pot_id', $potId)
            ->where('game_no >', $gameCount)
            ->delete();

        $builder->where('pot_id', $potId)
            ->where('game_no <=', $gameCount)
            ->delete();

        if ($payload !== []) {
            $this->scoreModel->insertBatch($payload);
        }

        $db->transComplete();

        if (! $db->transStatus()) {
            return $this->saveBulkError('Terjadi masalah saat menyimpan score.', $isAjax);
        }

        if ($isAjax) {
            return $this->response->setJSON([
                'status'        => 'success',
                'message'       => 'Perubahan berhasil disimpan.',
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
}
