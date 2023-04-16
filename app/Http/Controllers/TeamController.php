<?php

namespace App\Http\Controllers;

use App\Enums\PlayerPosition;
use App\Enums\PlayerSkill;
use App\Models\Player;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum;

class TeamController extends Controller
{
    public function process(Request $request)
    {
        $validator = Validator::make($request->all(), [
            '*' => 'required|array',
            '*.position' => ['required', new Enum(PlayerPosition::class)],
            '*.mainSkill' => ['required', new Enum(PlayerSkill::class)],
            '*.numberOfPlayers' => 'required|integer|min:1|max:11'
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            $firstErrorField = $errors->keys()[0];
            $firstErrorValue = $request->input($firstErrorField);
            // Remove prefix from error message if key is inside array
            $field = last(explode('.', $firstErrorField));

            return response()->json([ 'message' => "Invalid value for $field: $firstErrorValue"], 400);
        }
        
        // Retrieve all existing players and group them by position  
        $requirements = $request->json()->all();
        $players = Player::all();
        $playersByPosition = $players->groupBy(fn($player) => $player->position->value);
        $selectedPlayers = [];
    
        foreach ($requirements as $requirement) {
            // Extract required player position, main skill, and number of players
            $position = $requirement['position'];
            $mainSkill = $requirement['mainSkill'];
            $numberOfPlayers = $requirement['numberOfPlayers'];
    
            // Find all available players for the given position
            $availablePlayers = $playersByPosition[$position] ?? collect();
            
            // If there aren't enough players available for the given position, return an error response
            if ($availablePlayers->count() < $numberOfPlayers) {
                return response()->json(['error' => 'Insufficient number of players for position: ' . $position], 400);
            }

            // Select the required number of players with the specified main skill
            $selectedPlayersBySkill = $availablePlayers
                ->filter(fn($player) => $player->hasSkill($mainSkill))
                ->sortByDesc(fn($player) => $player->getSkillValue($mainSkill))
                ->take($numberOfPlayers);

            // If there are no players with the specified main skill, select the best players available -highest skill values)-
            if ($selectedPlayersBySkill->count() == 0) {
                $selectedPlayersBySkill = $availablePlayers
                    ->sortByDesc(fn($player) => $player->getHighestSkillValue())
                    ->take($numberOfPlayers);
            }

            $selectedPlayers = array_merge($selectedPlayers, $selectedPlayersBySkill->all());
        }

        // Format the selected players data and return it in the specific required schema
        $selectedPlayers = collect($selectedPlayers)->map(fn($player) => [
            'name' => $player->name,
            'position' => $player->position,
            'playerSkills' => collect($player->skills)->map(fn($skill) => [
                'skill' => $skill->skill,
                'value' => $skill->value
            ])
        ]);
    
        return response()->json($selectedPlayers);
    }
}
