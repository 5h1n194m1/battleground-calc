<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');

        $this->db->table('tournaments')->insert([
            'name'       => 'Battleground Community Cup',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $tournamentId = (int) $this->db->insertID();

        $this->db->table('pots')->insert([
            'tournament_id' => $tournamentId,
            'name'          => 'Pot A',
            'sort_order'    => 1,
            'created_at'    => $now,
            'updated_at'    => $now,
        ]);

        $potId = (int) $this->db->insertID();

        $teams = [
            ['name' => 'Alpha Wolves', 'sort_order' => 1],
            ['name' => 'Bravo Hawks', 'sort_order' => 2],
            ['name' => 'Charlie Vipers', 'sort_order' => 3],
        ];

        $teamIds = [];

        foreach ($teams as $team) {
            $this->db->table('teams')->insert([
                'pot_id'     => $potId,
                'name'       => $team['name'],
                'sort_order' => $team['sort_order'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $teamIds[] = (int) $this->db->insertID();
        }

        $scores = [
            ['team_id' => $teamIds[0], 'game_no' => 1, 'rank_no' => 1, 'kill_point' => 6, 'placement_point' => 12, 'total_point' => 18],
            ['team_id' => $teamIds[1], 'game_no' => 1, 'rank_no' => 3, 'kill_point' => 4, 'placement_point' => 8, 'total_point' => 12],
            ['team_id' => $teamIds[2], 'game_no' => 1, 'rank_no' => 5, 'kill_point' => 2, 'placement_point' => 6, 'total_point' => 8],
            ['team_id' => $teamIds[0], 'game_no' => 2, 'rank_no' => 4, 'kill_point' => 5, 'placement_point' => 7, 'total_point' => 12],
            ['team_id' => $teamIds[1], 'game_no' => 2, 'rank_no' => 2, 'kill_point' => 3, 'placement_point' => 9, 'total_point' => 12],
            ['team_id' => $teamIds[2], 'game_no' => 2, 'rank_no' => 7, 'kill_point' => 1, 'placement_point' => 4, 'total_point' => 5],
        ];

        foreach ($scores as $score) {
            $this->db->table('scores')->insert([
                'pot_id'          => $potId,
                'team_id'         => $score['team_id'],
                'game_no'         => $score['game_no'],
                'rank_no'         => $score['rank_no'],
                'kill_point'      => $score['kill_point'],
                'placement_point' => $score['placement_point'],
                'total_point'     => $score['total_point'],
                'created_at'      => $now,
                'updated_at'      => $now,
            ]);
        }
    }
}
