<?php

namespace RZP\Models\Key;

use App;
use RZP\Constants\Metric;
use Rzp\Credcase\Apikey\V1\ApiKeyResponse;
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
        if (!isset($credcaseResponse)) {
            return new PublicCollection();
        }
        return $this->transformCredcaseResponse($credcaseResponse);
    }

    public function fetchKey($routeName, ?ApiKeyResponse $credcaseResponse, $queryResult)
    {
        if (!isset($credcaseResponse)) {
            return null;
        }
        return $this->buildKeyFromCredcaseResponse($credcaseResponse);
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
    public function logCredcaseError($routeName, $traceCode, $metric, $credcaseItems, $apiDBItems, ): void
    {
        $this->trace->info($traceCode, [
            'route_name' => $routeName,
            'credcase_items' => $credcaseItems,
            'api_items' => $apiDBItems
        ]);
        $this->trace->count($metric, [
                'route_name' => $routeName,
            ]
        );
    }

    private function buildKeyFromCredcaseResponse(ApiKeyResponse $credcaseKeyResponse) {
        $key = new Entity;
        $id = $credcaseKeyResponse->getId();
        $strip = Entity::stripSign($id);
        if(!$strip) {
            $id = substr($id, -14);
        }
        $key->setId($id);
        $key->setMerchantId($credcaseKeyResponse->getOwnerId());
        $key->setCreatedAt(empty($credcaseKeyResponse->getCreatedAt()) ? null : $credcaseKeyResponse->getCreatedAt());
        $key->setUpdatedAt(empty($credcaseKeyResponse->getUpdatedAt()) ? null : $credcaseKeyResponse->getUpdatedAt());
        $key->setExpiredAt(empty($credcaseKeyResponse->getExpiredAt()) ? null : $credcaseKeyResponse->getExpiredAt());
        $secret = $credcaseKeyResponse->getSecret();
        if(!empty($secret) && strlen($secret) === Entity::SECRET_LENGTH){
            $key->encryptAndSetSecret($credcaseKeyResponse->getSecret());
        }
        return $key;
    }

    public function transformToApiKeyResponse(\Rzp\Credcase\Apikey\V1\ApiKeyCreateResponse $credcaseKeyResponse): Entity
    {
        $key = new Entity;
        $key->setId(preg_replace('/^rzp_(test|live)_/', '', $credcaseKeyResponse->getId()));
        $key->setMerchantId($credcaseKeyResponse->getOwnerId());
        $key->setCreatedAt($credcaseKeyResponse->getCreatedAt());
        $key->setUpdatedAt($credcaseKeyResponse->getUpdatedAt());
        $key->setExpiredAt(empty($credcaseKeyResponse->getExpiredAt()) ? null : $credcaseKeyResponse->getExpiredAt());
        $key->encryptAndSetSecret($credcaseKeyResponse->getSecret());
        return $key;
    }

    public function transformToApiKeyResponseNoSecret(\Rzp\Credcase\Apikey\V1\ApiKeyResponse $credcaseKeyResponse): Entity
    {
        $key = new Entity;
        $key->setId(preg_replace('/^rzp_(test|live)_/', '', $credcaseKeyResponse->getId()));
        $key->setMerchantId($credcaseKeyResponse->getOwnerId());
        $key->setCreatedAt($credcaseKeyResponse->getCreatedAt());
        $key->setUpdatedAt($credcaseKeyResponse->getUpdatedAt());
        $key->setExpiredAt(empty($credcaseKeyResponse->getExpiredAt()) ? null : $credcaseKeyResponse->getExpiredAt());
        return $key;
    }

    public function transformToRotateApiKeyResponse(\Rzp\Credcase\Apikey\V1\ApiKeyRotateResponse $credcaseKeyResponse): array
    {
        $oldKey = $this->transformToApiKeyResponseNoSecret($credcaseKeyResponse->getOldKey());
        $newKey = $this->transformToApiKeyResponse($credcaseKeyResponse->getNewKey());
        $keysData['old'] = $oldKey->toArrayPublic();

        $keysData['new'] = $newKey->toArrayPublicWithSecret();

        return $keysData;
    }
}
