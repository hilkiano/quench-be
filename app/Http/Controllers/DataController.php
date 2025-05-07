<?php

namespace App\Http\Controllers;

use App\Http\Requests\Data\ComboboxRequest;
use App\Http\Requests\Data\ListRequest;
use App\Http\Requests\Data\StatisticRequest;
use App\Models\Recipe;
use App\Traits\GeneralHelpers;
use Auth;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Str;

class DataController extends Controller
{
    use GeneralHelpers;

    public function index($className, $id, $relations = null)
    {
        try {
            $model = $this->checkModel($className);
            if ($model->getConnection()->getSchemaBuilder()->hasColumn($model->getTable(), "deleted_at")) {
                $data = $model->withTrashed()
                    ->when($relations, function ($query) use ($relations) {
                        $query->with(explode("&", $relations));
                    })
                    ->find($id);
            } else {
                $data = $model
                    ->when($relations, function ($query) use ($relations) {
                        $query->with(explode("&", $relations));
                    })
                    ->find($id);
            }

            return $this->jsonResponse(data: $data);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            return $this->jsonResponse(false, null, $e->getMessage(), $e->getTrace(), 500);
        }
    }

    public function comboboxData(ComboboxRequest $request)
    {
        try {
            $data = null;
            $model = $this->checkModel($request->model);
            if ($model) {
                $data = $model->select("$request->label as label", "$request->value as value")->get();
            }

            return $this->jsonResponse(data: $data);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            return $this->jsonResponse(false, null, $e->getMessage(), $e->getTrace(), 500);
        }
    }

    public function list(ListRequest $request)
    {
        try {
            $model = $this->checkModel($request->model);

            $withTrashed = $request->has("with_trashed") ? filter_var($request->with_trashed, FILTER_VALIDATE_BOOLEAN) : false;

            $query = $model
                ->when($model->getConnection()->getSchemaBuilder()->hasColumn($model->getTable(), "deleted_at"), function ($query) use ($withTrashed) {
                    $query->withTrashed($withTrashed);
                })
                ->when($request->has("relations"), function ($query) use ($request) {
                    $query->with($this->generateRelationsArray($request->relations));
                })
                ->when($request->has("relation_count"), function ($query) use ($request) {
                    $query->withCount($this->generateRelationsArray($request->relation_count));
                })
                ->when($request->has("sort") && $request->has("sort_direction"), function ($query) use ($request) {
                    $query->orderBy($request->sort, $request->sort_direction);
                })
                ->when($request->has("global_filter") && $request->has("global_filter_columns"), function ($query) use ($request) {
                    $query->where(function ($query) use ($request) {
                        foreach (explode(",", $request->global_filter_columns) as $column) {
                            $query->orWhere($column, "LIKE", "%$request->global_filter%");
                        }
                    });
                })
                ->when($request->has("filter"), function ($query) use ($request, $model) {
                    $filterObj = json_decode($request->filter);
                    foreach ($filterObj as $filter) {
                        if ($model->getConnection()->getSchemaBuilder()->hasColumn($model->getTable(), $filter->column)) {
                            $query->where($filter->column, $filter->value);
                        }
                    }
                })
                ->paginate($request->has("limit") ? $request->limit : 20)->withQueryString();

            $result = [
                'total'     => $query->total(),
                'prev_page'  => $query->previousPageUrl(),
                'next_page'  => $query->nextPageUrl(),
                'rows'      => $query->items(),
                'page_count' => (int) ceil($query->total() / $query->perPage()),
                'page'      => $query->currentPage()
            ];

            return $this->jsonResponse(data: $result);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            return $this->jsonResponse(false, null, $e->getMessage(), $e->getTrace(), 500);
        }
    }

    public function statistics(StatisticRequest $request)
    {
        try {
            $result = [];
            switch ($request->type) {
                case 'user':
                    $result["user"] = $this->getUserStatistics($request->user_id);
                    break;
            }

            return $this->jsonResponse(data: $result);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            return $this->jsonResponse(false, null, $e->getMessage(), $e->getTrace(), 500);
        }
    }

    private function getUserStatistics(string $userId)
    {
        $submittedRecipe = Recipe::where("created_by", $userId)->count();

        return [
            "submitted_recipe" => $submittedRecipe,
            "tried_recipe" => 0
        ];
    }
}
