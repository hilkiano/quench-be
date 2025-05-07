<?php

namespace App\Http\Controllers;

use App\Http\Requests\RecipeDraft\DeleteRequest;
use App\Http\Requests\RecipeDraft\SaveRequest;
use App\Models\RecipeDraft;
use Illuminate\Support\Facades\Log;
use App\Traits\GeneralHelpers;
use Auth;

class RecipeDraftController extends Controller
{
    use GeneralHelpers;

    public function save(SaveRequest $request)
    {
        try {
            // Format the data
            $value = $this->getFormData($request);

            // Update or create new draft
            if ($request->id) {
                $draft = RecipeDraft::find((int) $request->id);
                $draft->data = $value;
                $draft->created_by = Auth::id();
                $draft->updated_by = Auth::id();

                if ($request->image) {
                    // Delete old image
                    if ($draft->image_url) {
                        $parsedUrl = parse_url($draft->image_url);
                        $this->deleteFile("s3", $parsedUrl["path"]);
                    }

                    $draft->image_url = $this->storeImage("s3", $request->file("image"), "draft/$request->id");
                }

                $draft->save();
            } else {
                // Delete old draft data if the user's draft count reaches the allowed length.
                $drafts = RecipeDraft::where("created_by", Auth::id())->orderBy("created_at", "desc")->get();

                if (count($drafts) === (int) env("DRAFT_MAX")) {
                    $oldestDraft = $drafts->last();
                    RecipeDraft::where("id", $oldestDraft->id)->delete();
                }

                $draft = new RecipeDraft();
                $draft->data = $value;
                $draft->created_by = Auth::id();
                $draft->updated_by = Auth::id();

                $draft->save();

                if ($request->image) {
                    $draft = RecipeDraft::find($draft->id);
                    $draft->image_url = $this->storeImage("s3", $request->file("image"), "draft/$draft->id");

                    $draft->save();
                }
            }

            return $this->jsonResponse(data: $draft->id);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            return $this->jsonResponse(false, null, $e->getMessage(), $e->getTrace(), 500);
        }
    }

    public function delete(int $id)
    {
        try {
            $draft = RecipeDraft::find($id);

            if ($draft->image_url) {
                $path = env("APP_ENV") === "local" ? "development/draft/{$draft->id}" : "draft/{$draft->id}";
                $this->deleteDirectory("s3", $path);
            }

            $draft->delete();

            return $this->jsonResponse(data: $draft);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            return $this->jsonResponse(false, null, $e->getMessage(), $e->getTrace(), 500);
        }
    }

    private function getFormData(SaveRequest $request)
    {
        $arrayData = [
            "basic_info" => $request->basic_info ? json_decode($request->basic_info) : null,
            "ingredients" => $request->ingredients ? json_decode($request->ingredients) : [],
            "steps" => $request->steps ? json_decode($request->steps) : [],
        ];

        if ($request->id) {
            $oldData = RecipeDraft::find($request->id);

            $arrayData["basic_info"] = $oldData->data["basic_info"] ? array_replace($oldData->data["basic_info"], (array) $arrayData["basic_info"]) : (array) $arrayData["basic_info"];
            $arrayData["ingredients"] = $oldData->data["ingredients"] ? array_replace($oldData->data["ingredients"], (array) $arrayData["ingredients"]) : (array) $arrayData["ingredients"];
            $arrayData["steps"] = $oldData->data["steps"] ? array_replace($oldData->data["steps"], (array) $arrayData["steps"]) : (array) $arrayData["steps"];
        }

        return $arrayData;
    }
}
