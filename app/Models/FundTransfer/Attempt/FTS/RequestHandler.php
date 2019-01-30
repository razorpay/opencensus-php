<?php
/**
 * Created by PhpStorm.
 * User: amogh
 * Date: 2019-01-30
 * Time: 14:55
 */

namespace RZP\Models\FundTransfer\Attempt\FTS;

use App;

use RZP\Models\Base;
use RZP\Models\BankAccount;
use RZP\Models\Vpa;
use RZP\Models\FundTransfer\Attempt\Entity;

class RequestHandler extends Base\Core
{
    const MODE              = 'mode';
    const AMOUNT            = 'amount';
    const CHANNEL           = 'channel';
    const PRODUCT           = 'product';
    const TRANSFER          = 'transfer';
    const ENTITY_ID         = 'entity_id';
    const NARRATION         = 'narration';
    const SETTLEMENT        = 'settlement';
    const MERCHANT_ID       = 'merchant_id';
    const INITIATE_AT       = 'initiate_at';
    const FUND_ACCOUNT_ID   = 'fund_account_id';


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

    const BANK_ACCOUNT              = 'bank_account';
    const VPA                       = 'vpa';

    public function createTransferRequest($attempt, $source): array
    {
        $request = [
            self::PRODUCT           => $attempt->getSourceType(),
            self::MERCHANT_ID       => $attempt->merchant->getId(),

            self::TRANSFER => [
                self::MODE              => $attempt->getMode(),
                self::AMOUNT            => $source->getAmount(),
                self::CHANNEL           => $attempt->getChannel(),
                self::ENTITY_ID         => $attempt->getId(),
                self::NARRATION         => $attempt->getNarration(),
                self::INITIATE_AT       => $attempt->getInitiateAt(),
            ],
        ];

        return $request;
    }


    public function sendFTSFundTransferRequest(
        Base\Entity $source,
        Entity $fta)
    {
        $request = $this->createTransferRequest($fta, $source);

        $request = $this->addFTSAccountId($request, $fta);

        $response = App::getFacadeRoot()['fts']->requestFundTransfer($request, true);

        $this->updateSourceAndFTA($fta, $source, $response);
    }

    public function sendFTSFundTransferRequestUsingBankAccount(
        Base\Entity $source,
        BankAccount\Entity $bankAccount,
        Entity $fta )
    {
        $request = $this->createTransferRequest($fta, $source);

        $request = $this->addBankAccountDetails($request, $bankAccount);

        $response = App::getFacadeRoot()['fts']->requestFundTransfer($request, true);

        $this->updateSourceAndFTA($fta, $source, $response);
    }

    public function sendFTSFundTransferRequestUsingVPA(
        Base\Entity $source,
        vpa\Entity $vpa,
        Entity $fta )
    {
        $request = $this->createTransferRequest($fta, $source);

        $request = $this->addVpaDetails($request, $vpa);

        $response = App::getFacadeRoot()['fts']->requestFundTransfer($request, true);

        $this->updateSourceAndFTA($fta, $source, $response);
    }

    public function addFTSAccountId(
        array $request,
        Entity $fta):array
    {
        $request[] = array(
            self::FUND_ACCOUNT_ID   => $fta->bank_account->getFTSAccountId(),
        );

        return $request;
    }

    public function addBankAccountDetails(
        array $request,
        BankAccount\Entity $ba):array
    {
        $request['bank_account'] = [
            self::TYPE                       => $ba->getType(),
            self::BENEFICIARY_PIN            => $ba->getBeneficiaryPin(),
            self::BENEFICIARY_NAME           => $ba->getBeneficiaryName(),
            self::BENEFICIARY_CODE           => $ba->getBeneficiaryCode(),
            self::BENEFICIARY_CITY           => $ba->getBeneficiaryCity(),
            self::BENEFICIARY_STATE          => $ba->getBeneficiaryState(),
            self::BENEFICIARY_MOBILE         => $ba->getBeneficiaryMobile(),
            self::BENEFICIARY_ADDRESS        => $ba->getBeneficiaryAddress1(),
            self::BENEFICIARY_COUNTRY        => $ba->getBeneficiaryCountry(),
            self::BENEFICIARY_EMAIL_ID       => $ba->getBeneficiaryEMail(),
            self::BENEFICIARY_IFSC_CODE      => $ba->getIfscCode(),
            self::BENEFICIARY_BANK_NAME      => $ba->getBankName(),
            self::BENEFICIARY_ACCOUNT_TYPE   => $ba->getAccountType(),
            self::BENEFICIARY_ACCOUNT_NUMBER => $ba->getAccountNumber(),
        ];

        return $request;
    }

    public function addVpaDetails(
        array $request,
        vpa\Entity $vpa):array
    {
        $request['vpa'] = [
            self::HANDLE       => $vpa->getHandle(),
            self::USERNAME     => $vpa->getUsername(),
        ];

        return $request;
    }

    public function updateSourceAndFTA(
        Entity $fta,
        Base\Entity $source,
        array $response)
    {
        $this->updateFundTransferAttempt($fta, $response);

        $fta->setStatus($response['status']);

        $this->repo->saveOrFail($fta);

        $source->setFTSTransferId($response['transfer_id']);

        $this->repo->saveOrFail($source);
    }
}