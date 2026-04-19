<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateScoresTable extends Migration
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
            'team_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'game_no' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'rank_no' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'kill_point' => [
                'type'     => 'INT',
                'unsigned' => true,
                'default'  => 0,
            ],
            'placement_point' => [
                'type'     => 'INT',
                'unsigned' => true,
                'default'  => 0,
            ],
            'total_point' => [
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
        $this->forge->addKey('team_id');
        $this->forge->addUniqueKey(['pot_id', 'team_id', 'game_no'], 'scores_pot_team_game_unique');
        $this->forge->addForeignKey('pot_id', 'pots', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('team_id', 'teams', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('scores', true);
    }

    public function down()
    {
        $this->forge->dropTable('scores', true);
    }
}
