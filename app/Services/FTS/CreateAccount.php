<?php

namespace RZP\Services\FTS;

use Razorpay\Trace\Logger as Trace;

use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Vpa;
use RZP\Models\Card;
use RZP\Trace\TraceCode;
use RZP\Constants\Country;
use RZP\Models\BankAccount;
use RZP\Models\BankingAccount;
use RZP\Constants\IndianStates;
use RZP\Models\Base\PublicEntity;
use RZP\Exception\LogicException;
use RZP\Models\Settlement\Channel;
use RZP\Jobs\FTS\CreateAccount as Account;

class CreateAccount extends Base
{
    protected $account;

    protected $vpaCore;

    protected $product;

    protected $cardCore;

    protected $bankAccountCore;

    protected $bankingAccountCore;

    public function __construct($app)
    {
        parent::__construct($app);

        $this->vpaCore = new Vpa\Core;

        $this->cardCore = new Card\Core;

        $this->bankAccountCore = new BankAccount\Core;

        $this->bankingAccountCore = new BankingAccount\Core;
    }

    /**
     * Handler method in service for creation
     * of fund account using FTS
     *
     * @param string $id
     * @param string $type
     * @param string $product
     * @return array
     * @throws LogicException
     * @throws \RZP\Exception\RuntimeException
     * @throws \Throwable
     */
    public function createFundAccount(string $id, string $type, string $product): array
    {
        $this->product = $product;

        $input = $this->makeRequestUsingType($id, $type, $product);

        $response = $this->createAndSendRequest(parent::FUND_ACCOUNT_CREATE_URI, 'POST', $input);

        $ftsFundAccountId = array_key_exists(Constants::FUND_ACCOUNT_ID, $response['body']) ?
            $response['body'][Constants::FUND_ACCOUNT_ID] : null;

        if (empty(trim($ftsFundAccountId)) === false)
        {
            $this->saveFtsAccountId($ftsFundAccountId, $type);
        }

        return $response;
    }

    public function createSourceAccount(string $id, string $ftsAccountId, array $content,
                                        string $product, string $channel = 'ICICI')
    {
        $input = $this->getSourceAccountRequestBody($product, $ftsAccountId, $channel, $content);

        $response = $this->createAndSendRequest(parent::SOURCE_ACCOUNT_CREATE_URI, 'POST', $input);

        return $response;
    }

    /**
     * Method to make request to be sent to FTS
     * based on the type of account
     *
     * @param string $id
     * @param string $type
     * @param string $product
     * @return mixed
     * @throws LogicException
     */
    public function makeRequestUsingType(string $id, string $type, string $product)
    {
        // ToDo need to confirm this before merging
        $request[Constants::DEFAULT_CHANNEL] = Channel::YESBANK;

        $request[Constants::PRODUCT] = $product;

        switch ($type)
        {
            case Constants::BANK_ACCOUNT:
                $this->account = $this->bankAccountCore->getBankAccountEntity($id);

                $request[Constants::BANK_ACCOUNT] = $this->getAccountDetails($this->account);

                break;

            case Constants::VPA:
                $this->account = $this->vpaCore->getVpaEntity($id);

                $request[Constants::VPA] = $this->getVpaDetails($this->account);

                break;

            case Constants::BANKING_ACCOUNT:
                $this->account = $this->bankingAccountCore->getBankingAccountEntity($id);

                $request[Constants::BANK_ACCOUNT] = $this->getBankingAccountDetails($this->account);

                // TODO: this should be generic, hardcoding for now
                $request[Constants::DEFAULT_CHANNEL] = Channel::RBL;

                break;

            case Constants::CARD:
                $this->account = $this->cardCore->getCardEntity($id);

                $request[Constants::CARD] = $this->getCardDetails($this->account);

                break;

            default:
                throw new LogicException('Account Type is not supported ' . $type);
        }

        $request[Constants::MERCHANT_ID] = $this->account->merchant->getId();

        return $request;
    }

    /**
     * Method to Populate bank account details
     * in an array using entity
     *
     * @param $ba
     * @return array
     */
    public function getAccountDetails(BankAccount\Entity $ba):array
    {
        return [
            Constants::IFSC_CODE                  => $ba->getIfscCode(),
            Constants::ACCOUNT_TYPE               => $ba->getAccountType() ?? Constants::SAVING,
            Constants::ACCOUNT_NUMBER             => $ba->getAccountNumber(),
            Constants::BENEFICIARY_NAME           => $ba->getBeneficiaryName(),
            Constants::BENEFICIARY_CITY           => $ba->getBeneficiaryCity(),
            Constants::BENEFICIARY_EMAIL          => $ba->getBeneficiaryEmail(),
            Constants::BENEFICIARY_STATE          => $ba->getBeneficiaryState(),
            Constants::BENEFICIARY_MOBILE         => $ba->getBeneficiaryMobile(),
            Constants::IS_VIRTUAL_ACCOUNT         => $ba->isVirtual(),
            Constants::BENEFICIARY_ADDRESS        => $ba->getBeneficiaryAddress1(),
            Constants::BENEFICIARY_COUNTRY        => $ba->getBeneficiaryCountry(),
            Constants::BENEFICIARY_BANK_NAME      => $ba->getBankName(),
        ];
    }

    public function getBankingAccountDetails(BankingAccount\Entity $ba): array
    {
        return [
            Constants::IFSC_CODE                  => $ba->getAccountIfsc(),
            Constants::ACCOUNT_TYPE               => strtoupper($ba->getAccountType()),
            Constants::ACCOUNT_NUMBER             => $ba->getAccountNumber(),
            Constants::BENEFICIARY_NAME           => $ba->getBeneficiaryName(),
            Constants::BENEFICIARY_CITY           => $ba->getBeneficiaryCity(),
            Constants::BENEFICIARY_EMAIL          => $ba->getBeneficiaryEmail(),
            Constants::BENEFICIARY_STATE          => IndianStates::getStateCode($ba->getBeneficiaryState()),
            Constants::BENEFICIARY_MOBILE         => $ba->getBeneficiaryMobile(),
            Constants::BENEFICIARY_ADDRESS        => $ba->getBeneficiaryAddress1(),
            Constants::BENEFICIARY_COUNTRY        => Country::getCountryCode(strtolower($ba->getBeneficiaryCountry())),
            Constants::BENEFICIARY_BANK_NAME      => $ba->getChannel(),
        ];
    }

    public function getCardDetails(Card\Entity $card)
    {
        return [
            Constants::NAME         => $card->getName(),
            Constants::ISSUER_BANK  => $this->getIssuer($card),
            Constants::VAULT_TOKEN  => $this->getCardVaultToken($card),
            Constants::NETWORK_CODE => $card->getNetworkCode(),
        ];
    }

    /**
     * Method to Populate vpa details
     * in an array using entity
     *
     * @param $vpa
     * @return array
     */
    public function getVpaDetails(Vpa\Entity $vpa):array
    {
        return [
            Constants::HANDLE       => $vpa->getHandle(),
            Constants::USERNAME     => $vpa->getUsername(),
        ];
    }

    /**
     * Method to persist fts_fund_account_id returned in response
     * to account entities of specific types
     *
     * @param $ftsAccountId
     * @param $type
     * @throws LogicException
     */
    public function saveFtsAccountId($ftsAccountId, $type)
    {
        switch ($type)
        {
            case Constants::BANK_ACCOUNT:
                $this->bankAccountCore->updateBankAccountWithFtsId($this->account, $ftsAccountId);

                break;

            case Constants::VPA:
                $this->vpaCore->updateVpaWithFtsId($this->account, $ftsAccountId);

                break;

            case Constants::BANKING_ACCOUNT:
                $this->bankingAccountCore->updateBankingAccountWithFtsId($this->account, $ftsAccountId);

                break;

            default:
                throw new LogicException('Account Type is not supported ' . $type);
        }
    }

    protected function getSourceAccountRequestBody(string $product, string $fundAccountId, string $channel, array $content)
    {
        $request = [
            Constants::PRODUCT              => $product,
            Constants::CREDENTIALS          => $content[Constants::CREDENTIALS],
            Constants::MOZART_IDENTIFIER    => $content[Constants::MOZART_IDENTIFIER],
            Constants::FUND_ACCOUNT_ID      => intval($fundAccountId),
            Constants::CHANNEL              => strtoupper($channel),
            Constants::CONFIGURATION        => $content[Constants::CONFIGURATION],
        ];

        return $request;
    }


    public function callFtsCreateAccount(PublicEntity $account, string $product)
    {
        try
        {
            Account::dispatch($this->mode, $account->getId(), $account->getEntityName(), $product);

            $this->trace->info(
                TraceCode::FTS_CREATE_ACCOUNT_JOB_DISPATCHED,
                [
                    'product'      => $product,
                    'account_type' => $account->getEntityName(),
                    'account_id'   => $account->getId(),
                ]);
        }
        catch(\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::FTS_CREATE_ACCOUNT_DISPATCH_FAILED,
                [
                    'product'      => $product,
                    'account_type' => $account->getEntityName(),
                    'account_id'   => $account->getId(),
                ]);
        }
    }

    protected function getCardVaultToken(Card\Entity $card)
    {
        $token = $card->getCardVaultToken();

        if ($token === null)
        {
            $this->trace->error(
                TraceCode::CARD_TOKEN_IS_NOT_AVAILABLE,
                [
                    'card_id' => $card->getId()
                ]);

            (new SlackNotification())->send(
                'Vault token missing',
                [
                    'card_id' => $card->getId()
                ],
                null, 1, 'fts_alerts');
        }

        return $token;
    }

    protected function getIssuer(Card\Entity $card)
    {
        $iin = $card->iinRelation;

        if (empty($iin) === true)
        {
            throw new BadRequestValidationFailureException("iin is not valid mode for issuer");
        }

        return $iin->getIssuer();
    }
}
