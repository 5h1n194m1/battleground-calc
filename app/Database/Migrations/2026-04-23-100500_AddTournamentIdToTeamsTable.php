<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTournamentIdToTeamsTable extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('tournament_id', 'teams')) {
            $this->forge->addColumn('teams', [
                'tournament_id' => [
                    'type'     => 'INT',
                    'unsigned' => true,
                    'null'     => true,
                    'after'    => 'id',
                ],
            ]);
        }

        $this->db->query('UPDATE teams SET tournament_id = (
            SELECT pots.tournament_id FROM pots WHERE pots.id = teams.pot_id
        ) WHERE pot_id IS NOT NULL AND (tournament_id IS NULL OR tournament_id = 0)');

        $indexExists = $this->db->query("
            SELECT COUNT(1) AS total
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'teams'
              AND INDEX_NAME = 'teams_tournament_id_index'
        ")->getRowArray();

        if ((int) ($indexExists['total'] ?? 0) === 0) {
            $this->db->query('CREATE INDEX teams_tournament_id_index ON teams (tournament_id)');
        }

        $fkExists = $this->db->query("
            SELECT COUNT(1) AS total
            FROM information_schema.REFERENTIAL_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE()
              AND CONSTRAINT_NAME = 'teams_tournament_id_foreign'
              AND TABLE_NAME = 'teams'
        ")->getRowArray();

        if ((int) ($fkExists['total'] ?? 0) === 0) {
            $this->db->query('ALTER TABLE teams ADD CONSTRAINT teams_tournament_id_foreign FOREIGN KEY (tournament_id) REFERENCES tournaments(id) ON DELETE SET NULL ON UPDATE CASCADE');
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('tournament_id', 'teams')) {
            $fkExists = $this->db->query("
                SELECT COUNT(1) AS total
                FROM information_schema.REFERENTIAL_CONSTRAINTS
                WHERE CONSTRAINT_SCHEMA = DATABASE()
                  AND CONSTRAINT_NAME = 'teams_tournament_id_foreign'
                  AND TABLE_NAME = 'teams'
            ")->getRowArray();

            if ((int) ($fkExists['total'] ?? 0) > 0) {
                $this->db->query('ALTER TABLE teams DROP FOREIGN KEY teams_tournament_id_foreign');
            }

            $indexExists = $this->db->query("
                SELECT COUNT(1) AS total
                FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'teams'
                  AND INDEX_NAME = 'teams_tournament_id_index'
            ")->getRowArray();

            if ((int) ($indexExists['total'] ?? 0) > 0) {
                $this->db->query('DROP INDEX teams_tournament_id_index ON teams');
            }

            $this->forge->dropColumn('teams', 'tournament_id');
        }
    }
}
