<?php

namespace Modules\Crud\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Modules\Crud\Contracts\AuthorizesCrudIndex;
use Modules\Crud\Contracts\HasCrudDefinition;
use Modules\Crud\Contracts\HasCrudFilters;
use Modules\Crud\CrudField;
use Modules\Crud\CrudFilter;

final class CrudOptionsController
{
    public function __invoke(Request $request, string $filter, string $model): JsonResponse
    {
        abort_unless(is_a($model, Model::class, true), 404);
        abort_unless(is_a($model, HasCrudDefinition::class, true), 404);

        $definition = app($model::crudDefinition());
        if ($definition instanceof AuthorizesCrudIndex) {
            $definition->authorizeViewAny();
        } else {
            Gate::authorize('viewAny', $model);
        }

        $source = $request->string('source', 'filter')->toString();

        abort_unless(in_array($source, ['field', 'filter'], true), 422);

        $remoteSource = null;

        if ($source === 'field') {
            foreach ($definition->fields() as $candidate) {
                if ($candidate->name() === $filter) {
                    $remoteSource = $candidate;

                    break;
                }
            }
        } elseif ($definition instanceof HasCrudFilters) {
            foreach ($definition->filters() as $candidate) {
                if ($candidate->name() === $filter) {
                    $remoteSource = $candidate;

                    break;
                }
            }
        }

        abort_unless($remoteSource instanceof CrudField || $remoteSource instanceof CrudFilter, 404);
        abort_unless($remoteSource->isRemote(), 404);
        abort_unless($remoteSource->isRelation(), 422);

        $relationName = $remoteSource->relationName();
        abort_unless($relationName !== null, 422);

        $relation = (new $model)->{$relationName}();
        $relatedModel = $relation->getRelated();

        Gate::authorize('viewAny', $relatedModel::class);

        $search = trim($request->string('search')->toString());
        $selected = trim($request->string('selected')->toString());
        $searchColumns = $remoteSource->remoteSearchColumns();
        $searchTerms = preg_split('/[\s-]+/u', $search, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($selected === '' && mb_strlen($search) < $remoteSource->remoteConfig()['min_chars']) {
            return response()->json(['data' => []]);
        }

        $selectedOption = $selected === ''
            ? null
            : $relatedModel->newQuery()->where($remoteSource->relationColumn(), $selected)->first();

        $matches = $searchTerms === [] || $searchColumns === []
            ? collect()
            : $relatedModel->newQuery()
                ->where(function (Builder $query) use ($searchColumns, $searchTerms): void {
                    foreach ($searchTerms as $term) {
                        $query->where(function (Builder $termQuery) use ($searchColumns, $term): void {
                            foreach ($searchColumns as $column) {
                                $termQuery->orWhere($column, 'like', "%{$term}%");
                            }
                        });
                    }
                })
                ->limit(20)
                ->get();

        $options = collect($selectedOption === null ? [] : [$selectedOption])
            ->merge($matches)
            ->unique(fn (Model $option): string => (string) $option->getAttribute($remoteSource->relationColumn()))
            ->values()
            ->map(fn (Model $option): array => [
                'value' => (string) $option->getAttribute($remoteSource->relationColumn()),
                'label' => $remoteSource->remoteOptionLabel($option),
            ])
            ->all();

        return response()->json(['data' => $options]);
    }
}
