<?php

namespace App\Http\Controllers;

use App\Enums\PlayerPosition;
use App\Enums\PlayerSkill as EnumsPlayerSkill;
use App\Models\Player;
use App\Models\PlayerSkill;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum;

class PlayerController extends Controller
{
    public function index()
    {
        $players = Player::all(['id','name','position']);
    
        return response()->json($players);
    }

    public function show($id)
    {
        try {
            $player = Player::with('skills')->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Player not found'
            ], 404);
        }
    
        return response()->json([
            'id' => $player->id,
            'name' => $player->name,
            'position' => $player->position,
            'playerSkills' => $player->skills
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'position' => ['required', new Enum(PlayerPosition::class)],
            'playerSkills' => 'required|array|min:1',
            'playerSkills.*.skill' => ['required', new Enum(EnumsPlayerSkill::class)],
            'playerSkills.*.value' => 'required|integer|min:0|max:100',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            $firstErrorField = $errors->keys()[0];
            $firstErrorValue = $request->input($firstErrorField);
            // Remove prefix from error message if key is inside array
            $field = last(explode('.', $firstErrorField));
            
            return response()->json([ 'message' => "Invalid value for $field: $firstErrorValue"], 400);
        }
    
        $player = Player::create($request->only(['name', 'position']));
        $skills = collect($request->input('playerSkills'))->map(function($skillData) use ($player) {
            return new PlayerSkill([
                'skill' => $skillData['skill'],
                'value' => $skillData['value'],
                'player_id' => $player->id,
            ]);
        });
        $player->skills()->saveMany($skills);
    
        return response()->json([
            'id' => $player->id,
            'name' => $player->name,
            'position' => $player->position,
            'playerSkills' => $player->skills
        ], 201);
    }

    public function update(Request $request, $id)
    {
        try {
            $player = Player::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Player not found'
            ], 404);
        }
    
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'position' => ['required', new Enum(PlayerPosition::class)],
            'playerSkills' => 'required|array|min:1',
            'playerSkills.*.skill' => ['required', new Enum(EnumsPlayerSkill::class)],
            'playerSkills.*.value' => 'required|integer|min:0|max:100'
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            $firstErrorField = $errors->keys()[0];
            $firstErrorValue = $request->input($firstErrorField);
            // Remove prefix from error message if key is inside array
            $field = last(explode('.', $firstErrorField));
            
            return response()->json([ 'message' => "Invalid value for $field: $firstErrorValue"], 400);
        }
    
        $player->name = $request->input('name');
        $player->position = $request->input('position');
        $player->save();

        if ($request->has('playerSkills')) {
            $skills = collect($request->input('playerSkills'))->map(function ($skill) {
                return new PlayerSkill([
                    'skill' => $skill['skill'],
                    'value' => $skill['value']
                ]);
            });
            $player->skills()->delete();
            $player->skills()->saveMany($skills);
        }
    
        $player->refresh();
    
        return response()->json([
            'id' => $player->id,
            'name' => $player->name,
            'position' => $player->position,
            'playerSkills' => $player->skills
        ]);
    }

    # NOTE: I have implemented the Bearer token flow in a static way, with the same token always being used for authentication.
    # However, in a real-world scenario, I would use time-expired unique tokens to enhance security.
    public function destroy(Request $request, $id)
    {
        $bearerToken = 'SkFabTZibXE1aE14ckpQUUxHc2dnQ2RzdlFRTTM2NFE2cGI4d3RQNjZmdEFITmdBQkE='; 
        $authHeader = $request->header('Authorization');
        if (!$authHeader || $authHeader !== "Bearer $bearerToken") {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        try {
            $player = Player::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Player not found'
            ], 404);
        }
    
        $player->delete();
    
        return response()->json([
            'message' => 'Player deleted successfully'
        ]);
    }
}
