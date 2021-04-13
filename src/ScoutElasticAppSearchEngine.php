<?php

namespace Konsulting\ScoutElasticAppSearch;

use Laravel\Scout\Builder;
use Laravel\Scout\Engines\Engine;
use Elastic\AppSearch\Client\Client;
use Illuminate\Database\Eloquent\SoftDeletes;
use Elastic\OpenApi\Codegen\Exception\NotFoundException;

class ScoutElasticAppSearchEngine extends Engine
{
    /**
     * The Algolia client.
     *
     * @var Client
     */
    protected $elastic;

    /**
     * Determines if soft deletes for Scout are enabled or not.
     *
     * @var bool
     */
    protected $softDelete;

    /**
     * The name of the index to use on the operations
     * Set through the use of initIndex on calls that need to operate on the index
     *
     * @var string
     */
    protected $indexName;

    /**
     * Create a new engine instance.
     *
     * @param  Client  $elastic
     * @param  bool  $softDelete
     * @return void
     */
    public function __construct(Client $elastic, $softDelete = false)
    {
        $this->elastic = $elastic;
        $this->softDelete = $softDelete;
    }

    /**
     * Update the given model in the index.
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $models
     * @return void
     */
    public function update($models)
    {
        if ($models->isEmpty()) {
            return;
        }

        $this->ensureIndexExists($models->first()->searchableAs());

        if ($this->usesSoftDelete($models->first()) && $this->softDelete) {
            $models->each->pushSoftDeleteMetadata();
        }

        $objects = $models->map(function ($model) {
            if (empty($searchableData = $model->toSearchableArray())) {
                return;
            }

            return array_merge(
                ['scout_object_id' => $model->getScoutKey()],
                $searchableData,
                $model->scoutMetadata()
            );
        })->filter()->values()->all();

        if (! empty($objects)) {
            $this->elastic->indexDocuments($this->indexName, $objects);
        }
    }

    /**
     * Remove the given model from the index.
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $models
     * @return void
     */
    public function delete($models)
    {
        $this->ensureIndexExists($models->first()->searchableAs());

        $this->elastic->deleteDocuments(
            $this->indexName,
            $models->map(function ($model) {
                return $model->getScoutKey();
            })->values()->all()
        );
    }

    /**
     * Perform the given search on the engine.
     *
     * @param  \Laravel\Scout\Builder  $builder
     * @return mixed
     */
    public function search(Builder $builder)
    {
        return $this->performSearch($builder, array_filter([
            'filters' => $this->filters($builder),
            'size' => $builder->limit,
        ]));
    }

    /**
     * Perform the given search on the engine.
     *
     * @param  \Laravel\Scout\Builder  $builder
     * @param  int  $perPage
     * @param  int  $page
     * @return mixed
     */
    public function paginate(Builder $builder, $perPage, $page)
    {
        return $this->performSearch($builder, [
            'filters' => $this->filters($builder),
            'size' => $perPage,
            'current' => $page - 1,
        ]);
    }

    /**
     * Perform the given search on the engine.
     *
     * @param  \Laravel\Scout\Builder  $builder
     * @param  array  $options
     * @return mixed
     */
    protected function performSearch(Builder $builder, array $options = [])
    {
        $this->ensureIndexExists($builder->index ?: $builder->model->searchableAs());

        if ($builder->callback) {
            return call_user_func(
                $builder->callback,
                $this->elastic,
                $builder->query,
                $options,
                $this->indexName
            );
        }

        return $this->elastic->search($this->indexName, $builder->query, $options);
    }

    /**
     * Get the filter array for the query.
     *
     * @param  \Laravel\Scout\Builder  $builder
     * @return array
     */
    protected function filters(Builder $builder)
    {
        return collect($builder->wheres)->mapWithKeys(function ($value, $key) {
            return [$key => [$value]];
        })->all();
    }

    /**
     * Pluck and return the primary keys of the given results.
     *
     * @param  mixed  $results
     * @return \Illuminate\Support\Collection
     */
    public function mapIds($results)
    {
        return collect($results['results'])->pluck('scout_object_id')->values();
    }

    /**
     * Map the given results to instances of the given model.
     *
     * @param  \Laravel\Scout\Builder  $builder
     * @param  mixed  $results
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function map(Builder $builder, $results, $model)
    {
        if (count($results['results']) === 0) {
            return $model->newCollection();
        }

        $objectIds = collect($results['results'])->pluck('scout_object_id')->values()->all();
        $objectIdPositions = array_flip($objectIds);

        return $model->getScoutModelsByIds(
            $builder, $objectIds
        )->filter(function ($model) use ($objectIds) {
            return in_array($model->getScoutKey(), $objectIds);
        })->sortBy(function ($model) use ($objectIdPositions) {
            return $objectIdPositions[$model->getScoutKey()];
        })->values();
    }

    /**
     * Get the total count from a raw result returned by the engine.
     *
     * @param  mixed  $results
     * @return int
     */
    public function getTotalCount($results)
    {
        return $results['meta']['page']['total_results'];
    }

    /**
     * Flush all of the model's records from the engine.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function flush($model)
    {
        $this->ensureIndexExists($model->first()->searchableAs());

        // A bit brutal :(
        $this->elastic->deleteEngine($this->indexName);
        $this->ensureIndexExists($this->indexName);
    }

    /**
     * Determine if the given model uses soft deletes.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return bool
     */
    protected function usesSoftDelete($model)
    {
        return in_array(SoftDeletes::class, class_uses_recursive($model));
    }

    /**
     * Dynamically call the Algolia client instance.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->elastic->$method(...$parameters);
    }

    /**
     * Make sure the index exists.
     *
     * @param $name
     */
    protected function ensureIndexExists($name): void
    {
        $this->indexName = str_replace('_', '-', $name);

        try {
            $this->elastic->getEngine($this->indexName);
            return;
        } catch (NotFoundException $e) {
            $this->elastic->createEngine($this->indexName);
        }
    }
}
