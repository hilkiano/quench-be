<?php

namespace App\Http\Controllers;

use App\Http\Requests\Umami\PageviewRequest;
use App\Traits\GeneralHelpers;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UmamiController extends Controller
{
    use GeneralHelpers;

    private $db;

    public function __construct()
    {
        $this->db = "mysql_umami";
    }

    public function getPageViews(PageviewRequest $request)
    {
        try {
            return $this->jsonResponse(data: DB::connection($this->db)->table("website_event")->where("url_path", "/recipe/{$request->id}")->count());
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            return $this->jsonResponse(false, null, $e->getMessage(), $e->getTrace(), 500);
        }
    }
}
