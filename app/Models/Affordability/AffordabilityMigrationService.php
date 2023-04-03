<?php

namespace RZP\Models\Affordability;

use ApiResponse;
use App;
use DB;
use Mail;
use Cache;
use Config;
use Request;
use RZP\Models\Emi\CardlessEmiProvider;
use RZP\Models\Emi\CreditEmiProvider;
use RZP\Models\Emi;
use RZP\Models\Emi\PaylaterProvider;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Terminal;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Methods;
use Razorpay\Trace\Logger as Trace;

class AffordabilityMigrationService extends Base\Service
{
    const defaultCreditEmiProviders = [

        CreditEmiProvider::HDFC => 1,
        CreditEmiProvider::UTIB => 1,
        CreditEmiProvider::ICIC => 1,
        CreditEmiProvider::AMEX => 1,
        CreditEmiProvider::BARB => 1,
        CreditEmiProvider::CITI => 1,
        CreditEmiProvider::HSBC => 1,
        CreditEmiProvider::INDB => 1,
        CreditEmiProvider::KKBK => 1,
        CreditEmiProvider::RATN => 1,
        CreditEmiProvider::SCBL => 1,
        CreditEmiProvider::YESB => 1,
        CreditEmiProvider::ONECARD => 1,

    ];
    const defaultCardlessEmiProviders = [

        CardlessEmiProvider::EARLYSALARY  => 1,

    ];
    const defaultPaylaterProviders =[

        PaylaterProvider::ICIC => 1,

    ];


    // redis key format: affordability:last_created_at_<isPaylater>_<isCardlessEmi>_<isCreditEmi>
    const REDIT_KEY_LAST_MIGRATED_MERCHANT_PHASE_1 = 'affordability:last_created_at_%s_%s_%s';

    // redis key format: affordability:last_created_at_<Method>
    const REDIT_KEY_LAST_MIGRATED_MERCHANT_PHASE_2 = 'affordability:last_created_at_%s';

    // redis key format: affordability:run_in_progress_<isPaylater>_<isCardlessEmi>_<isCreditEmi>
    const REDIS_KEY_PREVIOUS_RUN_IN_PROGRESS_PHASE1 = 'affordability:run_in_progress_%s_%s_%s';

    // redis key format: affordability:run_in_progress_<Method>
    const REDIS_KEY_PREVIOUS_RUN_IN_PROGRESS_PHASE2 = 'affordability:run_in_progress_%s';

    // redis key format: affordability:run_in_progress_<Method>_<Instrument>
    const REDIS_KEY_PREVIOUS_RUN_IN_PROGRESS_INSTRUMENT = 'affordability:run_in_progress_%s_%s';

    // redis key format: affordability:last_created_at_<Method>_<Instrument>
    const REDIT_KEY_LAST_MIGRATED_MERCHANT_INSTRUMENT = 'affordability:last_created_at_%s_%s';

    // redis key format: affordability:sub_merchants_data_cached
    const REDIS_KEY_SUB_MERCHANTS_DATA_CACHED = 'affordability:sub_merchants_data_cached_%s';

    // redis key format: affordability:sub_merchants_data_cached_<Method>
    const REDIS_KEY_SUB_MERCHANTS_DATA = 'affordability:sub_merchants_data_%s';

    // 60 days = 60 * 24 * 60 * 60 = 5184000
    const REDIS_KEY_TTL = 5184000;


    public function getCreditEmiProviders($terminals)
    {

        $allCreditEmiProviders = Emi\CreditEmiProvider::getAllCreditEmiProviders();
        $creditEmiProviders = [];

        foreach($allCreditEmiProviders as $instrument)
        {
            if($instrument !== 'BAJAJ'  and $instrument !== 'SBIN')
            {
                $creditEmiProviders[$instrument] = '1';
            }
        }


        foreach ($terminals as $terminal)
        {

            $gateway = $terminal['gateway'];
            $emi = $terminal['emi'];

            if(($gateway === 'bajajfinserv') and ($emi === true))
            {
                $creditEmiProviders['BAJAJ'] = '1';
            }

            else if(($gateway === 'emi_sbi'))
            {
                $creditEmiProviders['SBIN'] = '1';
            }

        }

        return $creditEmiProviders;

    }

    public function getCardlessEmiProviderMap($terminals)
    {
        $cardlessEmiProviders = [];

        $cardlessEmiProviders['earlysalary'] = '1';

        $gatewayAcquirers = [];

        foreach ($terminals as $terminal)
        {

            $terminalProviders = [];

            if( $terminal[Terminal\Entity::CARDLESS_EMI] == 0)
            {
                continue;
            }

            if ((Payment\Processor\CardlessEmi::isMultilenderProvider($terminal[Terminal\Entity::GATEWAY_ACQUIRER])) and
                (empty($terminal[Terminal\Entity::ENABLED_BANKS]) === false))
            {
                $terminalProviders = array_map('strtolower', $terminal[Terminal\Entity::ENABLED_BANKS]);
            }

            else
            {
                $terminalProviders[] = $terminal[Terminal\Entity::GATEWAY_ACQUIRER];
            }

            $gatewayAcquirers = array_unique(array_merge($gatewayAcquirers,$terminalProviders));
        }

        foreach($gatewayAcquirers as $gatewayAcquirer)
        {
            $cardlessEmiProviders[$gatewayAcquirer] = '1';
        }

        return $cardlessEmiProviders;


    }

    public function getPaylaterProviderMap($terminals)
    {

        $paylaterProviders = [];

        $paylaterProviders['icic'] = '1';

        $gatewayAcquirers = [];

        foreach ($terminals as $terminal)
        {

            $terminalProviders = [];

            if( $terminal[Terminal\Entity::PAYLATER] == 0)
            {
                continue;
            }

            if ((Payment\Processor\PayLater::isMultilenderProvider($terminal[Terminal\Entity::GATEWAY_ACQUIRER])) and
                (empty($terminal[Terminal\Entity::ENABLED_BANKS]) === false))
            {
                $terminalProviders = array_map('strtolower', $terminal[Terminal\Entity::ENABLED_BANKS]);
            }

            else
            {
                $terminalProviders[] = $terminal[Terminal\Entity::GATEWAY_ACQUIRER];
            }

            $gatewayAcquirers = array_unique(array_merge($gatewayAcquirers,$terminalProviders));
        }

        foreach($gatewayAcquirers as $gatewayAcquirer)
        {
            $paylaterProviders[$gatewayAcquirer] = '1';
        }

        return $paylaterProviders;

    }

    public function migrateAffordabilityMethodsforMerchant($merchantId , $method)
    {

        $finalInput = [];

        $terminals = $this->app['repo']->terminal->getEnabledTerminalsByMerchantId($merchantId);
        $terminals = $terminals->toArray();

        if ($method->isCreditEmiEnabled() == true) {
            $finalInput[Methods\Entity::CREDIT_EMI_PROVIDERS] = $this->getCreditEmiProviders($terminals);
        }
        if ($method->isCardlessEmiEnabled() == true) {
            $finalInput[Methods\Entity::CARDLESS_EMI_PROVIDERS] = $this->getCardlessEmiProviderMap($terminals);
        }
        if ($method->isPaylaterEnabled() == true) {
            $finalInput[Methods\Entity::PAYLATER_PROVIDERS] = $this->getPaylaterProviderMap($terminals);
        }

        if($finalInput != [])
        {
            $method->setMethods($finalInput);

            $this->repo->saveOrFail($method);

        }

    }

    public function getRequestBodyToUpdateInstrument($terminal)
    {
        $updatedInstruments = [];

        $terminalGateway = $terminal->getGateway();

        if ($terminalGateway == 'paylater')
        {
            $updatedInstruments  = [
                'paylater_providers' => []
            ];

            $gatewayAcquirer = $terminal->getGatewayAcquirer();

            if($gatewayAcquirer == 'flexmoney')
            {
                $enabledBanks = $terminal->getEnabledBanks();

                foreach($enabledBanks as $enabledBank)
                {
                    if(in_array(strtolower($enabledBank), (new Emi\PaylaterProvider)::getAllPaylaterProviders(), true) == true)
                    {
                        $updatedInstruments['paylater_providers'][strtolower($enabledBank)] = '1';
                    }

                }
            }

            else
            {
                if(in_array($gatewayAcquirer, (new Emi\PaylaterProvider)::getAllPaylaterProviders(), true) == true)
                {
                    $updatedInstruments['paylater_providers'][$gatewayAcquirer] = '1';
                }

            }
        }

        else if($terminalGateway == 'cardless_emi')
        {
            $updatedInstruments  = [
                'cardless_emi_providers' => []
            ];

            $gatewayAcquirer = $terminal->getGatewayAcquirer();

            if($gatewayAcquirer == 'flexmoney')
            {
                $enabledBanks = $terminal->getEnabledBanks();
                foreach($enabledBanks as $enabledBank)
                {
                    if(in_array(strtolower($enabledBank), (new Emi\CardlessEmiProvider())::getAllCardlessEmiProviders(), true) == true)
                    {
                        $updatedInstruments['cardless_emi_providers'][strtolower($enabledBank)] = '1';
                    }
                }
            }
            else
            {
                if(in_array($gatewayAcquirer, (new Emi\CardlessEmiProvider())::getAllCardlessEmiProviders(), true) == true)
                {
                    $updatedInstruments['cardless_emi_providers'][$gatewayAcquirer] = '1';
                }

            }

        }

        else if($terminalGateway == 'emi_sbi')
        {
            $updatedInstruments  = [
                'credit_emi_providers' => [
                    'SBIN' => '1'
                ]
            ];

        }

        else if($terminalGateway == 'bajajfinserv')
        {
            $updatedInstruments  = [
                'credit_emi_providers' => [
                    'BAJAJ' => '1'
                ]
            ];
        }

        return $updatedInstruments;
    }

    public function getRequestBodyToUpdateSpecificInstrument($gateway,$instrument)
    {
        $updatedInstruments = [];

        if ($gateway == 'paylater')
        {
            $updatedInstruments  = [
                'paylater_providers' => [
                    $instrument => '1'
                ]
            ];

        }

        else if($gateway == 'cardless_emi')
        {
            $updatedInstruments  = [
                'cardless_emi_providers' => [
                    $instrument => '1'
                ]
            ];
        }

        else if($gateway == 'credit_emi')
        {
            $updatedInstruments  = [
                'credit_emi_providers' => [
                    $instrument => '1'
                ]
            ];

        }

        return $updatedInstruments;
    }

    public function updateMerchantsForDedicatedTerminals($terminal)
    {

        $subMerchantsTerminalsRedisKey = sprintf(self::REDIS_KEY_SUB_MERCHANTS_DATA, $terminal->getId());
        $subMerchantsTerminals =  $this->app->cache->get($subMerchantsTerminalsRedisKey);
        $merchantIds = [];


        if($subMerchantsTerminals != null)
        {
            $merchantIds = $subMerchantsTerminals;
        }

        $merchantIds[] =  $terminal->getMerchantId();

        $methods = $this->repo->useSlave(function() use ($merchantIds)
        {
            return $this->repo->methods->fetchMethodsBasedOnMerchantIds($merchantIds);
        });

        $updatedInstruments = $this->getRequestBodyToUpdateInstrument($terminal);

        if($updatedInstruments != [])
        {
            foreach($methods as $method)
            {
                try
                {
                    $method->setMethods($updatedInstruments);

                    $this->repo->saveOrFail($method);

                }
                catch(\Throwable $ex)
                {
                    $data = ["merchant_id" => $method->getMerchantId() , "terminal_id" => $terminal->getId()];

                    $this->trace->traceException($ex,
                        Trace::ERROR,
                        TraceCode::UPDATE_METHOD_FAILED,
                        $data);

                }

            }

        }

    }

    public function updateMerchantsForSpecificInstruments($inputFrom,$method,$instrument,$count)
    {

        $redisKey = sprintf(self::REDIT_KEY_LAST_MIGRATED_MERCHANT_INSTRUMENT, $method, $instrument);

        if($inputFrom != null)
        {
            $from = $inputFrom;
        }
        else
        {
            $from =  $this->app->cache->get($redisKey);

            if($from == null)
            {
                $from = 0;
            }
        }

        $createdAt = null;

        $methods = $this->repo->useSlave(function() use ($method,$from,$count)
        {
            return $this->repo->methods->fetchMethodsBasedOnMethodName($method,$from,$count);
        });

        $updatedInstruments = $this->getRequestBodyToUpdateSpecificInstrument($method,$instrument);

        $successCount = 0;
        $failureCount = 0;
        $totalCount = 0;
        $success = [];
        $failure = [];

        if($updatedInstruments != [])
        {
            foreach($methods as $method)
            {

                $totalCount++;

                try
                {
                    $method->setMethods($updatedInstruments);

                    $this->repo->saveOrFail($method);

                    $createdAt = $method->getCreatedAt();

                    $success[] = $method->getMerchantId();

                    $successCount++;

                }
                catch(\Throwable $ex)
                {
                    $data = ["merchant_id" => $method->getMerchantId()];

                    $failure[] = $method->getMerchantId();

                    $failureCount++;

                    $this->trace->traceException($ex,
                        Trace::ERROR,
                        TraceCode::UPDATE_METHOD_FAILED,
                        $data);

                }

            }

        }

        $this->trace->info(
            TraceCode::SUCCESS_MERCHANTS,
            $success
        );

        $this->trace->info(
            TraceCode::FAILED_MERCHANTS,
            $failure
        );

        $this->app->cache->set($redisKey, $createdAt, self::REDIS_KEY_TTL);

        $res = [
            'from' => $from,
            'total count' => $totalCount,
            'success count' => $successCount,
            'failed count' => $failureCount,
        ];

        return $res;

    }

    public function updateMerchantsForSharedTerminals($isPaylater,$isCardlessEmi,$isCreditEmi,$count,$inputFrom)
    {

        $redisKey = sprintf(self::REDIT_KEY_LAST_MIGRATED_MERCHANT_PHASE_1, $isPaylater,$isCardlessEmi,$isCreditEmi);

        if($inputFrom != null)
        {
            $from = $inputFrom;
        }
        else
        {
            $from =  $this->app->cache->get($redisKey);

            if($from == null)
            {
                $from = 0;
            }
        }


        // fetch methods from slave, method value as null
        $methods = $this->repo->useSlave(function() use ($count,$isPaylater,$isCardlessEmi,$isCreditEmi,$from)
        {
            return $this->repo->methods->fetchBasedOnAffordabilityMethods($count,$isPaylater,$isCardlessEmi,$isCreditEmi,$from);

        });

        $merchants = [];
        $skippedMerchants = [];

        $createdAt = null;

        foreach ($methods as $method)
        {

            $addonMethods = $method->getAddonMethods();

            if($addonMethods != null)
            {
                $skippedMerchants[] = $method->getMerchantId();
            }
            else
            {
                $merchants[] = $method->getMerchantId();
            }

            $createdAt = $method->getCreatedAt();
        }

        try
        {

            $updatedMethods = [];

            if($isPaylater == 1)
            {
                $updatedMethods[Methods\Entity::PAYLATER] = self::defaultPaylaterProviders;
            }

            if($isCardlessEmi == 1)
            {
                $updatedMethods[Methods\Entity::CARDLESS_EMI] = self::defaultCardlessEmiProviders;
            }

            if($isCreditEmi == 1)
            {
                $updatedMethods[Methods\Entity::CREDIT_EMI] = self::defaultCreditEmiProviders;
            }

            $this->repo->methods->bulkUpdateAddonMethodsForMerchants($merchants,$updatedMethods);

            $this->app->cache->set($redisKey, $createdAt, self::REDIS_KEY_TTL);

            $this->trace->info(
                TraceCode::SKIPPED_MERCHANTS,
                $skippedMerchants
            );

            $this->trace->info(
                TraceCode::UPDATE_METHOD_RESPONSE,
                $merchants
            );

            $res = [
                'from' => $from,
                'success' => $count,
                'skipped' => $skippedMerchants
            ];

            return $res;

        }

        catch(\Throwable $ex)
        {

            $this->trace->traceException($ex,
                Trace::ERROR,
                TraceCode::UPDATE_METHOD_FAILED,['from' => $from]);

            $res = [
                'from' => $from,
                'failure' => $count,
                'skipped' => $skippedMerchants
            ];

            return $res;

        }

    }

    public function migrateInstrumentsOnDedicatedTerminals($methodName,$count,$inputFrom)
    {
        $subMerchantsRedisKey = sprintf(self::REDIS_KEY_SUB_MERCHANTS_DATA_CACHED,$methodName);


        $subMerchantsCached =  $this->app->cache->get($subMerchantsRedisKey);

        if($subMerchantsCached == null)
        {

            $subMerchants = (new Terminal\Repository)->getSubMerchantsTerminalsforAffordabilityMethods($methodName);

            $terminalsMap = [];

            foreach ($subMerchants as $subMerchant)
            {
                $terminalsMap[$subMerchant->terminal_id][] = $subMerchant->merchant_id;
            }

            foreach ($terminalsMap as $terminal => $merchants)
            {
                $subMerchantsTerminalsRedisKey = sprintf(self::REDIS_KEY_SUB_MERCHANTS_DATA, $terminal);
                $this->app->cache->set($subMerchantsTerminalsRedisKey, $merchants, self::REDIS_KEY_TTL);
            }

            $this->app->cache->set($subMerchantsRedisKey, true, self::REDIS_KEY_TTL);

        }

        $redisKey = sprintf(self::REDIT_KEY_LAST_MIGRATED_MERCHANT_PHASE_2, $methodName);

        if($inputFrom != null)
        {
            $from = $inputFrom;
        }
        else
        {

            $from =  $this->app->cache->get($redisKey);

            if($from == null)
            {
                $from = 0;
            }
        }

        // fetch terminals query
        $terminals = (new Terminal\Repository)->getTerminalsforAffordabilityMethods($methodName,$from,$count);

        $createdAt = null;

        $failureCount = 0;

        foreach($terminals as $terminal)
        {
            $createdAt = $terminal->getCreatedAt();

            if($terminal->getGatewayAcquirer() != 'earlysalary')
            {
                try
                {
                    $this->updateMerchantsForDedicatedTerminals($terminal);
                }
                catch(\Throwable $ex)
                {
                    $data = ["terminal_id" => $terminal->getId()];
                    $failureCount++;

                    $this->trace->traceException($ex,
                        Trace::ERROR,
                        TraceCode::UPDATE_METHOD_FAILED,
                        $data);

                }
            }
        }

        $this->app->cache->set($redisKey, $createdAt, self::REDIS_KEY_TTL);

        $res = ['failure'=>$failureCount];

        return $res;


    }

    public function migrateInstrumentsForSpecificMerchants($merchantIds)
    {

        $successCount = 0;
        $failureCount = 0;
        $totalCount = 0;

        // fetch methods from slave, method value as null
        $methods = $this->repo->useSlave(function() use ($merchantIds)
        {
            return $this->repo->methods->fetchMethodsBasedOnMerchantIds($merchantIds);

        });

        foreach ($methods as $method)
        {
            $totalCount++;
            $merchantId = $method->getMerchantId();

            try
            {
                $this->migrateAffordabilityMethodsforMerchant($merchantId , $method);

                $successCount++;

            }
            catch(\Throwable $ex)
            {
                $data = ["merchant_id" => $merchantId];

                $this->trace->traceException($ex,
                    Trace::ERROR,
                    TraceCode::UPDATE_METHOD_FAILED,
                    $data);

                $failureCount++;
            }
        }

        $res = ["success"=> $successCount, "failure"=>$failureCount, "total"=>$totalCount];

        $this->trace->info(
            TraceCode::UPDATE_METHOD_RESPONSE,
            $res
        );
        return $res;

    }

    public function updateAffordabilityPaymentMethods($input)
    {
        $this->trace->info(
            TraceCode::UPDATE_METHOD_REQUEST,
            $input
        );

        $count = $input['count'] ?? 100;
        $sharedTerminals = $input['shared_terminals'];
        $isPaylater = $input['paylater'];
        $isCardlessEmi = $input['cardless_emi'];
        $isCreditEmi = $input['credit_emi'];
        $methodName = $input['method'];
        $inputFrom = $input['from'];
        $instrument = $input['instrument'];

        $res = null;

        if (isset($input['merchant_ids']))
        {
            $merchantIds = $input['merchant_ids'];

            $res = $this->migrateInstrumentsForSpecificMerchants($merchantIds);

        }

        else if(isset($input['instrument']))
        {
            $redisKey = sprintf(self::REDIS_KEY_PREVIOUS_RUN_IN_PROGRESS_INSTRUMENT, $methodName, $instrument);

            $previousRunInProgress =  $this->app->cache->get($redisKey);

            if($previousRunInProgress == true)
            {
                throw new Exception\LogicException('Previous run still in progress',
                    ErrorCode::BAD_REQUEST_PREVIOUS_RUN_IN_PROGRESS);
            }

            $this->app->cache->set($redisKey, true, self::REDIS_KEY_TTL);

            try
            {
                $res = $this->updateMerchantsForSpecificInstruments($inputFrom,$methodName,$instrument,$count);

                $this->app->cache->set($redisKey, false, self::REDIS_KEY_TTL);

            }
            catch(\Throwable $ex)
            {
                $this->trace->traceException($ex,
                    Trace::ERROR,
                    TraceCode::UPDATE_METHOD_FAILED);
                $this->app->cache->set($redisKey, false, self::REDIS_KEY_TTL);
            }
        }

        else if($sharedTerminals == true)
        {

            $redisKey = sprintf(self::REDIS_KEY_PREVIOUS_RUN_IN_PROGRESS_PHASE1, $isPaylater,$isCardlessEmi,$isCreditEmi);

            $previousRunInProgress =  $this->app->cache->get($redisKey);

            if($previousRunInProgress == true)
            {
                throw new Exception\LogicException('Previous run still in progress',
                    ErrorCode::BAD_REQUEST_PREVIOUS_RUN_IN_PROGRESS);
            }

            $this->app->cache->set($redisKey, true, self::REDIS_KEY_TTL);

            try
            {
                $res = $this->updateMerchantsForSharedTerminals($isPaylater,$isCardlessEmi,$isCreditEmi,$count,$inputFrom);

                $this->app->cache->set($redisKey, false, self::REDIS_KEY_TTL);

            }
            catch(\Throwable $ex)
            {
                $this->app->cache->set($redisKey, false, self::REDIS_KEY_TTL);
            }

        }

        else if($sharedTerminals == false)
        {

            $redisKey = sprintf(self::REDIS_KEY_PREVIOUS_RUN_IN_PROGRESS_PHASE2, $methodName);

            $previousRunInProgress =  $this->app->cache->get($redisKey);

            if($previousRunInProgress == true)
            {
                throw new Exception\LogicException('Previous run still in progress',
                    ErrorCode::BAD_REQUEST_PREVIOUS_RUN_IN_PROGRESS);
            }

            $this->app->cache->set($redisKey, true, self::REDIS_KEY_TTL);

            try
            {
                $res = $this->migrateInstrumentsOnDedicatedTerminals($methodName,$count,$inputFrom);

                $this->app->cache->set($redisKey, false, self::REDIS_KEY_TTL);

            }
            catch(\Throwable $ex)
            {
                $this->trace->traceException($ex,
                    Trace::ERROR,
                    TraceCode::UPDATE_METHOD_FAILED);
                $this->app->cache->set($redisKey, false, self::REDIS_KEY_TTL);
            }

        }

        $this->trace->info(
            TraceCode::UPDATE_METHOD_RESPONSE,
            $res
        );

        return $res;
    }
}
