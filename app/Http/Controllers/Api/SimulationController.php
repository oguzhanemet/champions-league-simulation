<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Fixture;
use App\Models\Standing;
use App\Models\Team;
use App\Services\FixtureService;
use App\Services\MatchSimulationService;
use App\Services\ChampionshipPredictionService;
use Illuminate\Http\JsonResponse;

class SimulationController extends Controller
{
    public function __construct(
        private FixtureService $fixtureService,
        private MatchSimulationService $simulationService,
        private ChampionshipPredictionService $predictionService
    ) {}

    /**
     * Retrieves the league table, sorted by Premier League rules:
     * Points > Goal Difference > Goals For.
     */
    public function getStandings(): JsonResponse
    {
       $standings = Standing::with('team')
            ->orderByDesc('points')
            ->orderByDesc('goal_difference')
            ->orderByDesc('goals_for')
            ->get();

        return response()->json($standings);
    }

    /**
     * Gets fixtures by week.
     */
    public function getFixtures(): JsonResponse
    {
        $fixtures = Fixture::with(['homeTeam', 'awayTeam'])
            ->orderBy('week')
            ->get()
            ->groupBy('week');

        return response()->json($fixtures);
    }
    
    /**
     * Calculates championship probabilities for the final 3 weeks.
     * The algorithm considers remaining matches and current standings.
     */
    public function getPredictions(): JsonResponse
    {
        $predictions = $this->predictionService->calculateProbabilities();
        return response()->json($predictions);
    }
    /**
     * Generate the fixtures.
     */
    public function generateFixtures(): JsonResponse
    {
        $this->fixtureService->generate();
        return response()->json(['message' => 'Fixtures generated successfully']);
    }

    /**
     * Triggers the simulation for the next week based on team power factors.
     */
    public function playNextWeek(): JsonResponse
    {
        $nextWeek = Fixture::where('is_played', false)->min('week');
        
        if (!$nextWeek) {
            return response()->json(['message' => 'All matches have been played.'], 400);
        }

        $this->simulationService->simulateWeek($nextWeek);
        return response()->json(['message' => "Week {$nextWeek} played successfully"]);
    }

    /**
     * Automatically plays all remaining matches in the league.
     */
    public function playAll(): JsonResponse
    {
        $this->simulationService->simulateAll();
        return response()->json(['message' => 'All remaining matches played successfully']);
    }
    
    /**
     * Resets the simulation data, clearing fixtures and resetting team statistics.
     */
    public function resetData(): JsonResponse
    {
        
        Fixture::truncate();

        $teams = Team::all();
        foreach ($teams as $team) {
            Standing::updateOrCreate(
                ['team_id' => $team->id],
                ['played' => 0, 'won' => 0, 'drawn' => 0, 'lost' => 0, 'goals_for' => 0, 'goals_against' => 0, 'goal_difference' => 0, 'points' => 0]
            );
        }

        return response()->json(['message' => 'Simulation data has been reset']);
    }

     /**
     * It will add team.
     */
    public function addTeam(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'name' => 'required|string|unique:teams,name|max:50',
        ]);

        $team = \App\Models\Team::create([
            'name' => $request->name,
            'attack_strength' => rand(65, 95),
            'defense_strength' => rand(65, 95),
        ]);

        \App\Models\Standing::create([
            'team_id' => $team->id,
            'played' => 0, 'won' => 0, 'drawn' => 0, 'lost' => 0, 
            'goals_for' => 0, 'goals_against' => 0, 'goal_difference' => 0, 'points' => 0
        ]);

        return response()->json(['message' => 'Team added successfully!']);
    }

     /**
     * It will remove team.
     */
    public function removeTeam($id): \Illuminate\Http\JsonResponse
    {
        $team = \App\Models\Team::findOrFail($id);
        
        \App\Models\Standing::where('team_id', $id)->delete();
        \App\Models\Fixture::where('home_team_id', $id)->orWhere('away_team_id', $id)->delete();
        
        $team->delete();

        return response()->json(['message' => 'Team removed successfully!']);
    }

     /**
     * It will update team score.
     */
    public function updateScore(\Illuminate\Http\Request $request, $id): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'home_goals' => 'required|integer|min:0',
            'away_goals' => 'required|integer|min:0',
        ]);

        $fixture = \App\Models\Fixture::findOrFail($id);
        $fixture->update([
            'home_goals' => $request->home_goals,
            'away_goals' => $request->away_goals,
            'is_played' => true, 
        ]);

        $simService = new \App\Services\MatchSimulationService();
        $simService->recalculateStandings();

        return response()->json(['message' => 'Score updated successfully!']);
    }
}