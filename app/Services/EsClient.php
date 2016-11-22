<?php

namespace RZP\Services;

use Elasticsearch\ClientBuilder;

class EsClient
{
    protected $client;

    protected $esMock;

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
        return $this->client->delete($params);
    }

    public function createIndex($params)
    {
        return $this->client->index($params);
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


}