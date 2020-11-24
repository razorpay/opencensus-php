<?php

namespace RZP\Models\Settlement\OndemandFundAccount;

use Config;
use Request;
use Illuminate\Support\Str;

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

    public function addOndemandFundAccountForMerchant($merchantId)
    {
        $this->trace->info(TraceCode::CREATE_SETTLEMENT_ONDEMAND_FUND_ACCOUNT, [
            'merchant_id'   => $merchantId,
        ]);

        $this->merchant = $this->repo->merchant->findOrFail($merchantId);

        $fundAccount = (new Repository)->findByMerchantId($merchantId);

        /** @var RazorpayXClient $razorpayXClientService */
       $razorpayXClientService = $this->app->razorpayXClient;

        if (isset($fundAccount[OndemandFundAccount\Entity::CONTACT_ID]) === false)
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

            $this->repo->saveOrFail($fundAccount);
        }

        $data = $this->getBankAccountDetails($merchantId);

        $fundAccount[OndemandFundAccount\Entity::FUND_ACCOUNT_ID] =
        $razorpayXClientService->createFundAccount($fundAccount[OndemandFundAccount\Entity::CONTACT_ID],
                                                   $data)['id'];

        $this->repo->saveOrFail($fundAccount);

        return $fundAccount;
    }

    public function getBankAccountDetails($merchantId)
    {
        $accountDetails = (new Merchant\Service)->getBankAccount($merchantId);

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
}
