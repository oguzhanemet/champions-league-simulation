<?php

namespace App\Services;

use App\Models\Fixture;
use App\Models\Standing;
use Illuminate\Support\Facades\DB;

class MatchSimulationService
{
    /**
     * It simulates a specific match and records the results in a database.
     */
    public function simulateMatch(Fixture $fixture): void
    {
        if ($fixture->is_played) {
            return; 
        }

        $homeTeam = $fixture->homeTeam;
        $awayTeam = $fixture->awayTeam;

        $homeAdvantage = 1.1;

        $homeLambda = (($homeTeam->attack_strength * $homeAdvantage) / $awayTeam->defense_strength) * 1.5;
        $awayLambda = ($awayTeam->attack_strength / ($homeTeam->defense_strength * $homeAdvantage)) * 1.5;

        $homeGoals = $this->poissonRandomVariable($homeLambda);
        $awayGoals = $this->poissonRandomVariable($awayLambda);

        DB::transaction(function () use ($fixture, $homeGoals, $awayGoals, $homeTeam, $awayTeam) {
            $fixture->update([
                'home_goals' => $homeGoals,
                'away_goals' => $awayGoals,
                'is_played' => true,
            ]);

            $this->updateStanding($homeTeam->id, $homeGoals, $awayGoals);
            $this->updateStanding($awayTeam->id, $awayGoals, $homeGoals);
        });
    }

    /**
     * It simulates all the matches in a given week.
     */
    public function simulateWeek(int $week): void
    {
        $fixtures = Fixture::with(['homeTeam', 'awayTeam'])
            ->where('week', $week)
            ->where('is_played', false)
            ->get();

        foreach ($fixtures as $fixture) {
            $this->simulateMatch($fixture);
        }
    }

    /**
     * Simulates all remaining matches (for the Play All button).
     */
    public function simulateAll(): void
    {
        $fixtures = Fixture::with(['homeTeam', 'awayTeam'])
            ->where('is_played', false)
            ->orderBy('week')
            ->get();

        foreach ($fixtures as $fixture) {
            $this->simulateMatch($fixture);
        }
    }

    /**
     * Poisson distribution algorithm (adaptation of Knuth's algorithm) 
     * Returns the statistically most likely integer (goal) based on the given lambda value.
     */
    private function poissonRandomVariable(float $lambda): int
    {
        $L = exp(-$lambda);
        $p = 1.0;
        $k = 0;

        do {
            $k++;
            $p *= mt_rand() / mt_getrandmax();
        } while ($p > $L);

        return $k - 1;
    }

    /**
     * It updates the team's standings according to Premier League rules.
     */
    private function updateStanding(int $teamId, int $goalsFor, int $goalsAgainst): void
    {
        $standing = Standing::firstOrCreate(['team_id' => $teamId]);

        $standing->played += 1;
        $standing->goals_for += $goalsFor;
        $standing->goals_against += $goalsAgainst;
        $standing->goal_difference += ($goalsFor - $goalsAgainst);

        if ($goalsFor > $goalsAgainst) {
            $standing->won += 1;
            $standing->points += 3;
        } elseif ($goalsFor === $goalsAgainst) {
            $standing->drawn += 1;
            $standing->points += 1;
        } else {
            $standing->lost += 1;
        }

        $standing->save();
    }

    public function recalculateStandings(): void
    {
        \App\Models\Standing::query()->update([
            'played' => 0, 'won' => 0, 'drawn' => 0, 'lost' => 0,
            'goals_for' => 0, 'goals_against' => 0, 'goal_difference' => 0, 'points' => 0
        ]);

        $playedFixtures = \App\Models\Fixture::where('is_played', true)->get();
        foreach ($playedFixtures as $fixture) {
            $this->updateStanding($fixture->home_team_id, $fixture->home_goals, $fixture->away_goals);
            $this->updateStanding($fixture->away_team_id, $fixture->away_goals, $fixture->home_goals);
        }
    }
}