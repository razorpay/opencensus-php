<?php

namespace RZP\Models\Key;

use App;
use RZP\Constants\Metric;
use RZP\Models\Base\PublicCollection;
use RZP\Trace\TraceCode;

class CredcaseService
{
    const COUNT = 'count';
    const ITEMS = 'items';
    const ENTITY = 'entity';
    protected $entity = 'key';

    protected $app;

    private $trace;

    public function __construct($app)
    {
        $this->app = $app;
        $this->trace = app('trace');
    }

    /**
     * Transforms the ApiKeyListResponse into a desired format.
     *
     * @param object $apiKeyListResponse The response object containing the list of API keys.
     * @return PublicCollection The transformed array of API key items.
     */
    public function transformCredcaseResponse($apiKeyListResponse)
    {
        // Access the 'items' field from the ApiKeyListResponse object
        $items = $apiKeyListResponse->getItems();
        $collection = new PublicCollection();

        // Iterate over each item in the 'items' array
        foreach ($items as $item) {
            $key = $this->buildKeyFromCredcaseResponse($item);
            $collection->push($key);
        }

        // Return the transformed items
        return $collection;
    }

    public function fetchKeys($routeName, $credcaseResponse, $queryResults)
    {
        $credcaseItems = $this->transformCredcaseResponse($credcaseResponse);

        // Extract relevant fields for comparison (id, merchant_id, expired_at)
        $filteredCredcaseItems = $this->extractFields($credcaseItems);
        $filteredQueryItems = $this->extractFields($queryResults);

        // Compare only the extracted fields
        if ($this->compareKeys($filteredCredcaseItems, $filteredQueryItems)) {
            return $credcaseItems;
        }

        // Log metric if there is a mismatch
        $this->logCredcaseError($routeName, TraceCode::CREDCASE_READ_RESPONSE_MISMATCH, Metric::CREDCASE_READ_RESPONSE_MISMATCH);
        return $queryResults;
    }

    /**
     * Extract specific fields (id, merchant_id, expired_at) from the items array
     */
    private function extractFields($items)
    {
        // Convert the PublicCollection to an array of items
        $itemsArray = $items->toArrayWithItems();  // Assuming this method gives you the array of items
        $itemsList = $itemsArray['items']; // Extract the items part

        return array_map(function ($item) {
            return [
                'id' => $item["id"] ?? null,
                'merchant_id' => $item['merchant_id'] ?? null,
                'expired_at' => empty($item['expired_at']) ? null : 'not null',
            ];
        }, $itemsList);
    }

    /**
     * Compare two arrays of extracted fields
     */
    private function compareKeys($credcaseItems, $queryItems)
    {
        // Sort both arrays to ensure comparison order consistency
        sort($credcaseItems);
        sort($queryItems);

        return $credcaseItems === $queryItems;
    }

    /**
     * Logs a count mismatch for the given route.
     *
     * @param $routeName
     * @param $traceCode
     * @param $metric
     */
    public function logCredcaseError($routeName, $traceCode, $metric): void
    {
        $this->trace->info($traceCode, [
            'route_name' => $routeName,
        ]);
        $this->trace->count($metric, [
                'route_name' => $routeName,
            ]
        );
    }

    private function buildKeyFromCredcaseResponse($credcaseKeyResponse) {
        $key = new Entity;
        $id = $credcaseKeyResponse->getId();
        Entity::stripSign($id);
        $key->setId($id);
        $key->setMerchantId($credcaseKeyResponse->getOwnerId());
        $key->setCreatedAt(empty($credcaseKeyResponse->getCreatedAt()) ? null : $credcaseKeyResponse->getCreatedAt());
        $key->setUpdatedAt(empty($credcaseKeyResponse->getUpdatedAt()) ? null : $credcaseKeyResponse->getUpdatedAt());
        $key->setExpiredAt(empty($credcaseKeyResponse->getExpiredAt()) ? null : $credcaseKeyResponse->getExpiredAt());
        return $key;
    }
}
