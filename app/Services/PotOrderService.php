<?php

namespace App\Services;

use App\Models\PotModel;

class PotOrderService
{
    public function __construct(private readonly ?PotModel $potModel = null)
    {
    }

    public function normalizeTournament(int $tournamentId): void
    {
        $potModel = $this->potModel ?? new PotModel();
        $pots = $potModel
            ->where('tournament_id', $tournamentId)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();

        foreach ($pots as $index => $pot) {
            $order = $index + 1;
            if ((int) ($pot['sort_order'] ?? 0) !== $order) {
                $potModel->update((int) $pot['id'], ['sort_order' => $order]);
            }
        }
    }

    public function movePot(int $tournamentId, int $potId, ?int $desiredOrder = null): void
    {
        $potModel = $this->potModel ?? new PotModel();
        $pots = $potModel
            ->where('tournament_id', $tournamentId)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();

        if ($pots === []) {
            return;
        }

        $ids = array_map(static fn (array $pot): int => (int) $pot['id'], $pots);
        $ids = array_values(array_filter($ids, static fn (int $id): bool => $id !== $potId));

        $targetIndex = $desiredOrder === null ? count($ids) : max(0, min(count($ids), $desiredOrder - 1));
        array_splice($ids, $targetIndex, 0, [$potId]);

        foreach ($ids as $index => $id) {
            $potModel->update($id, ['sort_order' => $index + 1]);
        }
    }
}
