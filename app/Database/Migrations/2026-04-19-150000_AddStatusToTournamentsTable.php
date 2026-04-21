<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddStatusToTournamentsTable extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('status', 'tournaments')) {
            $this->forge->addColumn('tournaments', [
                'status' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'default'    => 'belum_mulai',
                    'after'      => 'name',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('status', 'tournaments')) {
            $this->forge->dropColumn('tournaments', 'status');
        }
    }
}
