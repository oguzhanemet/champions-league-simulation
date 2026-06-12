<?php

namespace Database\Seeders;

use App\Models\Team;
use Illuminate\Database\Seeder;

class TeamSeeder extends Seeder
{
    public function run(): void
    {
        $teams = [
            // Güçlü hücum, sağlam savunma
            ['name' => 'Manchester City', 'attack_strength' => 95, 'defense_strength' => 90], 
            // Dengeli ve dinamik
            ['name' => 'Liverpool', 'attack_strength' => 90, 'defense_strength' => 88],
            // Katı savunma, kontra atak
            ['name' => 'Chelsea', 'attack_strength' => 82, 'defense_strength' => 85],
            // Ofansif ama savunmada bazen kırılgan
            ['name' => 'Arsenal', 'attack_strength' => 85, 'defense_strength' => 80],
        ];

        foreach ($teams as $teamData) {
            $team = Team::create($teamData);
            
            // Takım oluştuğunda puan durumunu da sıfır olarak başlatalım
            $team->standing()->create();
        }
    }
}
