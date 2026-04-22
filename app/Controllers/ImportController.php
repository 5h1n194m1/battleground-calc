<?php

namespace App\Controllers;

use App\Models\PlayerModel;
use App\Models\PotModel;
use App\Models\RegistrationModel;
use App\Models\TeamMemberModel;
use App\Models\TeamModel;
use App\Models\TournamentModel;
use CodeIgniter\HTTP\Files\UploadedFile;
use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

class ImportController extends BaseController
{
    private const MAX_MEMBERS = 6;

    private RegistrationModel $registrationModel;
    private PlayerModel $playerModel;
    private TournamentModel $tournamentModel;
    private PotModel $potModel;
    private TeamModel $teamModel;
    private TeamMemberModel $teamMemberModel;

    public function __construct()
    {
        $this->registrationModel = new RegistrationModel();
        $this->playerModel       = new PlayerModel();
        $this->tournamentModel   = new TournamentModel();
        $this->potModel          = new PotModel();
        $this->teamModel         = new TeamModel();
        $this->teamMemberModel   = new TeamMemberModel();
    }

    public function registrations(): string
    {
        $tournaments = $this->tournamentModel->orderBy('created_at', 'DESC')->findAll();

        $selectedTournamentId = (int) ($this->request->getGet('tournament_id') ?? 0);
        if ($selectedTournamentId <= 0 && $tournaments !== []) {
            $selectedTournamentId = (int) $tournaments[0]['id'];
        }

        $pots = $selectedTournamentId > 0
            ? $this->potModel
                ->where('tournament_id', $selectedTournamentId)
                ->orderBy('sort_order', 'ASC')
                ->orderBy('name', 'ASC')
                ->findAll()
            : [];

        $selectedPotId = (int) ($this->request->getGet('pot_id') ?? 0);

        return view('imports/registrations', [
            'pageTitle'            => 'Import Registrations',
            'recentRegistrations'  => $this->recentRegistrations(),
            'tournaments'          => $tournaments,
            'pots'                 => $pots,
            'selectedTournamentId' => $selectedTournamentId,
            'selectedPotId'        => $selectedPotId,
        ]);
    }

    public function storeRegistrations()
    {
        if (! $this->validate([
            'registration_file' => [
                'label' => 'File registrasi',
                'rules' => 'uploaded[registration_file]|ext_in[registration_file,csv,txt,xlsx]|max_size[registration_file,8192]',
            ],
        ])) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'File import belum valid.')
                ->with('validation', $this->validator->getErrors());
        }

        $file = $this->request->getFile('registration_file');
        $importMode = (string) $this->request->getPost('import_mode');
        if (! in_array($importMode, ['global', 'with_pot'], true)) {
            $importMode = 'global';
        }

        $selectedTournamentId = (int) ($this->request->getPost('tournament_id') ?? 0);
        $selectedPotId = (int) ($this->request->getPost('pot_id') ?? 0);

        if ($importMode === 'with_pot') {
            if ($selectedTournamentId <= 0 || $selectedPotId <= 0) {
                return redirect()->back()->withInput()->with('error', 'Mode dengan pot wajib memilih tournament dan pot.');
            }

            $pot = $this->potModel->find($selectedPotId);
            if ($pot === null || (int) $pot['tournament_id'] !== $selectedTournamentId) {
                return redirect()->back()->withInput()->with('error', 'Pot tujuan import tidak valid.');
            }
        }

        try {
            $rows = $this->parseRegistrationFile($file);
        } catch (RuntimeException $exception) {
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        }

        if ($rows === []) {
            return redirect()->back()->withInput()->with('error', 'Tidak ada data registrasi yang bisa diimport.');
        }

        $db = db_connect();
        $db->transStart();

        $existingTeamsByPot = [];
        if ($importMode === 'with_pot' && $selectedPotId > 0) {
            $existingTeams = $this->teamModel
                ->where('pot_id', $selectedPotId)
                ->orderBy('sort_order', 'ASC')
                ->orderBy('name', 'ASC')
                ->findAll();

            foreach ($existingTeams as $team) {
                $existingTeamsByPot[$this->normalizeName((string) $team['name'])] = $team;
            }
        }

        foreach ($rows as $row) {
            $registrationId = (int) $this->registrationModel->insert([
                'team_name'   => $row['team_name'],
                'leader_name' => $row['leader_name'],
                'whatsapp'    => $row['whatsapp'],
                'email'       => $row['email'],
                'notes'       => $row['notes'] !== '' ? $row['notes'] : null,
            ], true);

            foreach ($row['players'] as $player) {
                $this->playerModel->insert([
                    'registration_id' => $registrationId,
                    'player_name'     => $player['player_name'],
                    'player_role'     => $player['player_role'] !== '' ? $player['player_role'] : null,
                ]);
            }

            if ($importMode === 'with_pot' && $selectedPotId > 0) {
                $teamId = $this->upsertTeamIntoPot($selectedPotId, $row, $existingTeamsByPot);
                $this->replaceTeamMembers($teamId, $row['players']);
            }
        }

        $db->transComplete();

        if (! $db->transStatus()) {
            return redirect()->back()->withInput()->with('error', 'Import registrasi gagal disimpan ke database.');
        }

        $query = array_filter([
            'tournament_id' => $selectedTournamentId > 0 ? $selectedTournamentId : null,
            'pot_id'        => $selectedPotId > 0 ? $selectedPotId : null,
        ], static fn ($value) => $value !== null);

        $targetUrl = site_url('imports/registrations');
        if ($query !== []) {
            $targetUrl .= '?' . http_build_query($query);
        }

        $modeLabel = $importMode === 'with_pot' ? ' ke registrasi + team pot' : ' ke registrasi';

        return redirect()->to($targetUrl)->with('success', count($rows) . ' data berhasil diimport' . $modeLabel . '.');
    }

    private function parseRegistrationFile(UploadedFile $file): array
    {
        $extension = strtolower($file->getExtension() ?: pathinfo($file->getName(), PATHINFO_EXTENSION));
        $path = $file->getTempName();

        if ($extension === 'xlsx') {
            return $this->parseXlsxFile($path);
        }

        if (in_array($extension, ['csv', 'txt'], true)) {
            return $this->parseDelimitedFile($path);
        }

        throw new RuntimeException('Format file belum didukung. Gunakan CSV, TXT, atau XLSX.');
    }

    private function parseDelimitedFile(string $path): array
    {
        $sample = file_get_contents($path, false, null, 0, 4096) ?: '';
        $delimiter = $this->detectDelimiter($sample);

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('File import tidak dapat dibaca.');
        }

        $headers = fgetcsv($handle, 0, $delimiter);
        if ($headers === false) {
            fclose($handle);
            throw new RuntimeException('Header file tidak ditemukan.');
        }

        $rows = [];
        while (($columns = fgetcsv($handle, 0, $delimiter)) !== false) {
            if ($columns === [null] || $columns === []) {
                continue;
            }
            $rows[] = $columns;
        }

        fclose($handle);

        return $this->normalizeImportedRows($headers, $rows);
    }

    private function parseXlsxFile(string $path): array
    {
        $rows = $this->readXlsxRows($path);
        if ($rows === []) {
            throw new RuntimeException('Sheet XLSX tidak berisi data yang bisa dibaca.');
        }

        $headers = array_shift($rows);
        if (! is_array($headers) || $headers === []) {
            throw new RuntimeException('Header XLSX tidak ditemukan.');
        }

        return $this->normalizeImportedRows($headers, $rows);
    }

    private function normalizeImportedRows(array $headers, array $rows): array
    {
        $normalizedHeaders = array_map(fn ($header) => $this->normalizeHeader((string) $header), $headers);

        if (in_array('team_name', $normalizedHeaders, true)) {
            return $this->buildRowsFromStandardHeaders($normalizedHeaders, $rows);
        }

        if (in_array('nama_team', $normalizedHeaders, true) || in_array('nick_captain', $normalizedHeaders, true)) {
            return $this->buildRowsFromFormResponseHeaders($normalizedHeaders, $rows);
        }

        throw new RuntimeException('Format file belum dikenali. Gunakan template header lama atau file XLSX respons form seperti contoh yang Anda lampirkan.');
    }

    private function buildRowsFromStandardHeaders(array $headers, array $rows): array
    {
        $map = [];
        foreach ($headers as $index => $header) {
            if ($header !== '') {
                $map[$header] = $index;
            }
        }

        $result = [];
        foreach ($rows as $row) {
            $teamName = $this->cellValue($row, $map['team_name'] ?? null);
            if ($teamName === '') {
                continue;
            }

            $players = [];
            for ($index = 1; $index <= self::MAX_MEMBERS; $index++) {
                $nameKey = 'player_' . $index . '_name';
                $fallbackKey = 'player_' . $index;
                $roleKey = 'player_' . $index . '_role';

                $playerName = $this->cellValue($row, $map[$nameKey] ?? $map[$fallbackKey] ?? null);
                $playerRole = $this->cellValue($row, $map[$roleKey] ?? null);

                if ($playerName === '') {
                    continue;
                }

                $players[] = [
                    'player_name' => $playerName,
                    'player_role' => $playerRole,
                ];
            }

            $result[] = [
                'team_name'   => $teamName,
                'leader_name' => $this->cellValue($row, $map['leader_name'] ?? null),
                'whatsapp'    => $this->cellValue($row, $map['whatsapp'] ?? null),
                'email'       => $this->cellValue($row, $map['email'] ?? null),
                'notes'       => $this->cellValue($row, $map['notes'] ?? null),
                'players'     => $players,
            ];
        }

        return $result;
    }

    private function buildRowsFromFormResponseHeaders(array $headers, array $rows): array
    {
        $teamIndex = $this->firstHeaderIndex($headers, ['nama_team']);
        $leaderIndex = $this->firstHeaderIndex($headers, ['nick_captain']);
        $phoneIndex = $this->firstHeaderIndex($headers, ['no_perwakilan', 'no_wa', 'whatsapp']);
        $emailIndex = $this->firstHeaderIndex($headers, ['email']);
        $notesIndex = $this->firstHeaderIndex($headers, ['bukti_pembayaran']);

        $playerIndexes = [];
        foreach ($headers as $index => $header) {
            if ($header === '') {
                continue;
            }

            if (str_contains($header, 'nick_player') || $header === 'nick_captain') {
                $playerIndexes[] = $index;
            }
        }

        $result = [];
        foreach ($rows as $row) {
            $teamName = $this->cellValue($row, $teamIndex);
            if ($teamName === '') {
                continue;
            }

            $players = [];
            $seen = [];
            foreach ($playerIndexes as $index) {
                $name = $this->cellValue($row, $index);
                if ($name === '' || $name === '-') {
                    continue;
                }

                $normalized = $this->normalizeName($name);
                if ($normalized === '' || isset($seen[$normalized])) {
                    continue;
                }

                $seen[$normalized] = true;
                $players[] = [
                    'player_name' => $name,
                    'player_role' => '',
                ];

                if (count($players) >= self::MAX_MEMBERS) {
                    break;
                }
            }

            $result[] = [
                'team_name'   => $teamName,
                'leader_name' => $this->cellValue($row, $leaderIndex),
                'whatsapp'    => $this->cellValue($row, $phoneIndex),
                'email'       => $this->cellValue($row, $emailIndex),
                'notes'       => $this->cellValue($row, $notesIndex),
                'players'     => $players,
            ];
        }

        return $result;
    }

    private function detectDelimiter(string $sample): string
    {
        $candidates = [',', ';', "\t"];
        $best = ',';
        $bestCount = 0;

        foreach ($candidates as $candidate) {
            $count = substr_count($sample, $candidate);
            if ($count > $bestCount) {
                $best = $candidate;
                $bestCount = $count;
            }
        }

        return $best;
    }

    private function readXlsxRows(string $path): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('File XLSX tidak bisa dibuka.');
        }

        $sharedStrings = [];
        $sharedStringXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedStringXml !== false) {
            $sharedStrings = $this->readSharedStrings($sharedStringXml);
        }

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheetXml === false) {
            $zip->close();
            throw new RuntimeException('Sheet pertama XLSX tidak ditemukan.');
        }

        $rows = $this->readSheetRows($sheetXml, $sharedStrings);
        $zip->close();

        return $rows;
    }

    private function readSharedStrings(string $xml): array
    {
        $document = simplexml_load_string($xml);
        if (! $document instanceof SimpleXMLElement) {
            return [];
        }

        $document->registerXPathNamespace('a', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $items = $document->xpath('//a:si') ?: [];

        $strings = [];
        foreach ($items as $item) {
            $parts = $item->xpath('.//a:t') ?: [];
            $value = '';
            foreach ($parts as $part) {
                $value .= (string) $part;
            }
            $strings[] = $value;
        }

        return $strings;
    }

    private function readSheetRows(string $xml, array $sharedStrings): array
    {
        $document = simplexml_load_string($xml);
        if (! $document instanceof SimpleXMLElement) {
            return [];
        }

        $document->registerXPathNamespace('a', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $rowNodes = $document->xpath('//a:sheetData/a:row') ?: [];

        $rows = [];
        foreach ($rowNodes as $rowNode) {
            $cells = [];
            $cellNodes = $rowNode->xpath('./a:c') ?: [];

            foreach ($cellNodes as $cellNode) {
                $ref = (string) ($cellNode['r'] ?? '');
                $type = (string) ($cellNode['t'] ?? '');
                $index = $this->columnIndexFromReference($ref);
                if ($index < 0) {
                    continue;
                }

                $value = '';
                if ($type === 's') {
                    $sharedIndex = (int) ($cellNode->v ?? 0);
                    $value = (string) ($sharedStrings[$sharedIndex] ?? '');
                } elseif ($type === 'inlineStr') {
                    $parts = $cellNode->xpath('./a:is/a:t') ?: [];
                    foreach ($parts as $part) {
                        $value .= (string) $part;
                    }
                } else {
                    $value = (string) ($cellNode->v ?? '');
                }

                $cells[$index] = trim($value);
            }

            if ($cells === []) {
                continue;
            }

            ksort($cells);
            $maxIndex = max(array_keys($cells));
            $row = [];
            for ($index = 0; $index <= $maxIndex; $index++) {
                $row[] = $cells[$index] ?? '';
            }

            if (implode('', $row) === '') {
                continue;
            }

            $rows[] = $row;
        }

        return $rows;
    }

    private function columnIndexFromReference(string $reference): int
    {
        if ($reference === '') {
            return -1;
        }

        if (! preg_match('/^([A-Z]+)/i', $reference, $matches)) {
            return -1;
        }

        $letters = strtoupper($matches[1]);
        $index = 0;
        $length = strlen($letters);

        for ($i = 0; $i < $length; $i++) {
            $index = ($index * 26) + (ord($letters[$i]) - 64);
        }

        return $index - 1;
    }

    private function firstHeaderIndex(array $headers, array $needles): ?int
    {
        foreach ($headers as $index => $header) {
            foreach ($needles as $needle) {
                if ($header === $needle || str_contains($header, $needle)) {
                    return $index;
                }
            }
        }

        return null;
    }

    private function cellValue(array $row, ?int $index): string
    {
        if ($index === null) {
            return '';
        }

        $value = trim((string) ($row[$index] ?? ''));
        return $value === 'NULL' ? '' : $value;
    }

    private function normalizeHeader(string $header): string
    {
        $header = strtolower(trim(str_replace(["\r", "\n", "\t"], ' ', $header)));
        $header = preg_replace('/[^a-z0-9]+/', '_', $header) ?? '';
        return trim($header, '_');
    }

    private function upsertTeamIntoPot(int $potId, array $row, array &$existingTeamsByPot): int
    {
        $normalizedName = $this->normalizeName($row['team_name']);
        $team = $existingTeamsByPot[$normalizedName] ?? null;

        if ($team !== null) {
            $teamId = (int) $team['id'];
            $this->teamModel->update($teamId, ['name' => $row['team_name']]);
            return $teamId;
        }

        $nextSortOrder = $this->teamModel->where('pot_id', $potId)->countAllResults() + 1;
        $teamId = (int) $this->teamModel->insert([
            'pot_id'     => $potId,
            'name'       => $row['team_name'],
            'sort_order' => $nextSortOrder,
        ], true);

        $existingTeamsByPot[$normalizedName] = [
            'id'        => $teamId,
            'pot_id'    => $potId,
            'name'      => $row['team_name'],
            'sort_order'=> $nextSortOrder,
        ];

        return $teamId;
    }

    private function replaceTeamMembers(int $teamId, array $players): void
    {
        $this->teamMemberModel->where('team_id', $teamId)->delete();

        $seen = [];
        foreach (array_slice($players, 0, self::MAX_MEMBERS) as $player) {
            $playerName = trim((string) ($player['player_name'] ?? ''));
            if ($playerName === '') {
                continue;
            }

            $normalized = $this->normalizeName($playerName);
            if ($normalized === '' || isset($seen[$normalized])) {
                continue;
            }

            $seen[$normalized] = true;
            $this->teamMemberModel->insert([
                'team_id'         => $teamId,
                'registration_id' => null,
                'player_name'     => $playerName,
                'player_role'     => ! empty($player['player_role']) ? (string) $player['player_role'] : null,
            ]);
        }
    }

    private function normalizeName(string $value): string
    {
        $value = strtolower(trim($value));
        return preg_replace('/[^a-z0-9]+/', '', $value) ?? '';
    }

    private function recentRegistrations(): array
    {
        return $this->registrationModel
            ->orderBy('created_at', 'DESC')
            ->findAll(10);
    }
}
