<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemoveRegistrationArtifacts extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('team_members') && $this->db->fieldExists('registration_id', 'team_members')) {
            $constraints = $this->db->query("
                SELECT CONSTRAINT_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'team_members'
                  AND COLUMN_NAME = 'registration_id'
                  AND REFERENCED_TABLE_NAME IS NOT NULL
            ")->getResultArray();

            foreach ($constraints as $constraint) {
                $name = trim((string) ($constraint['CONSTRAINT_NAME'] ?? ''));
                if ($name !== '') {
                    $this->db->query('ALTER TABLE `team_members` DROP FOREIGN KEY `' . $name . '`');
                }
            }

            $indexes = $this->db->query("
                SELECT INDEX_NAME
                FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'team_members'
                  AND COLUMN_NAME = 'registration_id'
                  AND INDEX_NAME != 'PRIMARY'
            ")->getResultArray();

            foreach ($indexes as $index) {
                $name = trim((string) ($index['INDEX_NAME'] ?? ''));
                if ($name !== '') {
                    $this->db->query('ALTER TABLE `team_members` DROP INDEX `' . $name . '`');
                }
            }

            $this->forge->dropColumn('team_members', 'registration_id');
        }

        if ($this->db->tableExists('players')) {
            $this->forge->dropTable('players', true);
        }

        if ($this->db->tableExists('registrations')) {
            $this->forge->dropTable('registrations', true);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('registrations') || $this->db->tableExists('players')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'team_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'leader_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'whatsapp' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'email' => [
                'type'       => 'VARCHAR',
                'constraint' => 190,
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('registrations', true);

        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'registration_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'player_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'player_role' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('registration_id');
        $this->forge->addForeignKey('registration_id', 'registrations', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('players', true);

        if ($this->db->tableExists('team_members') && ! $this->db->fieldExists('registration_id', 'team_members')) {
            $fields = [
                'registration_id' => [
                    'type'     => 'BIGINT',
                    'unsigned' => true,
                    'null'     => true,
                    'after'    => 'team_id',
                ],
            ];
            $this->forge->addColumn('team_members', $fields);
            $this->db->query('ALTER TABLE `team_members` ADD KEY `team_members_registration_id_index` (`registration_id`)');
            $this->db->query('ALTER TABLE `team_members` ADD CONSTRAINT `team_members_registration_id_foreign` FOREIGN KEY (`registration_id`) REFERENCES `registrations` (`id`) ON DELETE SET NULL ON UPDATE CASCADE');
        }
    }
}
