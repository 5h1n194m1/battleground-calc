<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AllowTeamsWithoutPot extends Migration
{
    public function up()
    {
        $this->db->query('ALTER TABLE `teams` DROP FOREIGN KEY `fk_teams_pot_id`');
        $this->db->query('ALTER TABLE `teams` MODIFY `pot_id` INT UNSIGNED NULL');
        $this->db->query('ALTER TABLE `teams` ADD CONSTRAINT `fk_teams_pot_id` FOREIGN KEY (`pot_id`) REFERENCES `pots` (`id`) ON DELETE SET NULL ON UPDATE CASCADE');
    }

    public function down()
    {
        $firstPot = $this->db->table('pots')->select('id')->orderBy('id', 'ASC')->get(1)->getRowArray();
        if ($firstPot !== null) {
            $this->db->table('teams')->where('pot_id IS NULL', null, false)->update(['pot_id' => (int) $firstPot['id']]);
        }

        $this->db->query('ALTER TABLE `teams` DROP FOREIGN KEY `fk_teams_pot_id`');
        $this->db->query('ALTER TABLE `teams` MODIFY `pot_id` INT UNSIGNED NOT NULL');
        $this->db->query('ALTER TABLE `teams` ADD CONSTRAINT `fk_teams_pot_id` FOREIGN KEY (`pot_id`) REFERENCES `pots` (`id`) ON DELETE CASCADE ON UPDATE CASCADE');
    }
}
