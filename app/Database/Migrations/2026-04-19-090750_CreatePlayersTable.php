<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePlayersTable extends Migration
{
    public function up()
    {
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
    }

    public function down()
    {
        $this->forge->dropTable('players', true);
    }
}
