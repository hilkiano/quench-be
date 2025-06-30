<?php

namespace App\Http\Controllers;

use App\Traits\GeneralHelpers;
use Http;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    use GeneralHelpers;

    public function sendNotification(Request $request)
    {
        try {
            $http = Http::post(env("APP_FE_URL") . "/api/web-push/send", [
                "title" => "Test Push",
                "body" => "Backend send",
                "icon" => "/images/launchicon.png",
                "url" => "https://goose.hilkiano.com",
            ]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            return $this->jsonResponse(false, null, $e->getMessage(), $e->getTrace(), 500);
        }
    }
}
