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
                throw new \Exception("No Virtual Account Found");
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
            $va_currency = strtoupper($va_currency);

            if(Gateway::isCurrencySupportedForInternationalBankTransfer($va_currency) === false){
                throw new \Exception("Currency Not Supported for International Bank Transfer");
            }

             $virtual_bank_accounts = $this->fetchIntlVirtualBankAccountsForGateway($merchantId,Gateway::CURRENCY_CLOUD);

             if(count($virtual_bank_accounts) === 0)
             {
                 throw new \Exception("No Virtual Account Found");
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

        if($merchant->isFeatureEnabled(Constants::ENABLE_B2B_EXPORT) === false)
        {
            return [];
        }

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

            // Fetch Bank Accounts By Calling CC API
            if(isset($bank_accounts_json) === false)
            {
                $mutex_key = "fetch_cc_va_" . $merchantId;

                $this->mutex->acquireAndRelease($mutex_key,
                function () use ($merchantId,$mii)
                {
                    return (new BankTransfer\Service)->updateBankAccountDetails($merchantId,$mii);
                },20,
                ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_OPERATION_IN_PROGRESS);
            }

            $mii->reload();

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

            $bank_accounts = json_decode($bank_accounts_json,true);

            $bank_accounts_redacted = $this->redactBankAccounts($bank_accounts);

            $this->trace->info(
                TraceCode::FETCH_INTERNATIONAL_BANK_TRANSFERS_ACCOUNTS,
                [
                    'bank_accounts' => $bank_accounts_redacted
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
}
