<?php


namespace RZP\Models\BankingAccount\Activation\MIS;

use Carbon\Carbon;

use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\BankingAccount;
use RZP\Models\BankingAccount\Activation\Detail as ActivationDetail;

class Leads extends Base
{
    //Headers
    const MERCHANT_ID = 'Merchant ID';
    const MERCHANT_NAME = 'Customer Name';
    const MERCHANT_POC_NAME = 'Customer POC Name';
    const MERCHANT_POC_EMAIL = 'Customer POC Email';
    const MERCHANT_POC_DESIGNATION = 'Customer POC Designation';
    const MERCHANT_POC_PHONE = 'Customer POC Phone';
    const MERCHANT_CITY = 'Merchant City';
    const MERCHANT_REGION = 'Merchant Region';
    const YEARS_WITH_RAZORPAY = 'Years of Merchant Relationship with Razorpay';
    const PINCODE = 'Customer Pincode';

    const MERCHANT_ICV = 'ICV (in INR)';
    const BUSINESS_MODEL = 'Business Model';
    const ACCOUNT_TYPE = 'Account Type';
    const AVERAGE_MONTHLY_BALANCE = 'Average Monthly Balance';
    const CONSTITUTION_TYPE = 'Constitution Type';
    const ZERO_AVERAGE_MONTHLY_BALANCE = 'Zero AMB?';
    const EXPECTED_MONTHLY_GMV = 'Expected Monthly GMV';

    const RZP_REF_NO = 'RZP Reference No';
    const COMMENT = 'Comments';
    const SALES_TEAM = 'Sales Team';
    const SALES_POC_EMAIL = 'Razorpay Sales POC Email';
    const SALES_POC = 'Razorpay Sales POC';
    const SALES_POC_PHONE_NUMBER = 'Razorpay Sales POC Phone Number';

    const APPLICATION_SUBMISSION_DATE = 'Application Submission Date';

    protected static $toPublicMap = [
      self::CONSTITUTION_TYPE => [
          ActivationDetail\Validator::PRIVATE_PUBLIC_LIMITED_COMPANY    => 'Private/Public Limited Company',
          ActivationDetail\Validator::SOLE_PROPRIETORSHIP               => 'Sole Proprietorship',
          ActivationDetail\Validator::LIMITED_LIABILITY_PARTNERSHIP     => 'Limited Liability Partnership',
          ActivationDetail\Validator::PARTNERSHIP                       => 'Partnership',
      ],
      self::ACCOUNT_TYPE => [
          ActivationDetail\Validator::INSIGNIA      => 'Insignia',
          ActivationDetail\Validator::PREMIUM       => 'Premium',
          ActivationDetail\Validator::BUSINESS_PLUS => 'Business Plus',
          ActivationDetail\Validator::ZERO_BALANCE  => 'Zero Balance',
      ],
      self::SALES_TEAM => [
          ActivationDetail\Validator::GROWTH                 => 'X Growth',
          ActivationDetail\Validator::DIRECT_SALES           => 'X Direct Sales',
          ActivationDetail\Validator::KEY_ACCOUNT            => 'X Key Account',
          ActivationDetail\Validator::SME                    => 'X SME',
          ActivationDetail\Validator::CAPITAL_SME            => 'Capital SME',
          ActivationDetail\Validator::CAPITAL_GROWTH         => 'Capital Growth',
          ActivationDetail\Validator::CAPITAL_KAM            => 'Capital KAM',
          ActivationDetail\Validator::CAPITAL_DIRECT_SALES   => 'Capital Direct Sales',
          ActivationDetail\Validator::PG_SME                 => 'PG SME',
          ActivationDetail\Validator::PG_GROWTH              => 'PG Growth',
          ActivationDetail\Validator::PG_KAM                 => 'PG KAM',
          ActivationDetail\Validator::PG_DIRECT_SALES        => 'PG Direct Sales',
          ActivationDetail\Validator::SELF_SERVE             => 'Self Serve'

      ]
    ];

    protected function toPublic(string $header, string $value = null)
    {
        if ($value === null)
        {
            return $value;
        }

        return self::$toPublicMap[$header][$value];
    }


    public function __construct(array $input)
    {
        $timestamp = Carbon::createFromTimestamp(time(), Timezone::IST)->format('Y-m-d--H-i');

        $this->fileName = "CA-Leads-MIS-" . $timestamp;

        $this->fileType = 'banking_account_leads';

        parent::__construct($input);

        $this->trace->info(TraceCode::BANKING_ACCOUNT_LEADS_MIS_REQUEST,
            [
               'file_name' => $this->fileName,
               'type'      => $this->fileType,
               'input'     => $input
            ]);
    }

    public function getFileInput()
    {
        $bankingAccounts = $this->repo->banking_account->fetch($this->input);

        $fileInput = [];

        foreach ($bankingAccounts as $bankingAccount)
        {
            $bankAccountType = $bankingAccount->bankingAccountActivationDetails[ActivationDetail\Entity::ACCOUNT_TYPE];

            $zeroAmb = ($bankAccountType === ActivationDetail\Validator::ZERO_BALANCE) ? 'Yes' : 'No';

            $fileInput[] = [
                self::APPLICATION_SUBMISSION_DATE => date('Y-m-d'),
                self::RZP_REF_NO => $bankingAccount[BankingAccount\Entity::BANK_REFERENCE_NUMBER],
                self::MERCHANT_NAME => $bankingAccount->merchant[Merchant\Entity::NAME],
                self::MERCHANT_POC_NAME => $bankingAccount->bankingAccountActivationDetails[ActivationDetail\Entity::MERCHANT_POC_NAME],
                self::MERCHANT_POC_DESIGNATION => $bankingAccount->bankingAccountActivationDetails[ActivationDetail\Entity::MERCHANT_POC_DESIGNATION],
                self::MERCHANT_POC_EMAIL => $bankingAccount->bankingAccountActivationDetails[ActivationDetail\Entity::MERCHANT_POC_EMAIL],
                self::MERCHANT_POC_PHONE => $bankingAccount->bankingAccountActivationDetails[ActivationDetail\Entity::MERCHANT_POC_PHONE_NUMBER],
                self::PINCODE => $bankingAccount[BankingAccount\Entity::PINCODE],
                self::MERCHANT_ICV => $bankingAccount->bankingAccountActivationDetails[ActivationDetail\Entity::INITIAL_CHEQUE_VALUE],
                self::CONSTITUTION_TYPE => $this->toPublic(self::CONSTITUTION_TYPE, $bankingAccount->bankingAccountActivationDetails[ActivationDetail\Entity::BUSINESS_CATEGORY]),
                self::BUSINESS_MODEL => $bankingAccount->merchant->merchantDetail['business_model'],
                self::ACCOUNT_TYPE => $this->toPublic(self::ACCOUNT_TYPE, $bankingAccount->bankingAccountActivationDetails[ActivationDetail\Entity::ACCOUNT_TYPE]),
                self::ZERO_AVERAGE_MONTHLY_BALANCE => $zeroAmb,
                self::EXPECTED_MONTHLY_GMV => $bankingAccount->bankingAccountActivationDetails[ActivationDetail\Entity::EXPECTED_MONTHLY_GMV],
                self::COMMENT => $bankingAccount->bankingAccountActivationDetails[ActivationDetail\Entity::COMMENT],
                self::SALES_POC => $bankingAccount->spocs->first()['name'],
                self::SALES_POC_EMAIL => $bankingAccount->spocs->first()['email'],
                self::SALES_POC_PHONE_NUMBER => $bankingAccount->bankingAccountActivationDetails[ActivationDetail\Entity::SALES_POC_PHONE_NUMBER],
            ];
        }

        return $fileInput;
    }
}
