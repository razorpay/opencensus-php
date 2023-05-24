<?php

namespace RZP\Models\Merchant\InternationalIntegration;

use mysql_xdevapi\Exception;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Models\Base;
use RZP\Models\Currency\Currency;
use RZP\Models\Payment\Gateway;
use RZP\Models\BankTransfer;
use RZP\Models\Feature\Constants;
use RZP\Models\Payment\Processor\IntlBankTransfer;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    protected $mutex;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    const CURRENCY          = "currency";
    const ACCOUNT_NUMBER    = "account_number";
    const VA_CURRENCY       = "va_currency";
    const AMOUNT            = "amount";
    const ACCOUNT           = "account";
    const SYMBOL            = "symbol";
    const ROUTING_TYPE      = 'routing_type';
    const ROUTING_CODE      = 'routing_code';
    const ROUTING_DETAILS   = 'routing_details';
    const STATUS            = 'status';
    const ACTIVATED         = 'activated';
    const DEACTIVATED       = 'deactivated';

    // Routing Code Types

    const WIRE_ROUTING_NUMBER   = 'wire_routing_number';
    const ACH_ROUTING_NUMBER    = 'ach_routing_number';

    public static $preferredRoutingCodeMapping = [
        Currency::USD => self::ACH_ROUTING_NUMBER
    ];

    public function createMerchantInternationalIntegration($input)
    {
        try
        {

            $this->repo->merchant->findOrFail($input[Entity::MERCHANT_ID]);

            $mii = $this->repo->merchant_international_integrations->getByMerchantIdAndIntegrationEntity(
                $input[Entity::MERCHANT_ID], $input[Entity::INTEGRATION_ENTITY]);

            if(isset($mii))
            {
                throw new \Exception("Merchant Intgration Entity already exists");
            }
            else{
                $this->createNewIntegration($input);
            }
        }
        catch(\Throwable $e)
        {
            {
                $this->trace->traceException($e);

                $this->trace->info(
                    TraceCode::MERCHANT_INTERNATIONAL_INTEGRATION_SAVE_FAILED,
                    ['input' => $input[Entity::MERCHANT_ID]]
                );

                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_ERROR,
                    null,
                    null,
                    $e->getMessage()
                );
            }
        }

        return [
            'merchant_id'             => $input[Entity::MERCHANT_ID],
            'integration_entity'      => $input[Entity::INTEGRATION_ENTITY],
        ];
    }

    protected function createNewIntegration(array $input)
    {
        $merchantIntegration = new Entity;

        $merchantIntegration->generateId();

        $merchantIntegration->build($input);

        $this->repo->merchant_international_integrations->saveOrFail($merchantIntegration);

        $this->trace->info(TraceCode::MERCHANT_INTERNATIONAL_INTEGRATION_CREATE, [
            'merchant_id'             => $input[Entity::MERCHANT_ID],
            'integration_entity'      => $input[Entity::INTEGRATION_ENTITY],
        ]);

        return $merchantIntegration;
    }

    public function deleteMerchantInternationalIntegration(array $input)
    {
        try
        {
            $mid = $input[Entity::MERCHANT_ID];
            $integration_entity = $input[Entity::INTEGRATION_ENTITY];

            $this->repo->merchant->findOrFail($input[Entity::MERCHANT_ID]);

            $integration =  $this->repo->merchant_international_integrations->getByMerchantIdAndIntegrationEntity(
                $mid, $integration_entity);

            if(!isset($integration)){
                throw new \Exception("Invalid request");
            }

            $this->trace->info(
                TraceCode::MERCHANT_INTERNATIONAL_INTEGRATION_DELETE,
                ['input' => $input]
            );

            $this->repo->deleteOrFail($integration);
        }
        catch(\Exception $e)
        {

            $this->trace->info(
                TraceCode::MERCHANT_INTERNATIONAL_INTEGRATION_DELETE_FAILED,
                ['input' => $input, 'message' => $e->getMessage(), 'trace' => $e->getTrace()]
            );

            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR,
                null,
                null,
                $e->getMessage()
            );
        }

        return ['success' => 'true'];
    }

    public function editMerchantInternationalIntegrations($input)
    {
        $this->trace->info(TraceCode::MERCHANT_INTERNATIONAL_INTEGRATION_UPDATE,
            [
                'input' => $input
            ]);

        try {
            $this->repo->merchant->findOrFail($input[Entity::MERCHANT_ID]);

            $merchantIntegration = $this->repo->merchant_international_integrations->getByMerchantIdAndIntegrationEntity(
                $input[Entity::MERCHANT_ID], $input[Entity::INTEGRATION_ENTITY]);

            $merchantIntegration->edit($input);

            $this->repo->merchant_international_integrations->saveOrFail($merchantIntegration);
        }
        catch (\Exception $e)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR,
                null,
                null,
                $e->getMessage());
        }



        return $merchantIntegration;
    }

    public function getInternationalVirtualAccounts($merchantId)
    {
       try
       {
            $virtual_bank_accounts = $this->fetchIntlVirtualBankAccountsForGateway($merchantId,Gateway::CURRENCY_CLOUD);

            if(count($virtual_bank_accounts) === 0)
            {
                return [];
            }

            return $virtual_bank_accounts;
       }
       catch(\Exception $e)
       {
           throw new BadRequestException(
               ErrorCode::BAD_REQUEST_ERROR,
               null,
               null,
               $e->getMessage()
           );
       }
    }

    public function getInternationalVirtualAccountByVACurrency($input, $merchantId, $va_currency)
    {
        try
        {
            if(str_starts_with($va_currency,"va_"))
            {
                $va_currency = substr($va_currency,3);
            }

            $va_currency = strtoupper($va_currency);

            if(Gateway::isVACurrencySupportedForInternationalBankTransfer($va_currency) === false){
                throw new \Exception("Currency/Method Not Supported for International Bank Transfer");
            }

             $virtual_bank_accounts = $this->fetchIntlVirtualBankAccountsForGateway($merchantId,Gateway::CURRENCY_CLOUD);

             if(count($virtual_bank_accounts) === 0)
             {
                 return [];
             }

             $response = [];

             $response[self::ACCOUNT] = $this->fetchVirtualAccountByVACurrencyFromVirtualAccounts($virtual_bank_accounts,$va_currency);

            if(isset($input[self::AMOUNT]) === true && isset($input[self::CURRENCY]) === true)
            {
                $currency = $input[self::CURRENCY];
                $amount = $input[self::AMOUNT];

                $currency = strtoupper($currency);

                $merchant = $this->repo->merchant->findOrFail($merchantId);

                $response[self::AMOUNT]     = $this->getConvertedAmount($va_currency,$currency,$amount, $merchant->getDccMarkupPercentageForIntlBankTransfer());
                $response[self::CURRENCY]   = $va_currency;
                $response[self::SYMBOL]     = Currency::SYMBOL[$va_currency];

                return $response;
            }

             return $response;
        }
        catch(\Exception $e)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR,
                null,
                null,
                $e->getMessage()
            );
        }
    }

    private function fetchVirtualAccountByVACurrencyFromVirtualAccounts($virtual_bank_accounts,$va_currency){

        if(isset($virtual_bank_accounts) === false || isset($va_currency) === false)
        {
            return [];
        }

        foreach($virtual_bank_accounts as $key => $account)
        {
            if($account[self::VA_CURRENCY] === $va_currency)
            {
                return $account;
            }
        }

        return [];
    }

    public function fetchIntlVirtualBankAccountsForGateway($merchantId,$gateway) : array
    {
        $merchant = $this->repo->merchant->findOrFail($merchantId);

        $mii = $this->repo->merchant_international_integrations
            ->getByMerchantIdAndIntegrationEntity($merchantId, $gateway);

            if(isset($mii) === false)
            {
                $this->trace->info(
                    TraceCode::INTERNATIONAL_BANK_TRANSFERS_ACCOUNT_NOT_FOUND,
                    [
                        'bank_accounts' => []
                    ]
                );

                return [];
            }

            $bank_accounts_json = $mii->getBankAccount();

            if(isset($bank_accounts_json) === false)
            {
                $this->trace->info(
                    TraceCode::INTERNATIONAL_BANK_TRANSFERS_ACCOUNT_NOT_FOUND,
                    [
                        'bank_accounts' => []
                    ]
                );

                 return [];
            }

            $bank_accounts_map = json_decode($bank_accounts_json,true);

            $bank_accounts = [];

            $methods = $this->repo->methods->getMethodsForMerchant($merchant);

            $intl_bank_transfer_modes = $methods->getIntlBankTransferEnabledModes();

            foreach($bank_accounts_map as $key => $account)
            {
                $preferredRoutingCode = $this->getPreferredRoutingCodeByCurrency($account[self::VA_CURRENCY],$account[self::ROUTING_DETAILS]);

                unset($account[self::ROUTING_DETAILS]);

                $account[self::ROUTING_TYPE] = $preferredRoutingCode[self::ROUTING_TYPE];
                $account[self::ROUTING_CODE] = $preferredRoutingCode[self::ROUTING_CODE];

                $mode = Gateway::getIntlBankTransferModeByCurrency($account[self::VA_CURRENCY]);

                $account[self::STATUS] = $intl_bank_transfer_modes[$mode] ? self::ACTIVATED : self::DEACTIVATED;

                array_push($bank_accounts,$account);
            }

            $this->trace->info(
                TraceCode::FETCH_INTERNATIONAL_BANK_TRANSFERS_ACCOUNTS,
                [
                    'bank_accounts' => $this->redactBankAccounts($bank_accounts)
                ]
            );

            return $bank_accounts;
    }

    private function redactBankAccounts($bank_accounts)
    {
        foreach($bank_accounts as $key => $account)
        {
            if(isset($account[self::ACCOUNT_NUMBER]) === true){
                $account[self::ACCOUNT_NUMBER] = str_repeat('*', strlen($account[self::ACCOUNT_NUMBER]));
            }
        }

        return $bank_accounts;
    }

    private function getConvertedAmount(string $va_currency, string $currency, string $amount, string $markUpPercentage)
    {
        if ($va_currency === $currency)
        {
            return $amount;
        }

        $converted_amount = (new \RZP\Models\Currency\Core())->convertAmount($amount, $currency, $va_currency);

        return (int) ceil($converted_amount + (($markUpPercentage * $converted_amount) / 100));
    }

    private function getPreferredRoutingCodeByCurrency($va_currency,$routing_details)
    {

        if(array_key_exists($va_currency,self::$preferredRoutingCodeMapping) === false)
        {
            return $routing_details[0];
        }
        else
        {
            foreach ($routing_details as $routing_detail)
            {
                if($routing_detail[self::ROUTING_TYPE] === self::$preferredRoutingCodeMapping[$va_currency]){
                    return $routing_detail;
                }
            }
            return $routing_details[0];
        }
    }

    public function getByIntegrationKey($integrationKey)
    {
        return $this->repo->merchant_international_integrations->getByIntegrationKey($integrationKey);
    }
}
