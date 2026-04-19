<?php

namespace App\Controllers;

use App\Models\PlayerModel;
use App\Models\RegistrationModel;
use RuntimeException;

class ImportController extends BaseController
{
    private RegistrationModel $registrationModel;
    private PlayerModel $playerModel;

    public function __construct()
    {
        $this->registrationModel = new RegistrationModel();
        $this->playerModel       = new PlayerModel();
    }

    public function registrations(): string
    {
        return view('imports/registrations', [
            'pageTitle'           => 'Import Registrations',
            'previewRows'         => [],
            'payload'             => null,
            'recentRegistrations' => $this->recentRegistrations(),
            'expectedHeaders'     => $this->expectedHeaders(),
        ]);
    }

    public function storeRegistrations()
    {
        $action = (string) $this->request->getPost('action');

        if ($action === 'preview') {
            return $this->previewRegistrations();
        }

        if ($action === 'import') {
            return $this->importRegistrations();
        }

        return redirect()->back()->with('error', 'Aksi import tidak dikenali.');
    }

    private function previewRegistrations()
    {
        if (! $this->validate([
            'registration_file' => [
                'label' => 'File registrasi',
                'rules' => 'uploaded[registration_file]|ext_in[registration_file,csv,txt]|max_size[registration_file,2048]',
            ],
        ])) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'File import belum valid.')
                ->with('validation', $this->validator->getErrors());
        }

        $file = $this->request->getFile('registration_file');

        try {
            $rows = $this->parseCsvFile($file->getTempName());
        } catch (RuntimeException $exception) {
            return redirect()->back()->with('error', $exception->getMessage());
        }

        if ($rows === []) {
            return redirect()->back()->with('error', 'Tidak ada baris registrasi yang bisa dipreview.');
        }

        return view('imports/registrations', [
            'pageTitle'           => 'Import Registrations',
            'previewRows'         => $rows,
            'payload'             => base64_encode(json_encode($rows, JSON_THROW_ON_ERROR)),
            'recentRegistrations' => $this->recentRegistrations(),
            'expectedHeaders'     => $this->expectedHeaders(),
        ]);
    }

    private function importRegistrations()
    {
        $payload = (string) $this->request->getPost('payload');

        if ($payload === '') {
            return redirect()->back()->with('error', 'Payload preview tidak ditemukan.');
        }

        try {
            $rows = json_decode(base64_decode($payload, true) ?: '', true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return redirect()->back()->with('error', 'Payload import tidak valid.');
        }

        if (! is_array($rows) || $rows === []) {
            return redirect()->back()->with('error', 'Tidak ada data untuk diimport.');
        }

        $db = db_connect();
        $db->transStart();

        foreach ($rows as $row) {
            $registrationId = $this->registrationModel->insert([
                'team_name'   => $row['team_name'],
                'leader_name' => $row['leader_name'],
                'whatsapp'    => $row['whatsapp'],
                'email'       => $row['email'],
                'notes'       => $row['notes'] ?: null,
            ], true);

            foreach ($row['players'] as $player) {
                $this->playerModel->insert([
                    'registration_id' => $registrationId,
                    'player_name'     => $player['player_name'],
                    'player_role'     => $player['player_role'] ?: null,
                ]);
            }
        }

        $db->transComplete();

        if (! $db->transStatus()) {
            return redirect()->back()->with('error', 'Import registrasi gagal disimpan ke database.');
        }

        return redirect()->to(site_url('imports/registrations'))->with('success', count($rows) . ' registrasi berhasil diimport.');
    }

    private function parseCsvFile(string $path): array
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException('File CSV tidak dapat dibaca.');
        }

        $headers = fgetcsv($handle);

        if ($headers === false) {
            fclose($handle);
            throw new RuntimeException('Header CSV tidak ditemukan.');
        }

        $headers = array_map(static fn ($header) => strtolower(trim((string) $header)), $headers);
        $rows    = [];

        while (($columns = fgetcsv($handle)) !== false) {
            if ($columns === [null] || $columns === []) {
                continue;
            }

            $data = [];
            foreach ($headers as $index => $header) {
                $data[$header] = trim((string) ($columns[$index] ?? ''));
            }

            if (($data['team_name'] ?? '') === '') {
                continue;
            }

            $rows[] = [
                'team_name'   => $data['team_name'] ?? '',
                'leader_name' => $data['leader_name'] ?? '',
                'whatsapp'    => $data['whatsapp'] ?? '',
                'email'       => $data['email'] ?? '',
                'notes'       => $data['notes'] ?? '',
                'players'     => $this->extractPlayers($data),
            ];
        }

        fclose($handle);

        return $rows;
    }

    private function extractPlayers(array $data): array
    {
        $players = [];

        for ($index = 1; $index <= 4; $index++) {
            $playerName = trim((string) ($data['player_' . $index . '_name'] ?? ($data['player_' . $index] ?? '')));
            $playerRole = trim((string) ($data['player_' . $index . '_role'] ?? ''));

            if ($playerName === '') {
                continue;
            }

            $players[] = [
                'player_name' => $playerName,
                'player_role' => $playerRole,
            ];
        }

        return $players;
    }

    private function recentRegistrations(): array
    {
        return $this->registrationModel
            ->orderBy('created_at', 'DESC')
            ->findAll(10);
    }

    private function expectedHeaders(): array
    {
        return [
            'team_name',
            'leader_name',
            'whatsapp',
            'email',
            'notes',
            'player_1_name',
            'player_1_role',
            'player_2_name',
            'player_2_role',
            'player_3_name',
            'player_3_role',
            'player_4_name',
            'player_4_role',
        ];
    }
}
