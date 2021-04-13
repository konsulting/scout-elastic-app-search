<?php

namespace Konsulting\ScoutElasticAppSearch;

use Elastic\AppSearch\Client\Client;
use Elastic\OpenApi\Codegen\Exception\NotFoundException;

/**
 * @method createEngine($language = null)
 * @method createMetaEngine($sourceEngines)
 * @method addMetaEngineSource($sourceEngines)
 * @method createCuration($queries, $promotedDocIds = null, $hiddenDocIds = null)
 * @method createSynonymSet($synonyms)
 * @method deleteCuration($curationId)
 * @method deleteDocuments($documentIds)
 * @method deleteEngine()
 * @method deleteMetaEngineSource($sourceEngines)
 * @method deleteSynonymSet($synonymSetId)
 * @method getApiLogs($fromDate, $toDate, $currentPage = null, $pageSize = null, $query = null, $httpStatusFilter = null, $httpMethodFilter = null, $sortDirection = null)
 * @method getCountAnalytics($filters = null, $interval = null)
 * @method getCuration($curationId)
 * @method getDocuments($documentIds)
 * @method getEngine()
 * @method getSchema()
 * @method getSearchSettings()
 * @method getSynonymSet($synonymSetId)
 * @method getTopClicksAnalytics($query = null, $pageSize = null, $filters = null)
 * @method getTopQueriesAnalytics($pageSize = null, $filters = null)
 * @method indexDocuments($documents)
 * @method listCurations($currentPage = null, $pageSize = null)
 * @method listEngines($currentPage = null, $pageSize = null)
 * @method listSynonymSets($currentPage = null, $pageSize = null)
 * @method logClickThrough($queryText, $documentId, $requestId = null, $tags = null)
 * @method multiSearch($queries)
 * @method querySuggestion($query, $fields = null, $size = null)
 * @method resetSearchSettings()
 * @method search($queryText, $searchRequestParams)
 * @method updateCuration($curationId, $queries, $promotedDocIds = null, $hiddenDocIds = null)
 * @method updateDocuments($documents)
 * @method updateSchema($schema)
 * @method updateSearchSettings($searchSettings)
 */
class ElasticAppProxy
{
    /**
     * @var Client
     */
    protected $elastic;

    /**
     * @var string
     */
    protected $engine;

    public function __construct(Client $elastic)
    {
        $this->elastic = $elastic;
    }

    public function setEngine($name): ElasticAppProxy
    {
        $this->engine = str_replace('_', '-', $name);

        return $this;
    }

    public function getClient()
    {
        return $this->elastic;
    }

    /**
     * Ensure the Engine exists by checking for it and if not there creating it.
     *
     * @param $name
     */
    public function ensureEngine($name)
    {
        $this->setEngine($name);

        try {
            $this->getEngine();
            return;
        } catch (NotFoundException $e) {
            $this->createEngine();
        }
    }

    /*
     * Flush the engine
     */
    public function flushEngine($name = null)
    {
        if ($name) {
            $this->setEngine($name);
        }

        $this->deleteEngine();
        $this->createEngine();
    }

    /**
     * Dynamically call the Elastic client instance. Add the engine name to methods that require it.
     *
     * @param  string  $method
     * @param  array  $parameters
     *
     * @return mixed
     * @throws EngineNotInitialisedException
     */
    public function __call($method, $parameters)
    {
        if (! method_exists($this->elastic, $method)) {
            throw new \BadMethodCallException($method.' method not found on '.get_class($this->elastic));
        }

        if ($method !== 'listEngines' && !$this->engine) {
            throw new EngineNotInitialisedException('Unable to proxy call to Elastic App Client, no Engine initialised');
        }

        if ($method !== 'listEngines' && $this->engine) {
            array_unshift($parameters, $this->engine);
        }

        return $this->elastic->$method(...$parameters);
    }
}
