<?php


namespace RZP\Models\Terminal;

use RZP\Exception;
use RZP\Models\Merchant;
use RZP\Models\Terminal;
use RZP\Trace\TraceCode;
use RZP\Models\Base\Collection;
use RZP\Models\Base\PublicCollection;

trait Migrate
{
    /**
     * This function fetches the terminal given in $terminal from terminals
     * It does a comparison. It logs the success/failure of the fetch and pushes metrics
     * BEWARE: it fails silently in case on any exception
     * @param Entity $terminal
     */
    public function runTerminalComparison(Entity $terminal, bool $compareSubmerchant = false)
    {

        $data[Entity::TERMINAL_ID] = $terminal->getId();
        // array sort, log mismatch, deactivated check

        try
        {
            $fetchedTerminal = $this->app['terminals_service']->fetchTerminalById($terminal->getId());

            $this->compareFetchedTerminal($terminal, $fetchedTerminal, $compareSubmerchant);

        }
        catch (\Exception $exception)
        {
            $data['message'] = $exception->getMessage();

            $this->pushTerminalsServiceMetrics(Metric::TERMINAL_FETCH_FAILURE, $data);

        }
        catch (\Throwable $throwable)
        {
            $data['message'] = $throwable->getMessage();

            $this->pushTerminalsServiceMetrics(Metric::TERMINAL_FETCH_FAILURE, $data);
        }
    }

    public function pushTerminalsServiceMetrics(string $metric, array $data = [])
    {
        $default = [
            'route' => $this->app['request.ctx']->getRoute(),
            'message' => null,
        ];

        $data = array_merge($data, $default);

        $this->app['trace']->count($metric, $data);
    }

    public function pushTerminalReadJoinMetrics(string $function)
    {
        $metricData = [
            'route' => $this->app['request.ctx']->getRoute(),
            "function" => $function,
            "isJoin"=> 'true'
        ];

        $this->trace->count(Terminal\Metric::TERMINAL_REPO_READ, $metricData);

    }

    public function compareFetchedTerminal(Entity $terminal, $fetchedTerminal, $compareSubmerchant)
    {
        $data = [
            'route' => $this->app['request.ctx']->getRoute(),
            'message' => null,
            Terminal\Entity::TERMINAL_ID => $terminal->getId(),
        ];

        if ($this->isMigrateTerminalSuccess($terminal, $fetchedTerminal, true, $compareSubmerchant) === true)
        {
            $this->pushTerminalsServiceMetrics(Metric::TERMINAL_FETCH_BY_ID_COMPARISON_SUCCESS, $data);
        }
        else
        {
            $data['message'] = 'field mismatch';

            $this->pushTerminalsServiceMetrics(Metric::TERMINAL_FETCH_BY_ID_COMPARISON_FAILURE, $data);
        }
    }

    public function isMigrateTerminalSuccess(Entity $terminal, $fetchTerminalResponse, bool $ignoreSecrets = false, bool $compareSubmerchant = true)
    {
        $isFetchTerminalSuccess = $this->isFetchTerminalFromTerminalsServiceSuccess($terminal, $fetchTerminalResponse, $ignoreSecrets);

        return $isFetchTerminalSuccess;
    }

    public function isFetchTerminalFromTerminalsServiceSuccess(Entity $terminal, $fetchTerminalResponse, $ignoreSecrets = False): bool
    {
        $success = true;

        if ($ignoreSecrets === true)
        {
            $originalTerminalArray = $terminal->toArray();
        }
        else
        {
            $originalTerminalArray = $terminal->toArrayWithPassword(false);
        }

        $ignoreAttributes = [
            Entity::CREATED_AT,
            Entity::UPDATED_AT,
            Entity::SYNC_STATUS,
            Entity::SHARED,
            Entity::USED_COUNT,
            Entity::NOTES,
            Entity::FPX,
            ];

        foreach (array_keys($originalTerminalArray) as $attribute)
        {

            if (array_search($attribute, $ignoreAttributes) !== false)
            {
                continue;
            }

            $originalValue = $originalTerminalArray[$attribute];


            if (array_key_exists($attribute, $fetchTerminalResponse) === true)
            {
                $responseValue = $fetchTerminalResponse[$attribute];
            }
            else
            {
                $responseValue = '';
            }

            if (is_array($originalValue) === true)
            {
                $originalValue = $originalValue ?? [];

                $responseValue = $responseValue ?? [];

                sort($originalValue);

                sort($responseValue);
            }

            if ($originalValue != $responseValue)
            {
                $data = [
                    Entity::TERMINAL_ID => $terminal->getId(),
                    'attribute' => $attribute,
                ];

                $this->trace->debug(TraceCode::TERMINALS_SERVICE_MIGRATE_FIELD_MISMATCH, $data);

                $success = false;
            }
        }
        return $success;
    }

    protected function areFetchedSubmerchantsSameForTerminal(Entity $terminal, $fetchTerminalResponse): bool
    {

        $terminalSubmerchantsIds = array_map(function ($submerchant) {
            return $submerchant[Merchant\Entity::ID];
        }, $terminal->merchants()->get([Terminal\Entity::ID])->toArray());

        sort($terminalSubmerchantsIds);

        $fetchedTerminalSubmerchantIds = $fetchTerminalResponse[Terminal\Entity::SUB_MERCHANTS] ?? [];

        sort($fetchedTerminalSubmerchantIds);

        $success =  $terminalSubmerchantsIds === $fetchedTerminalSubmerchantIds;

        if ($success === false)
        {
            $data = [
                'original'  => $terminalSubmerchantsIds,
                'fetched'   => $fetchedTerminalSubmerchantIds,
            ];

            $this->trace->debug(TraceCode::TERMINALS_SERVICE_TERMINAL_SUBMERCHANT_MISMATCH, $data);
        }

        return $success;

    }

    protected function compareFetchedTerminalIds($terminals, $fetchedTerminals) : bool
    {
        $fetchedTerminalIds = [];

        foreach ($fetchedTerminals as $fetchedTerminal)
        {
            $fetchedTerminalIds[] = $fetchedTerminal[Entity::ID];
        }

        $fetchedTerminalIds= array_values($fetchedTerminalIds);

        $terminalIds = [];

        foreach ($terminals as $terminal)
        {
            if ($terminal->getStatus() === Status::ACTIVATED)
            {
                $terminalIds[] = $terminal->getId();
            }
        }

        $terminalIds = array_values($terminalIds);

        sort($fetchedTerminalIds);

        sort($terminalIds);

        if ($fetchedTerminalIds !== $terminalIds)
        {

            $data = [
                'terminal_ids'              => $terminalIds,
                'fetched_terminal_ids'      => $fetchedTerminalIds,
            ];

            $this->pushTerminalsServiceMetrics(Metric::TERMINAL_FETCH_BY_MERCHANT_ID_TERMINAL_ID_MISMATCH);

            $this->trace->debug(TraceCode::TERMINALS_SERVICE_FETCH_BY_MERCHANT_ID_MISMATCH, $data);

            return false;
        }

        return true;
    }

    protected function processMigrateTerminalSuccess(Entity $terminal)
    {
        $options = [
            'shouldSync' => false,
        ];

        $terminal->setSyncStatus(SyncStatus::SYNC_SUCCESS);

        $data = ["terminal_id" => $terminal->getId()];

        $this->trace->info(TraceCode::TERMINALS_SERVICE_SYNC_SUCCESS, $data);

        $this->repo->terminal->saveOrFail($terminal, $options);
    }

    protected function processMigrateTerminalFailure(Entity $terminal)
    {
        $terminal->setSyncStatus(SyncStatus::SYNC_FAILED);

        $data = ["terminal_id" => $terminal->getId()];

        $this->trace->info(TraceCode::TERMINALS_SERVICE_SYNC_FAILED, $data);

        throw new Exception\IntegrationException('terminals service field mismatch');
    }

    public function compareTerminalArray(array $apiResponse, array $terminalResponse)
    {
        $ignoreAttributes = [
            Entity::SUB_MERCHANTS,
            Entity::ADMIN,
            Entity::CREATED_AT,
            Entity::UPDATED_AT,
            Entity::DELETED_AT,
            Entity::SYNC_STATUS,
            Entity::SHARED,
            Entity::USED_COUNT,
            Entity::DIRECT,
            Entity::MPAN
        ];

        $mismatchData = [];

        foreach (array_keys($apiResponse) as $attribute)
        {
            if (array_search($attribute, $ignoreAttributes) !== false)
            {
                continue;
            }

            $originalValue = $apiResponse[$attribute];

            if (array_key_exists($attribute, $terminalResponse) === true)
            {
                $responseValue = $terminalResponse[$attribute];
            }
            else
            {
                $responseValue = '';
            }

            if (is_array($originalValue) === true)
            {
                $originalValue = $originalValue ?? [];

                $responseValue = $responseValue ?? [];

                sort($originalValue);

                sort($responseValue);
            }

            if ($originalValue != $responseValue)
            {
                $data = [];

                if ($attribute !== Entity::MPAN)
                {
                    $data = ["api" => $originalValue, "terminal" => $responseValue];
                }
                else
                {
                    $data = ["api" => "mpan_mismatch", "terminal" => "mpan_mismatch"];
                }

                $mismatchData[$attribute] = $data;
            }
        }

        if (count($mismatchData) > 0)
        {
            $app = \App::getFacadeRoot();

            $data = ["api"=>$apiResponse["id"], "terminal"=>$terminalResponse["id"]];

            $mismatchData["id"] = $data;

            $app['trace']->info(TraceCode::TERMINALS_SERVICE_PROXY_TERMINAL_MISMATCH, $mismatchData);

            return false;
        }
        $data = [
            'route' => $this->app['request.ctx']->getRoute(),
            'message' => null,
            Terminal\Entity::TERMINAL_ID => $terminalResponse[Entity::ID],
        ];

        $this->pushTerminalsServiceMetrics(Metric::TERMINAL_FETCH_BY_ID_COMPARISON_SUCCESS, $data);

        return true;
    }

    public function compareArrayOfTerminalArrays(array $apiResponse, array $terminalResponse)
    {
        if (count($apiResponse) !== count($terminalResponse)){
            $app = \App::getFacadeRoot();

            $traceData = ["api_count"=> count($apiResponse), "terminals_count" => count($terminalResponse)];

            $app['trace']->info(TraceCode::TERMINALS_SERVICE_PROXY_TERMINAL_MISMATCH_ARRAY_COUNT, $traceData);

            return false;
        }

        usort($apiResponse, function($a, $b) {
            return $a['id'] <=> $b['id'];
        });

        usort($terminalResponse, function($a, $b) {
            return $a['id'] <=> $b['id'];
        });

        $mismatchedIds = [];

        foreach ($apiResponse as $index => $value){
            if ($this->compareTerminalArray($value, $terminalResponse[$index]) === false)
            {
                $mismatchedIds[] = $value["id"];
            }
        }

        if (count($mismatchedIds) > 0)
        {
            return false;
        }

        return true;
    }

    public static function compareTerminalEntity(Entity $apiEntity, Entity $terminalEntity, $compareMethods = null)
    {
        $mismatchData = [];

        // not comparing orgId
        $methods = ["getId", "getMerchantId", "getCategory", "getGateway", "getGatewayAcquirer", "getGatewayMerchantId",
                    "getGatewayMerchantId2", "getGatewayTerminalId", "getGatewayAccessCode", "getProcurer", "getVpa",
                    "getAccountType", "getMCMpan", "getRupayMpan", "getVisaMpan", "isEnabled", "getEnabledBanks","getTpv",
                    "isCardEnabled", "isNetbankingEnabled", "isEmiEnabled", "isUpiEnabled", "isOmnichannelEnabled", "isBankTransferEnabled",
                    "isAepsEnabled", "isEmandateEnabled", "isNachEnabled", "isCardlessEmiEnabled", "isCredEnabled", "isPayLaterEnabled",
                    "isExpected", "isBankingTypeBoth","getType", "isInternational", "getAccountNumber", "getIfscCode",
                    "getVirtualUpiRoot", "getVirtualUpiMerchantPrefix", "getVirtualUpiHandle", "getCapability", "getCurrency",
                    "getNetworkCategory", "isExpected", "getCapability", "getMode", "getEmiDuration"];

        if ($compareMethods == null)
        {
            $compareMethods = $methods;
        }

        foreach (array_values($compareMethods) as $methodName)
        {
            if ($apiEntity->$methodName() != $terminalEntity->$methodName())
            {
                $data = ["method" => $methodName, "api" => $apiEntity->$methodName(), "terminals" => $terminalEntity->$methodName()];

                $mismatchData[] = $data;
            }
        }
        if (count($mismatchData) > 0)
        {
            $app = \App::getFacadeRoot();

            $data = ["attribute" => "id", "api" => $apiEntity->getId(), "terminals" => $terminalEntity->getId()];

            $mismatchData[] = $data;

            $app['trace']->info(TraceCode::TERMINALS_SERVICE_PROXY_TERMINAL_MISMATCH, $mismatchData);

            return false;
        }

        return true;
    }

    public static function getEntityFromTerminalServiceResponse(array $t)
    {
        $finalArray = [];

        $ignoreAttributes = ["id", "updated_at", "created_at"];

        foreach (array_keys($t) as $attribute)
        {
            if (array_search($attribute, $ignoreAttributes) !== false)
            {
                continue;
            }

            if (($t[$attribute] === null) or ($t[$attribute] === ""))
            {
                continue;
            }

            $finalArray[$attribute] = $t[$attribute];
        }

        if (empty($finalArray["type"]) === false)
        {
            foreach (array_values($finalArray["type"]) as $value)
            {
                $formattedType[$value] = "1";
            }

            $finalArray["type"] = $formattedType;
        }
        $terminal = (new Entity)->buildFromTerminalServiceResponse($finalArray);

        $terminal->setId($t["id"]);

        $terminal->setMerchantId($t["merchant_id"]);


        if (array_key_exists("enabled",$t) === true)
        {
            $terminal->setEnabled($t["enabled"]);
        }

        if (array_key_exists("status",$t) === true)
        {
            $terminal->setStatus($t["status"]);
        }

        if (array_key_exists("created_at",$t) === true)
        {
            $terminal->setCreatedAt($t["created_at"]);
        }

        if (array_key_exists("updated_at",$t) === true)
        {
            $terminal->setUpdatedAt($t["updated_at"]);
        }

        if (array_key_exists("deleted_at",$t) === true)
        {
            $terminal->setDeletedAt($t["deleted_at"]);
        }

        if (array_key_exists("direct",$t) === true)
        {
            $terminal->setDirectForMerchant($t["direct"]);
        }

        // mode and type has defined modifiers, need to overwrite it if data present
        if (array_key_exists("mode",$t) === true)
        {
            $terminal->setMode($t["mode"]);
        }

        if (array_key_exists("type", $finalArray) === true)
        {
           $terminal->setType($finalArray["type"]);
        }

        $terminal->syncEntity();

        return $terminal;
    }

    public static function getEntityCollectionFromTerminalServiceResponse(array $response): PublicCollection
    {
        $terminals = [];

        foreach ($response as $value)
        {
            $terminal = self::getEntityFromTerminalServiceResponse($value);

            $terminals[] = $terminal;
        }

        $collection = new PublicCollection($terminals);

        return $collection;
    }

    public static function getEntityArrayFromTerminalServiceResponse(array $response): array
    {
        $terminals = [];

        foreach ($response as $value)
        {
            $terminal = self::getEntityFromTerminalServiceResponse($value);

            $terminals[] = $terminal;
        }

        return $terminals;
    }

    public static function compareTerminalCollection(PublicCollection $apiTerminals, PublicCollection $terminals, $compareMethods = null): bool
    {
        $app = \App::getFacadeRoot();

        $data = [];

        if ($apiTerminals->count() != $terminals->count())
        {
            $data["api_count"] = $apiTerminals->count();

            $data["terminals_count"] = $terminals->count();

            $app['trace']->info(TraceCode::TERMINALS_SERVICE_PROXY_TERMINAL_MISMATCH_COUNT, $data);

            return false;
        }

        $apiSorted = $apiTerminals->sortBy('id')->values();

        $terminalSorted = $terminals->sortBy('id')->values();


        $apiTerminalIds = $apiTerminals->pluck('id')->all();

        $tsTerminalIds = $terminalSorted->pluck('id')->all();

        $apiDiff = array_diff($apiTerminalIds, $tsTerminalIds);
        $tsDiff = array_diff($tsTerminalIds, $apiTerminalIds);

        $isEqual = true;

        if(empty($apiDiff) === false)
        {
            $app['trace']->info(TraceCode::TERMINALS_SERVICE_PROXY_TERMINAL_MISMATCH_IDS, ['api_terminal_ids' => $apiTerminalIds, 'ts_terminal_ids' => $tsTerminalIds,
            'api_diff' => $apiDiff, 'ts_diff' => $tsDiff]);

            $isEqual = false;
        }

        $countApi = $apiTerminals->count();

        $countTs = $terminals->count();

        // Algorithm is O(n*n) but its fine as n will not be too large
        for ($x = 0; $x < $countApi; $x++) {

            $item = $apiSorted[$x];
            for ($y = 0; $y < $countTs; $y++)
            {
                $itemToCompare = $terminalSorted[$y];

                if($item->getId() === $itemToCompare->getId())
                {
                    $isEntityEqual = self::compareTerminalEntity($item, $itemToCompare, $compareMethods);

                    if ($isEntityEqual === false)
                    {
                        $isEqual = false;
                    }
                }

            }
        }

        return $isEqual;
    }

    public static function getTerminalServiceRequestFromParam(array $terminalData): array
    {
        $content = [];

        if (empty($terminalData["merchant_id"]) === false)
        {
            $content["merchant_ids"] = [$terminalData["merchant_id"]];
        }

        if (empty($terminalData["gateway"]) === false)
        {
            $content["gateway"] = $terminalData["gateway"];
        }

        if (empty($terminalData["procurer"]) === false)
        {
            $content["procurer"] = $terminalData["procurer"];
        }

        if (empty($terminalData["gateway_acquirer"]) === false)
        {
            $content["gateway_acquirer"] = $terminalData["gateway_acquirer"];
        }

        if (empty($terminalData["status"]) === false)
        {
            $content["status"] = $terminalData["status"];
        }

        if (empty($terminalData["org_id"]) === false)
        {
            $content["org_id"] = $terminalData["org_id"];
        }

        if (empty($terminalData["enabled"]) === false)
        {
            $content["enabled"] = true;

            if ($terminalData["enabled"] == false)
            {
                $content["enabled"] = false;
            }
        }

        $identifiers = [];

        if (array_key_exists('gateway_merchant_id', $terminalData) === true)
        {
            $identifiers["gateway_merchant_id"] = $terminalData["gateway_merchant_id"];
        }
        if (array_key_exists('gateway_merchant_id2', $terminalData) === true)
        {
            $identifiers["gateway_merchant_id2"] = $terminalData["gateway_merchant_id2"];
        }
        if (array_key_exists('gateway_terminal_id', $terminalData) === true)
        {
            $identifiers["gateway_terminal_id"] = $terminalData["gateway_terminal_id"];
        }
        if (array_key_exists('gateway_terminal_id2', $terminalData) === true)
        {
            $identifiers["gateway_terminal_id2"] = $terminalData["gateway_terminal_id2"];
        }
        if (array_key_exists('vpa', $terminalData) === true)
        {
            $identifiers["vpa"] = $terminalData["vpa"];
        }

        $mpans = [];

        if (empty($terminalData["mc_mpan"]) === false)
        {
            $mpans["mc_mpan"] = $terminalData["mc_mpan"];
        }
        if (empty($terminalData["visa_mpan"]) === false)
        {
            $mpans["visa_mpan"] = $terminalData["visa_mpan"];
        }
        if (empty($terminalData["rupay_mpan"]) === false)
        {
            $mpans["rupay_mpan"] = $terminalData["rupay_mpan"];
        }

        if (count($mpans) > 0)
        {
            $identifiers["mpans"] = $mpans;
        }

        if (count($identifiers) > 0)
        {
            $content["identifiers"] = $identifiers;
        }

        return $content;
    }

}
