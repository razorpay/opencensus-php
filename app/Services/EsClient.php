<?php

namespace RZP\Services;

use Elasticsearch\ClientBuilder;

use RZP\Exception\InvalidArgumentException;
use RZP\Trace\TraceCode;

class EsClient
{
    protected $client;

    protected $esMock;

    protected $esHeimdallMock;

    protected $heimdallClient;

    protected $config;

    protected $trace;

    public function __construct($app)
    {
        $this->config = $app['config'];

        $this->trace = $app['trace'];
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

    public function cat(array $params)
    {
        $res = $this->client->cat()->indices($params);

        $this->trace->debug(TraceCode::ES_CAT_RESPONSE, [$res]);

        return [$res];
    }

    public function explain(array $params)
    {
        return $this->client->explain($params);
    }

    public function getMapping(array $params)
    {
        $mapping = $this->client->indices()->getMapping($params);

        $this->trace->debug(TraceCode::ES_MAPPING_RESPONSE, $mapping);

        return $mapping;
    }

    public function getSettings(array $params)
    {
        $settings = $this->client->indices()->getSettings();

        $this->trace->debug(TraceCode::ES_SETTINGS_RESPONSE, $settings);

        return $settings;
    }

    public function update($params)
    {
        // If ES mock is set to true.
        if ($this->esMock === true)
        {
            return null;
        }

        return $this->client->update($params);
    }

    public function bulkUpdate($params)
    {
        // If ES mock is set to true.
        if ($this->esMock === true)
        {
            return null;
        }

        return $this->client->bulk($params);
    }

    public function search(array $params)
    {
        if ($this->esMock === true)
        {
            return ['hits' => ['hits' => []]];
        }

        return $this->client->search($params);
    }

    public function indexExists(array $params)
    {
        if ($this->esMock === true)
        {
            return null;
        }

        return $this->client->indices()->exists($params);
    }

    public function searchNotes($params)
    {
        // If ES mock is set to true.
        if ($this->esMock === true)
        {
            return null;
        }

        $searchResponse = $this->client->search($params);

        if ($searchResponse['hits']['total'] === 0)
        {
            return null;
        }

        $entityResults = $searchResponse['hits']['hits'];
        $entityIds = [];
        foreach ($entityResults as $_ => $entityData)
        {
            $entityIds[] = $entityData['_id'];
        }

        return $entityIds;
    }

    public function get($params)
    {
        return $this->client->get($params);
    }

    public function multiGet($params)
    {
        return $this->client->mget($params);
    }

    public function delete($params)
    {
        if ($this->esMock === true)
        {
            return null;
        }

        return $this->client->delete($params);
    }

    public function createIndex($params)
    {
        if ($this->esMock === true)
        {
            return null;
        }

        return $this->client->indices()->create($params);
    }

    public function deleteIndex($params)
    {
        return $this->client->indices()->delete($params);
    }

    public function changeIndexSettings($params)
    {
        $this->client->indices()->putSettings($params);
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
        // If ES mock is set to true.
        if ($this->esHeimdallMock === true)
        {
            return null;
        }

        $searchResponse = $this->heimdallClient->search($params);

        if ($searchResponse['hits']['total'] === 0)
        {
            return null;
        }

        $entityResults = $searchResponse['hits']['hits'];

        return $entityResults;
    }

    public function index($params)
    {
        $this->client->index($params);
    }

    public function indexHeimdall($params)
    {
        $this->heimdallClient->index($params);
    }
}
