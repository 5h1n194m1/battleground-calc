<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddReferenceImagesToPotsTable extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('reference_image_1', 'pots')) {
            $this->forge->addColumn('pots', [
                'reference_image_1' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                    'after'      => 'sort_order',
                ],
            ]);
        }

        if (! $this->db->fieldExists('reference_image_2', 'pots')) {
            $this->forge->addColumn('pots', [
                'reference_image_2' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                    'after'      => 'reference_image_1',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('reference_image_2', 'pots')) {
            $this->forge->dropColumn('pots', 'reference_image_2');
        }

        if ($this->db->fieldExists('reference_image_1', 'pots')) {
            $this->forge->dropColumn('pots', 'reference_image_1');
        }
    }
}
