<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePotsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'tournament_id' => [
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
        $this->forge->addKey('tournament_id');
        $this->forge->addForeignKey('tournament_id', 'tournaments', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('pots', true);
    }

    public function down()
    {
        $this->forge->dropTable('pots', true);
    }
}
