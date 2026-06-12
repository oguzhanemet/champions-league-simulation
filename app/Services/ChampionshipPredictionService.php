<?php


namespace App\Services;

use App\Models\Team;
use App\Models\Fixture;
use App\Models\Standing;

class ChampionshipPredictionService
{
    /**
     * It calculates the championship percentages based on the remaining matches.
     */
    public function calculateProbabilities(): array
    {
        $totalWeeks = Fixture::max('week') ?? 0;
        
        $playedWeeks = Fixture::where('is_played', true)->max('week') ?? 0;
        
        if (($totalWeeks - $playedWeeks) > 3) {
            return $this->getZeroProbabilities();
        }

        $currentStandings = Standing::all()->keyBy('team_id')->toArray();
        $remainingFixtures = Fixture::where('is_played', false)->get();

        if ($remainingFixtures->isEmpty()) {
            return $this->getExactWinnerProbability($currentStandings);
        }

        $teams = Team::all()->keyBy('id');
        $championshipCounts = [];
        
        foreach ($teams as $team) {
            $championshipCounts[$team->id] = 0;
        }

        $iterations = 10000; // Monte Carlo: 10 k sim

        for ($i = 0; $i < $iterations; $i++) {
            $simulatedStandings = $currentStandings;

            foreach ($remainingFixtures as $fixture) {
                $homeId = $fixture->home_team_id;
                $awayId = $fixture->away_team_id;
                
                $homePower = $teams[$homeId]->attack_strength;
                $awayPower = $teams[$awayId]->attack_strength;
                
                $totalPower = $homePower + $awayPower;
                $rand = mt_rand(1, $totalPower * 100) / 100;

                if ($rand <= $homePower) {
                    $simulatedStandings[$homeId]['points'] += 3;
                } elseif ($rand > $homePower + ($totalPower * 0.2)) {
                    $simulatedStandings[$awayId]['points'] += 3;
                } else {
                    $simulatedStandings[$homeId]['points'] += 1;
                    $simulatedStandings[$awayId]['points'] += 1;
                }
            }

            usort($simulatedStandings, function ($a, $b) {
                if ($a['points'] === $b['points']) {
                    return $b['goal_difference'] <=> $a['goal_difference'];
                }
                return $b['points'] <=> $a['points'];
            });

            $championId = $simulatedStandings[0]['team_id'];
            $championshipCounts[$championId]++;
        }

        $probabilities = [];
        foreach ($championshipCounts as $teamId => $count) {
            $teamName = $teams[$teamId]->name;
            $probabilities[$teamName] = round(($count / $iterations) * 100, 1);
        }

        arsort($probabilities);
        
        return $probabilities;
    }

    private function getZeroProbabilities(): array
    {
        $teams = Team::pluck('name')->toArray();
        $probs = [];
        foreach ($teams as $team) {
            $probs[$team] = 0;
        }
        return $probs;
    }

    private function getExactWinnerProbability(array $standings): array
    {
        usort($standings, fn($a, $b) => $b['points'] <=> $a['points']);
        $teams = Team::all()->keyBy('id');
        
        $probs = [];
        foreach ($standings as $index => $standing) {
            $teamName = $teams[$standing['team_id']]->name;
            $probs[$teamName] = ($index === 0) ? 100 : 0;
        }
        return $probs;
    }
}