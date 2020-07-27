<?php


namespace RZP\Models\Terminal;

use App;
use RZP\Exception;
use RZP\Models\Merchant;
use RZP\Models\Terminal;
use RZP\Trace\TraceCode;

trait Migrate
{

    /**
     * @param bool $shouldSync
     * @return bool
     * order of preference in deciding whether the terminals should be synced or not
     * 1) shouldSync
     * 2) razorx feature called "TerminalsService_MigrateTerminal"
     */
    public static function shouldMigrateTerminal(bool $shouldSync) : bool
    {
        if ($shouldSync === false)
        {
            return false;
        }

        return self::getRazorxTreatment(self::getMigrateTerminalFeature());
    }

    public static function shouldMigrateTerminalDelete(bool $shouldSync) : bool
    {
        if ($shouldSync === false)
        {
            return false;
        }

        return self::getRazorxTreatment(self::getMigrateDeleteTerminalFeature());
    }

    public static function shouldMigrateSubmerchant(): bool
    {
        return self::getRazorxTreatment(self::getMigrateSubmerchantFeature());
    }

    public static function shouldRunComparison(): bool
    {
        return self::getRazorxTreatment(self::getShouldRunComparisonFeature());
    }

    protected static function getMigrateTerminalFeature(): string
    {
        return'TerminalsService_MigrateTerminal';
    }

    protected static function getMigrateDeleteTerminalFeature(): string
    {
        return'TerminalsService_MigrateDeleteTerminal';
    }

    protected static function getMigrateSubmerchantFeature(): string
    {
        return 'TerminalsService_MigrateSubmerchant';
    }

    protected static function getShouldRunComparisonFeature() : string
    {
        return 'TerminalsService_ShouldRunComparison';
    }


    protected static function getRazorxTreatment(string $feature): bool
    {
        $app = App::getFacadeRoot();

        $mode = $app['rzp.mode'] ?? \RZP\Constants\Mode::LIVE;

        $merchant = $app['basicauth']->getMerchant();

        $reqId = $app['request']->getId();

        if (isset($merchant) === true)
        {
            $reqId = $merchant->getId();
        }

        $variant = $app['razorx']->getTreatment($reqId, $feature, $mode);

        self::logRazorxResponse($feature, $variant);

        if ($variant === 'migrate')
        {
            return true;
        }

        return false;
    }

    protected static function logRazorxResponse(string $feature, string $variant)
    {
        $app = App::getFacadeRoot();

        $data = [
            'feature'   => $feature,
            'variant'   => $variant,
        ];

        $app['trace']->info(TraceCode::TERMINALS_SERVICE_RAZORX_RESPONSE, $data);
    }

    /**
     * This function fetches the terminal given in $terminal from terminals
     * It does a comparison. It logs the success/failure of the fetch and pushes metrics
     * BEWARE: it fails silently in case on any exception
     * @param Entity $terminal
     */
    public function runTerminalComparison(Entity $terminal)
    {

        $data[Entity::TERMINAL_ID] = $terminal->getId();
        // array sort, log mismatch, deactivated check

        try
        {
            $fetchedTerminal = $this->app['terminals_service']->fetchTerminalById($terminal->getId());

            $this->compareFetchedTerminal($terminal, $fetchedTerminal);

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

    public function compareFetchedTerminal(Entity $terminal, $fetchedTerminal)
    {
        $data = [
            'route' => $this->app['request.ctx']->getRoute(),
            'message' => null,
            Terminal\Entity::TERMINAL_ID => $terminal->getId(),
        ];

        if ($this->isMigrateTerminalSuccess($terminal, $fetchedTerminal, false) === true)
        {
            $this->pushTerminalsServiceMetrics(Metric::TERMINAL_FETCH_BY_ID_COMPARISON_SUCCESS, $data);
        }
        else
        {
            $data['message'] = 'field mismatch';

            $this->pushTerminalsServiceMetrics(Metric::TERMINAL_FETCH_BY_ID_COMPARISON_FAILURE, $data);
        }
    }

    public function isMigrateTerminalSuccess(Entity $terminal, $fetchTerminalResponse, bool $ignoreSecrets = false)
    {
        $isFetchTerminalSuccess = $this->isFetchTerminalFromTerminalsServiceSuccess($terminal, $fetchTerminalResponse, $ignoreSecrets);

        $areFetchedSubmerchantsSame = $this->areFetchedSubmerchantsSameForTerminal($terminal, $fetchTerminalResponse);

        return (($isFetchTerminalSuccess === true) and
                ($areFetchedSubmerchantsSame === true));
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
            $originalTerminalArray = $terminal->toArrayWithPassword();
        }

        $ignoreAttributes = [
            Entity::CREATED_AT,
            Entity::UPDATED_AT,
            Entity::SYNC_STATUS,
            Entity::SHARED,
            Entity::USED_COUNT,
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

            $this->trace->debug(TraceCode::TERMINALS_SERVICE_MERCHANT_TERMINAL_MISMATCH, $data);
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
            Entity::DIRECT
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
            $app = App::getFacadeRoot();

            $data = ["api"=>$apiResponse["id"], "terminal"=>$terminalResponse["id"]];

            $mismatchData["id"] = $data;

            $app['trace']->info(TraceCode::TERMINALS_SERVICE_PROXY_TERMINAL_MISMATCH, $mismatchData);

            return false;
        }

        return true;
    }

    public function compareArrayOfTerminalArrays(array $apiResponse, array $terminalResponse)
    {
        if (count($apiResponse) !== count($terminalResponse)){
            $app = App::getFacadeRoot();

            $traceData = ["api_count"=> count($apiResponse), "terminals_count" => count($terminalResponse)];

            $app['trace']->info(TraceCode::TERMINALS_SERVICE_PROXY_TERMINAL_MISMATCH_COUNT, $traceData);

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


}

