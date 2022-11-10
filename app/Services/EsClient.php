<?php

namespace RZP\Services;

use Elasticsearch\ClientBuilder;

use RZP\Constants\Es;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Exception\InvalidArgumentException;

class EsClient
{
    /**
     * Value gets used during scroll queries to ES.
     * It tells ES to keep scroll search context to be open
     * for another x seconds. Post that it'll return empty results.
     */
    const DEFAULT_SCROLL_SECS = '30s';

    protected $client;

    protected $esMock;

    protected $esHeimdallMock;

    protected $heimdallClient;

    protected $dedupeClient;

    protected $dedupeMock;

    protected $config;

    protected $trace;

    /**
     * If running unit tests, after every ES write operation we
     * manually do index refresh so it's available readily for tests.
     * Otherwise delay for refresh is 1 sec.
     *
     * @var boolean
     */
    protected $runningUnitTests;

    public function __construct($app)
    {
        $this->config = $app['config'];

        if (empty($this->dedupeClient) === true )
        {
            $dedupeHost = $this->config->get('database.dedupe_es_host').':443';

            $this->setDedupeEsClient($dedupeHost);
        }

        $this->trace = $app['trace'];

        $this->runningUnitTests = $app->runningUnitTests();
    }

    public function setEsClient($params)
    {
        $this->esMock = $this->config->get('database.es_mock');

        // Initiate client only if ES is not mocked.
        if ($this->esMock !== true)
        {
            $this->client = ClientBuilder::fromConfig($params);
        }
    }

    public function setHeimdallESClient($hosts)
    {
        $this->esHeimdallMock = $this->config->get('database.es_audit_mock');

        if ($this->esHeimdallMock !== true)
        {
            $this->heimdallClient = ClientBuilder::create()
                                        ->setHosts($hosts)->build();
        }
    }

    public function setDedupeEsClient($hosts)
    {
        try
        {
            $this->dedupeMock = $this->config->get('database.dedupe_es_mock');

            if (empty($this->dedupeMock) === false and $this->dedupeMock === false)
            {
                $this->dedupeClient = ClientBuilder::create()
                                                   ->setHosts($hosts)->build();
            }
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::DEDUPE_ES_CONNECTION_FAILURE
            );
        }
    }

    public function getDedupeClient()
    {
        return $this->dedupeClient;
    }

    public function cat(array $params)
    {
        $res = $this->client->cat()->indices($params);

        return [$res];
    }

    public function catCount()
    {
        return $this->client->cat()->count();
    }

    public function explain(array $params)
    {
        return $this->client->explain($params);
    }

    public function getAliases(array $params)
    {
        return $this->client->indices()->getAliases($params);
    }

    public function getMapping(array $params)
    {
        $mapping = $this->client->indices()->getMapping($params);

        return $mapping;
    }

    public function getSettings(array $params)
    {
        $settings = $this->client->indices()->getSettings();

        return $settings;
    }

    public function update($params)
    {
        if ($this->esMock === true)
        {
            return null;
        }

        $response = $this->client->update($params);

        $this->refreshIndicesIfApplicable();

        return $response;
    }

    /**
     * Makes a bulk request to ES.
     *
     * In our case using this same method to create/update even a single document.
     *
     * In ideal world, one would use index() for creating documents for first time,
     * update() to update document for next times. But in async flows we would
     * also want to handle failures and do upsetr instead. In async flows many a times
     * before index() the document has reached ES by previous tries or some other flows.
     *
     * Bulk update method handles everything: create, update, partial update, upserts.
     * We don't have to provide additional details (upsert params etc) as well. Also
     * afaik internally bulk update is optimized for bulk insertions/updates but has
     * makes no difference with single document.
     *
     * It first checks if doc exists already, if it is then the param body will
     * be used as partial document and it patches the same. If the document doesn't
     * exist then it'll create one with the same body.
     *
     * Refs:
     * - https://www.elastic.co/guide/en/elasticsearch/reference/current/docs-bulk.html
     *
     * @param array $params
     *
     * @return array
     */
    public function bulkUpdate($params)
    {
        // If ES mock is set to true, return dummy response.

        if ($this->esMock === true)
        {
            return ['errors' => false, 'took' => '1'];
        }

        $response = $this->client->bulk($params);

        $this->refreshIndicesIfApplicable();

        return $response;
    }

    public function postAliases(array $params)
    {
        return $this->client->indices()->updateAliases($params);
    }

    public function search(array $params)
    {
        if ($this->esMock === true)
        {
            return [Es::HITS => [Es::HITS => []]];
        }

        return $this->client->search($params);
    }

    /**
     * Search and scroll: Given the es request parameters, makes
     * scroll calls to ES and keeps returning the results using Generator.
     *
     * Note: The callee shouldn't take more than DEFAULT_SCROLL_SECS s to
     * process the yield results or else the scroll context in ES dies
     * and will not return further results.
     *
     * @param array $params
     *
     * @return \Generator
     */
    public function searchAndScroll(array $params): \Generator
    {
        $params[Es::SCROLL] = self::DEFAULT_SCROLL_SECS;

        $response = $this->search($params);

        while ((isset($response[Es::HITS][Es::HITS]) === true) and
            (count($response[Es::HITS][Es::HITS]) > 0))
        {
            yield $response;

            $scrollId = $response[Es::_SCROLL_ID];

            $response = $this->scroll($scrollId);
        }
    }

    /**
     * Makes a scroll search call to ES with given scroll id.
     *
     * @param string $scrollId
     *
     * @return array
     */
    public function scroll(string $scrollId): array
    {
        if ($this->esMock === true)
        {
            return [Es::HITS => [Es::HITS => []]];
        }

        $params = [
            Es::SCROLL_ID => $scrollId,
            Es::SCROLL    => self::DEFAULT_SCROLL_SECS,
        ];

        return $this->client->scroll($params);
    }

    public function indexExists(array $params)
    {
        if ($this->esMock === true)
        {
            return null;
        }

        return $this->client->indices()->exists($params);
    }

    public function get($params)
    {
        if ($this->client === null)
        {
            $this->trace->traceException(new \Exception());
        }

        return $this->client->get($params);
    }

    public function mget($params)
    {
        return $this->client->mget($params);
    }

    public function delete($params)
    {
        if ($this->esMock === true)
        {
            return null;
        }

        $response = $this->client->delete($params);

        $this->refreshIndicesIfApplicable();

        return $response;
    }

    public function createIndex($params)
    {
        if ($this->esMock === true)
        {
            return null;
        }

        return $this->client->indices()->create($params);
    }

    public function getClient()
    {
        return $this->client;
    }

    public function getHeimdallClient()
    {
        return $this->heimdallClient;
    }

    public function searchHeimdall($params)
    {
        if ($this->esHeimdallMock === true)
        {
            return null;
        }

        $this->trace->info(TraceCode::ES_SEARCH_QUERY, ['params' => $params]);

        $searchResponse = $this->heimdallClient->search($params);

        if ($searchResponse[Es::HITS]['total'] === 0)
        {
            return null;
        }

        $entityResults = $searchResponse[Es::HITS][Es::HITS];

        return $entityResults;
    }

    public function updateHeimdall($params)
    {
        if ($this->esHeimdallMock === true)
        {
            return null;
        }

        $this->trace->info(TraceCode::ES_UPDATE_ACTION, ['params' => $params]);

        $updateResponse = $this->heimdallClient->update($params);

        return true;
    }

    public function index($params)
    {
        $this->client->index($params);
    }

    public function indexHeimdall($params)
    {
        $paramTrace = $params;

        unset($paramTrace['body']['admin']['email'],
            $paramTrace['body']['entity']['change']['old']['email'],
            $paramTrace['body']['entity']['change']['old']['password']);

        $this->trace->info(TraceCode::ES_INDEX_REQUEST, ['params' => $paramTrace]);

        $this->heimdallClient->index($params);
    }

    public function updateDedupe($params)
    {
        try
        {
            if (empty($this->dedupeClient) === true or $this->dedupeMock === true)
            {
                return null;
            }

            $this->trace->info(TraceCode::ES_UPDATE_ACTION, ['params' => $params]);

            $this->dedupeClient->update($params);
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::DEDUPE_ES_UPDATE_FAILURE
            );
        }

        return true;
    }

    public function indexDedupe($params)
    {
        if (empty($this->dedupeClient) === false)
        {
            $this->dedupeClient->index($params);
        }
    }

    public function searchDedupe($params)
    {

        $entityResults = [];

        try
        {
            if (empty($this->dedupeClient ) === true or $this->dedupeMock === true)
            {
                return null;
            }

            $searchResponse = $this->dedupeClient->search($params);

            if ($searchResponse[Es::HITS]['total'] === 0)
            {
                return null;
            }
            $entityResults = $searchResponse[Es::HITS][Es::HITS];
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::DEDUPE_ES_SEARCH_FAILURE
            );
        }

        return $entityResults;
    }

    /**
     * Refreshes Es indexes if running unit tests.
     */
    protected function refreshIndicesIfApplicable()
    {
        if ($this->runningUnitTests === true)
        {
            $this->client->indices()->refresh();
        }
    }
}
