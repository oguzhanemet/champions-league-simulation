<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\Fixture;
use App\Models\Standing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SimulationFeatureTest extends TestCase
{
    use RefreshDatabase;
    public function test_can_add_a_new_team()
    {
        $response = $this->postJson('/api/simulation/add-team', [
            'name' => 'Real Madrid'
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('teams', ['name' => 'Real Madrid']);
        $this->assertDatabaseHas('standings', ['played' => 0]); 
    }

    public function test_can_generate_fixtures()
    {
        Team::factory()->count(4)->create();

        $response = $this->postJson('/api/simulation/generate-fixtures');

        $response->assertStatus(200);
        
        $this->assertDatabaseCount('fixtures', 12); 
    }

    public function test_can_play_all_matches()
    {
        $this->postJson('/api/simulation/reset');
        Team::factory()->count(4)->create();
        $this->postJson('/api/simulation/generate-fixtures');

        $response = $this->postJson('/api/simulation/play-all');

        $response->assertStatus(200);
        
        $unplayedMatches = Fixture::where('is_played', false)->count();
        $this->assertEquals(0, $unplayedMatches);
    }
}