<?php

namespace RZP\Models\Settlement\OndemandFundAccount;

use Config;
use Request;
use Illuminate\Support\Str;

use RZP\Constants\Mode;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Jobs\RequestJob;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\BankAccount;
use RZP\Models\Merchant\Detail;
use RZP\Services\RazorpayXClient;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Base\Core as BaseCore;
use RZP\Http\Request\Requests  as RzpRequest;
use RZP\Models\Settlement\OndemandFundAccount;

class Core extends Base\Core
{
    const CUSTOMER = 'customer';

    const ACCOUNT_TYPE = 'bank_account';

    public function addOndemandFundAccountForMerchant($merchantId, $bankAccount = null)
    {
        $this->trace->info(TraceCode::CREATE_SETTLEMENT_ONDEMAND_FUND_ACCOUNT, [
            'merchant_id'   => $merchantId,
        ]);

        $this->merchant = $this->repo->merchant->findOrFail($merchantId);

        $fundAccount = $this->getFundAccountByMerchantId($merchantId);

        /** @var RazorpayXClient $razorpayXClientService */
        $razorpayXClientService = $this->app->razorpayXClient;

        if ($fundAccount === null || isset($fundAccount[OndemandFundAccount\Entity::CONTACT_ID]) === false)
        {
            $merchantDetails = $this->merchant->merchantDetail;

            $name = str_limit(preg_replace('/[^a-zA-Z0-9 ]+/', '', $merchantDetails[Detail\Entity::CONTACT_NAME]), 50, '');

            $data = [
                'name'      => trim($name) ?: 'Razorpay',
                'email'     => trim($merchantDetails[Detail\Entity::CONTACT_EMAIL]) ?: 'void@razorpay.com',
                'contact'   => trim(substr($merchantDetails[Detail\Entity::CONTACT_MOBILE], -10, 10)) ?: '9876543210',
                'type'      => self::CUSTOMER,
            ];

            $contactId = $razorpayXClientService->createContact($data)['id'];

            $input = [
                OndemandFundAccount\Entity::MERCHANT_ID     => $merchantId,
                OndemandFundAccount\Entity::CONTACT_ID      => $contactId,
            ];

            $fundAccount = (new OndemandFundAccount\Entity)->build($input);

            $fundAccount->generateId();

            $fundAccount->merchant()->associate($this->merchant);

            $this->repo->transaction(function () use ($fundAccount)
            {
                $this->repo->saveOrFail($fundAccount);

                if ($this->isFundAccountMigrated('dual_write') === true) {
                    $this->app['capital_early_settlements']->dualWriteFundAccount([
                        'merchant_id' => $fundAccount[OndemandFundAccount\Entity::MERCHANT_ID],
                        'contact_id'  => $fundAccount[OndemandFundAccount\Entity::CONTACT_ID],
                    ]);
                }
            });
        }

        $data = $this->getBankAccountDetails($merchantId, $bankAccount);

        $fundAccountId = $razorpayXClientService->createFundAccount($fundAccount[OndemandFundAccount\Entity::CONTACT_ID], $data)['id'];

        return $this->repo->transaction(function () use ($fundAccountId, $merchantId)
        {
            $apiFundAccount = (new Repository)->findByMerchantId($merchantId);
            $apiFundAccount[OndemandFundAccount\Entity::FUND_ACCOUNT_ID] = $fundAccountId;
            $this->repo->saveOrFail($apiFundAccount);

            if ($this->isFundAccountMigrated('dual_write') === true) {
                $this->app['capital_early_settlements']->dualWriteFundAccount([
                    'merchant_id' => $merchantId,
                    'fund_account_id' => $fundAccountId,
                ]);
            }

            return $apiFundAccount;
        });
    }

    public function getBankAccountDetails($merchantId, $bankAccount = null)
    {
        if (empty($bankAccount) === true) {
            $accountDetails = (new Merchant\Service)->getBankAccount($merchantId);
        }
        else {
            $accountDetails = $bankAccount->toArray();
        }

        $beneficiaryName = trim(str_limit(preg_replace('/[^a-zA-Z0-9 ]+/',
                                                  '',
                                                  $accountDetails[BankAccount\Entity::BENEFICIARY_NAME]),
                                                  50,
                                                  ''));

        return [
            'name'           => strlen($beneficiaryName) > 4 ? $beneficiaryName : 'Razorpay',
            'ifsc'           => $accountDetails[BankAccount\Entity::IFSC_CODE],
            'account_number' => $accountDetails[BankAccount\Entity::ACCOUNT_NUMBER],
            'account_type'   => self::ACCOUNT_TYPE,
        ];
    }

    public function getFundAccountByMerchantId($merchantId)
    {
        if ($this->isFundAccountMigrated('read') === false) {
            return (new Repository)->findByMerchantId($merchantId);
        }

        try {
            $response = $this->app['capital_early_settlements']->getFundAccount($merchantId);
        }
        catch (Exception\BadRequestException $e) {
            $errorData = $e->getData();
            if ($errorData && $errorData['status_code'] === 404) {
                return null;
            }

            throw $e;
        }

        $input = [
            OndemandFundAccount\Entity::MERCHANT_ID     => $merchantId,
        ];

        if (empty($response[OndemandFundAccount\Entity::CONTACT_ID]) === false) {
            $input[OndemandFundAccount\Entity::CONTACT_ID] = $response[OndemandFundAccount\Entity::CONTACT_ID];
        }

        if (empty($response[OndemandFundAccount\Entity::FUND_ACCOUNT_ID]) === false) {
            $input[OndemandFundAccount\Entity::FUND_ACCOUNT_ID] = $response[OndemandFundAccount\Entity::FUND_ACCOUNT_ID];
        }

        return (new OndemandFundAccount\Entity)->build($input);
    }

    public function invalidateFundAccount($merchantId)
    {
        $this->repo->transaction(function () use ($merchantId)
        {
            $apiFundAccount = (new Repository)->findByMerchantId($merchantId);
            if ($apiFundAccount !== null)
            {
                $apiFundAccount->setFundAccountIdNull();
                $this->repo->saveOrFail($apiFundAccount);
            }

            $fundAccount = $this->getFundAccountByMerchantId($merchantId);
            if ($fundAccount !== null && $this->isFundAccountMigrated('dual_write') === true) {
                // The action here will be dual_write because this method will only be called in the dual write flow
                $this->app['capital_early_settlements']->invalidateAndCreateFundAccount($merchantId, true);
            }
        });
    }

    public function isFundAccountMigrated($action)
    {
        if ($this->mode === Mode::TEST)
        {
            return false;
        }

        $request = ['experiment_id' => $this->app['config']->get('app.fund_account_from_capital_es_experiment_id')];
        $response = $this->app['splitzService']->evaluateRequest($request);

        $variables = $response['response']['variant']['variables'] ?? [];
        if (is_array($variables) === false)
        {
            return false;
        }

        foreach ($variables as $variable)
        {
            if (is_array($variable) === true && $variable['key'] === $action && $variable['value'] === 'on')
            {
                return true;
            }
        }

        return false;
    }
}
