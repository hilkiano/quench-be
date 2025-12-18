<?php

namespace App\Http\Controllers;

use App\Models\Recipe;
use App\Notifications\SlackNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SlackController extends Controller
{
    public function notify(Request $request)
    {
        try {
            $recipe = Recipe::find($request->recipe_id);
            Notification::route('slack', env('SLACK_BOT_WEBHOOK_URL'))->notify(new SlackNotification($recipe));
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
