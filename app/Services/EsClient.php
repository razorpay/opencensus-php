<?php

namespace RZP\Services;

use Elasticsearch\ClientBuilder;

class EsClient
{
    protected $client;

    protected $esMock;

    protected $esHeimdallMock;

    protected $app;

    protected $heimdallClient;


    public function setEsClient($params)
    {
        $app = \App::getFacadeRoot();

        $this->esMock = $app['config']->get('database.es_mock');

        // Initiate client only if ES is not mocked.
        if ($this->esMock !== true)
        {
            $this->client = ClientBuilder::fromConfig($params);
        }
    }

    public function setHeimdallESClient($hosts)
    {
        $app = \App::getFacadeRoot();

        $this->esHeimdallMock = $app['config']->get('database.es_audit_mock');

        if ($this->esHeimdallMock !== true)
        {
            $this->heimdallClient = ClientBuilder::create()
                                        ->setHosts($hosts)->build();
        }
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

        /**
         * TODO:
         * RESOLVE THIS BEFORE MERGE.
         * Confirm what version of elasticsearch is there on prod.
         * I have upgraded the library in this pr.
         * With my combination, following query doesn't work.
         * Ref: http://stackoverflow.com/questions/40519806/no-query-registered-for-filtered
         */

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
