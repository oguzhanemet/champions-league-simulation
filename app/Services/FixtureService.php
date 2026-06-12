<?php

namespace App\Services;

use App\Models\Team;
use App\Models\Fixture;
use Illuminate\Support\Facades\DB;

class FixtureService
{
    /**
     * The league schedule is generated using the Round-Robin algorithm.
     */
    public function generate(): void
    {
        
        Fixture::truncate();
        
        DB::transaction(function () {
            $teams = Team::pluck('id')->toArray();
            shuffle($teams);
            $teamCount = count($teams);
            
            if ($teamCount % 2 !== 0) {
                array_push($teams, null);
                $teamCount++;
            }

            $totalRounds = $teamCount - 1;
            $matchesPerRound = $teamCount / 2;

            $fixtures = [];

            for ($round = 0; $round < $totalRounds; $round++) {
                for ($match = 0; $match < $matchesPerRound; $match++) {
                    $home = $teams[$match];
                    $away = $teams[$teamCount - 1 - $match];

                    if ($home !== null && $away !== null) {
                        $fixtures[] = [
                            'week' => $round + 1,
                            'home_team_id' => $home,
                            'away_team_id' => $away,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }
                
                $lastTeam = array_pop($teams);
                array_splice($teams, 1, 0, [$lastTeam]);
            }

            $secondHalfFixtures = [];
            foreach ($fixtures as $fixture) {
                $secondHalfFixtures[] = [
                    'week' => $fixture['week'] + $totalRounds,
                    'home_team_id' => $fixture['away_team_id'],
                    'away_team_id' => $fixture['home_team_id'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            Fixture::insert(array_merge($fixtures, $secondHalfFixtures));
        });
    }
}