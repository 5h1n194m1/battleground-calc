<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTeamMembersTable extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('team_members')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'team_id' => [
                'type'     => 'INT',
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
        $this->forge->addKey('team_id');
        $this->forge->addForeignKey('team_id', 'teams', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('team_members', true);
    }

    public function down()
    {
        $this->forge->dropTable('team_members', true);
    }
}
