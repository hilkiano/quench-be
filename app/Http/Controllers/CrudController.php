<?php

namespace App\Http\Controllers;

use App\Http\Requests\CrudRequest;
use App\Http\Requests\ForceDeleteRequest;
use App\Traits\GeneralHelpers;
use Auth;
use Illuminate\Support\Facades\Log;

class CrudController extends Controller
{
    use GeneralHelpers;

    public function create(CrudRequest $request, $className)
    {
        try {
            $model = $this->checkModel($className);

            $query = $model->create([
                ...$request->payload,
            ]);

            return $this->jsonResponse(true, $query);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            return $this->jsonResponse(false, null, $e->getMessage(), $e->getTrace(), 500);
        }
    }

    public function update(CrudRequest $request, $className)
    {
        try {
            $model = $this->checkModel($className);

            $data = $model
                ->where("id", $request->payload["id"])
                ->first()
                ->update([
                    ...$request->payload,
                    "updated_by" => Auth::check() ? Auth::id() : null
                ]);

            return $this->jsonResponse(data: $data);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            return $this->jsonResponse(false, null, $e->getMessage(), $e->getTrace(), 500);
        }
    }

    public function delete(CrudRequest $request, $className)
    {
        try {
            $model = $this->checkModel($className);

            if (is_array($request->payload["id"])) {
                $model->whereIn("id", $request->payload["id"])->get()->each(function ($row) {
                    $row->update(["deleted_by" => Auth::check() ? Auth::id() : null]);
                    $row->delete();
                });
            } else {
                $row = $model->where("id", $request->payload["id"])->first();
                $row->deleted_by = Auth::check() ? Auth::id() : null;
                $row->save();

                $row->delete();
            }

            return $this->jsonResponse();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            return $this->jsonResponse(false, null, $e->getMessage(), $e->getTrace(), 500);
        }
    }

    public function restore(CrudRequest $request, $className)
    {
        try {
            $model = $this->checkModel($className);

            if (is_array($request->payload["id"])) {
                $model->withTrashed()->whereIn("id", $request->payload["id"])->get()->each(function ($row) {
                    $row->update(["deleted_by" => null]);
                    $row->restore();
                });
            } else {
                $row = $model->withTrashed()->where("id", $request->payload["id"])->first();
                $row->deleted_by = null;
                $row->save();

                $row->restore();
            }

            return $this->jsonResponse();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            return $this->jsonResponse(false, null, $e->getMessage(), $e->getTrace(), 500);
        }
    }

    public function forceDelete(ForceDeleteRequest $request)
    {
        try {
            $result = [];
            foreach ($request->model as $model) {
                $modelClass = $this->checkModel($model["class"]);

                if ($modelClass) {
                    $del = $modelClass->find($model["id"])->forceDelete();
                    array_push($result, [
                        "request" => $model,
                        "result" => $del ? __('common.state.success') : __('common.state.failed')
                    ]);
                }
            }

            return $this->jsonResponse(data: $result);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            return $this->jsonResponse(false, null, $e->getMessage(), $e->getTrace(), 500);
        }
    }
}
