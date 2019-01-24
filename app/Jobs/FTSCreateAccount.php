<?php

namespace RZP\Jobs;

use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

class FTSCreateAccount extends Job
{
    const MAX_RETRY_ATTEMPTS   = 0;

    const MAX_ALLOWED_ATTEMPTS = 1;

    const ID                            = "id";
    const TYPE                          = "type";
    const BENEFICIARY_PIN               = "beneficiary_pin";
    const BENEFICIARY_NAME              = "beneficiary_name";
    const BENEFICIARY_CODE              = "beneficiary_code";
    const BENEFICIARY_CITY              = "beneficiary_city";
    const BENEFICIARY_STATE             = "beneficiary_state";
    const BENEFICIARY_MOBILE            = "beneficiary_mobile";
    const BENEFICIARY_ADDRESS           = "beneficiary_address";
    const BENEFICIARY_COUNTRY           = "beneficiary_country";
    const BENEFICIARY_EMAIL_ID          = "beneficiary_email_id";
    const BENEFICIARY_IFSC_CODE         = "beneficiary_ifsc_code";
    const BENEFICIARY_BANK_NAME         = "beneficiary_bank_name";
    const BENEFICIARY_ACCOUNT_TYPE      = "beneficiary_account_type";
    const BENEFICIARY_ACCOUNT_NUMBER    = "beneficiary_account_number";

    const USERNAME        = 'username';
    const HANDLE          = 'handle';
    const MERCHANT_ID     = 'merchant_id';

    const BANK_ACCOUNT              = 'bank_account';
    const VPA                       = 'vpa';

    /**
     * @var string
     */
    protected $queueConfigKey = 'fts_create_account';

    protected $id;

    protected $merchantId;

    protected $type;

    public function __construct(string $id, string $mode, string $type)
    {
        parent::__construct($mode);

        $this->id  = $id;

        $this->type = $type;
    }

    /**
     * Process queue request
     */
    public function handle()
    {
        try
        {
            if ($this->attempts() > self::MAX_ALLOWED_ATTEMPTS)
            {
                $this->delete();

                return;
            }

            parent::handle();

            $this->trace->info(TraceCode::FTS_CREATE_ACCOUNT, $this->type);

            switch ($this->type)
            {
                case self::BANK_ACCOUNT:
                    $account = $this->getBankAccount($this->id);

                    $request[self::BANK_ACCOUNT] = $this->getAccountDetails($account);

                    break;

                case self::VPA:
                    $account = $this->getVPA($this->id);

                    $request[self::VPA] = $this->getVPADetails($account);

                    break;

                default:
                    throw new LogicException('Type is not supported ' . $this->type);
            }

            $ftsResponse = App::getFacadeRoot()['fts']->createFundAccount($request, true);

            $ftsAccountId = array_key_exists('ftsAccountId', $ftsResponse['body']) ? $ftsResponse['body']['ftsAccountId'] : null;

            $this->saveFTSAccountId($ftsAccountId, $account);

            $this->trace->info(
                TraceCode::FTS_ACCOUNT_CREATED_FOR_MERCHANT,
                $ftsResponse);
        }
        catch (\Throwable $e)
        {

            $data = [
                'merchant_id'       => $this->merchantId ,
                'mode'              => $this->mode,
            ];

            $this->trace->traceException($e,
                Trace::ERROR,
                TraceCode::FTS_ACCOUNT_CREATION_FAILED,
                $data);

        }
    }

    public function getBankAccount($BankAccountId)
    {
        return $this->repoManager->bank_account->getBankAccountById($BankAccountId);
    }

    public function getVPA($vpaId)
    {
        return $this->repoManager->vpa->getVPAById($vpaId);
    }

    public function getAccountDetails($ba)
    {
        $data[self::ID]                         = $ba->getId();

        $data[self::TYPE]                       = $ba->getType();

        $data[self::BENEFICIARY_PIN]            = $ba->getBeneficiaryPin();

        $data[self::BENEFICIARY_NAME]           = $ba->getBeneficiaryName();

        $data[self::BENEFICIARY_CODE]           = $ba->getBeneficiaryCode();

        $data[self::BENEFICIARY_CITY]           = $ba->getBeneficiaryCity();

        $data[self::BENEFICIARY_STATE]          = $ba->getBeneficiaryState();

        $data[self::BENEFICIARY_MOBILE]         = $ba->getBeneficiaryMobile();

        $data[self::BENEFICIARY_ADDRESS]        = $ba->getBeneficiaryAddress1();

        $data[self::BENEFICIARY_COUNTRY]        = $ba->getBeneficiaryCountry();

        $data[self::BENEFICIARY_EMAIL_ID]       = $ba->getBeneficiaryEMail();

        $data[self::BENEFICIARY_IFSC_CODE]      = $ba->getIfscCode();

        $data[self::BENEFICIARY_BANK_NAME]      = $ba->getBankName();

        $data[self::BENEFICIARY_ACCOUNT_TYPE]   = $ba->getAccountType();

        $data[self::BENEFICIARY_ACCOUNT_NUMBER] = $ba->getAccountNumber();

        //TODO: to fetch the merchantId
        //$data[self::MERCHANT_ID]                = $this->merchantId;

        return $data;
    }

    public function getVPADetails($vpa)
    {

        $data[self::HANDLE]       = $vpa->getHandle();

        $data[self::USERNAME]     = $vpa->getUsername();

        //TODO: to fetch the merchantId
        //$data[self::MERCHANT_ID]  = $this->merchantId;

        return $data;
    }

    public function saveFTSAccountId($ftsAccountId, $account)
    {
        $account->setFTSAccountId($ftsAccountId);

        switch ($this->type)
        {
            case self::BANK_ACCOUNT:
                $this->repoManager->saveOrFail($account);

            case self::VPA:
                $this->repoManager->saveOrFail($account);

            default:
                throw new LogicException('Type is not supported ' . $this->type);
        }
    }
}
