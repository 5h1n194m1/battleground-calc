<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTeamsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'pot_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'sort_order' => [
                'type'     => 'INT',
                'unsigned' => true,
                'default'  => 0,
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
        $this->forge->addKey('pot_id');
        $this->forge->addForeignKey('pot_id', 'pots', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('teams', true);
    }

    public function down()
    {
        $this->forge->dropTable('teams', true);
    }
}
