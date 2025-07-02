<?php

namespace App\Http\Controllers;

use App\Http\Requests\Notification\SendNotificationRequest;
use App\Traits\GeneralHelpers;
use Http;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    use GeneralHelpers;

    public function sendNotification(SendNotificationRequest $request)
    {
        try {
            $notification = Http::post(env("APP_FE_URL") . "/api/web-push/send", [
                "subscription" => $request->subscription,
                "title" => $request->title,
                "body" => $request->body,
                "icon" => "/images/launchicon.png",
                "url" => $request->url ? $request->url : "https://goose.hilkiano.com",
                "image" => $request->image
            ]);

            return $this->jsonResponse(data: [
                "send_notification" => $notification->status()
            ]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            return $this->jsonResponse(false, null, $e->getMessage(), $e->getTrace(), 500);
        }
    }
}
