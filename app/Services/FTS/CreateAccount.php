<?php

namespace RZP\Services\FTS;

use Razorpay\Trace\Logger as Trace;

use RZP\Models\Vpa;
use RZP\Models\Card;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\Country;
use RZP\Models\BankAccount;
use RZP\Http\Request\Requests;
use RZP\Models\BankingAccount;
use RZP\Constants\IndianStates;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Base\PublicEntity;
use RZP\Exception\LogicException;
use RZP\Models\Settlement\Channel;
use RZP\Models\NodalBeneficiary;
use RZP\Exception\BadRequestException;
use RZP\Jobs\FTS\CreateAccount as Account;
use RZP\Models\Settlement\SlackNotification;
use RZP\Models\FundTransfer\Attempt\Type as Product;
use RZP\Exception\BadRequestValidationFailureException;

class CreateAccount extends Base
{
    protected $status;

    protected $account;

    protected $vpaCore;

    protected $product;

    protected $accountId;

    protected $accountType;

    protected $cardCore;

    protected $bankAccountCore;

    protected $bankingAccountCore;

    protected $nodalBeneficiaryCore;

    protected $channel;

    protected $sourceAccountType;

    public function __construct($app)
    {
        parent::__construct($app);

        $this->vpaCore = new Vpa\Core;

        $this->cardCore = new Card\Core;

        $this->bankAccountCore = new BankAccount\Core;

        $this->bankingAccountCore = new BankingAccount\Core;

        $this->nodalBeneficiaryCore = new NodalBeneficiary\Core;
    }

    /**
     * @param string $id
     * @param string $type
     * @param string $product
     * @param string|null $status
     * @throws LogicException
     */
    public function initialize(string $id, string $type, string $product, string $status=null)
    {
        $this->product     = $product;

        $this->accountId   = $id;

        $this->accountType = $type;

        $this->status      = $status;

        $this->channel     = null;

        $this->fetchAccountByType();
    }

    /**
     * Handler method in service for creation
     * of fund account using FTS
     *
     * @return array
     * @throws \RZP\Exception\RuntimeException
     * @throws \Throwable
     */
    public function createFundAccount(): array
    {
        $input = $this->prepareRequestUsingType();

        $response = $this->createAndSendRequest(parent::FUND_ACCOUNT_CREATE_URI, 'POST', $input);

        if ($this->accountType === Constants::BANKING_ACCOUNT)
        {
            $ftsFundAccountId = array_key_exists(Constants::FUND_ACCOUNT_ID, $response['body']) ?
                $response['body'][Constants::FUND_ACCOUNT_ID] : null;

            if (empty(trim($ftsFundAccountId)) === false)
            {
                $this->saveFtsAccountId($ftsFundAccountId);
            }
        }

        return $response;
    }

    public function createSourceAccount(string $id,
                                        string $ftsAccountId,
                                        array $content,
                                        string $product,
                                        string $channel = 'ICICI')
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
        $type = Constants::SAVING;

        if (empty($this->sourceAccountType) === false)
        {
            $type = $this->sourceAccountType;
        }

        return [
            Constants::IFSC_CODE                  => $ba->getIfscCode(),
            Constants::ACCOUNT_TYPE               => $ba->getAccountType() ?? $type,
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
     */
    public function saveFtsAccountId($ftsAccountId)
    {
             $this->bankingAccountCore->updateBankingAccountWithFtsId($this->account, $ftsAccountId);
    }

    protected function getSourceAccountRequestBody(string $product,
                                                   string $fundAccountId,
                                                   string $channel,
                                                   array $content)
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

    public function prepareRequestUsingType()
    {
        switch ($this->accountType)
        {
            case Constants::BANK_ACCOUNT:
                $request[Constants::BANK_ACCOUNT] = $this->getAccountDetails($this->account);

                if ($this->status === NodalBeneficiary\Status::VERIFIED)
                {
                    $request[Constants::BENEFICIARY_STATUS] = $this->getBeneficiaryStatus($this->account);
                }

                break;

            case Constants::VPA:
                $request[Constants::VPA] = $this->getVpaDetails($this->account);

                break;

            case Constants::BANKING_ACCOUNT:
                $request[Constants::BANK_ACCOUNT] = $this->getBankingAccountDetails($this->account);

                // TODO: this should be generic, hardcoding for now
                $request[Constants::DEFAULT_CHANNEL] = Channel::RBL;
                break;

            case Constants::CARD:
                $request[Constants::CARD] = $this->getCardDetails($this->account);

                break;

            default:
                throw new LogicException('Account Type is not supported ' . $this->accountType);
        }

        // ToDo need to confirm this before merging
        $request[Constants::DEFAULT_CHANNEL] = $this->getDefaultChannelByProductAndAccountType();

        $request[Constants::PRODUCT] = $this->product;

        $request[Constants::MERCHANT_ID] = $this->account->merchant->getId();

        return $request;
    }

    /**
     * Fetch Account By Type
     *
     * @throws LogicException
     */
    public function fetchAccountByType()
    {
        switch ($this->accountType)
        {
            case Constants::BANK_ACCOUNT:
                $this->account = $this->bankAccountCore->getBankAccountEntity($this->accountId);

                break;

            case Constants::VPA:
                $this->account = $this->vpaCore->getVpaEntity($this->accountId);

                break;

            case Constants::BANKING_ACCOUNT:
                $this->account = $this->bankingAccountCore->getBankingAccountEntity($this->accountId);

                break;

            case Constants::CARD:
                $this->account = $this->cardCore->getCardEntity($this->accountId);

                break;

            default:
                throw new LogicException('Account Type is not supported ' . $this->accountType);
        }
    }

    public function isAccountCreatedInFts()
    {
        // We don't want to create account in FTs for test mode
        if ($this->mode === Mode::TEST)
        {
            return true;
        }

        if ((method_exists($this->account, "getFtsFundAccountId") === true) &&
            (empty($this->account->getFtsFundAccountId()) === false))
        {
            return true;
        }

        return false;
    }

    protected function getDefaultChannelByProductAndAccountType()
    {
        if(empty($this->channel) === false)
        {
            return $this->channel;
        }

        if ($this->product === Product::PAYOUT)
        {
            if ($this->accountType === Constants::BANKING_ACCOUNT)
            {
                return Channel::RBL;
            }
        }

        return Channel::YESBANK;
    }

    protected function getBeneficiaryStatus(BankAccount\Entity $ba):array
    {
        return [
            Constants::STATUS           => Constants::COMPLETED,
            Constants::BENEFICIARY_CODE => $ba->getId(),
        ];
    }

    public function callFtsCreateAccount(PublicEntity $account, string $product)
    {
        try
        {
            Account::dispatch($this->mode, $account->getId(), $account->getEntityName(), $product)->delay(5);

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

            (new SlackNotification)->send(
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

    /**
     * Used to create source Account Via dashboard
     *
     * @param array $input
     * @return array
     */
    public function createAccountMappingForFts(array $input)
    {
        (new Validator)->validateInput('create_source_account', $input);

        $result = [];

        try
        {
            $bank_account_id = "";

            $fund_account_id = "";

            if (isset($input['id']) === true) {
                $bank_account_id = $input['id'];
            }

            if (isset($input['fund_account_id']) === true) {
                $fund_account_id = $input['fund_account_id'];
            }

            $this->initialize($bank_account_id, $input['type'], $input['product']);

            $this->channel = $input['channel'];

            $this->sourceAccountType = $input['sourceAccountType'];

            if (empty($fund_account_id) === true) {

                $response = $this->createFundAccount();

                if (empty($response[Constants::BODY][Constants::FUND_ACCOUNT_ID]) === true)
                {
                    throw new BadRequestException(
                        ErrorCode::BAD_REQUEST_ERROR_SOURCE_ACCOUNT_FUND_ACCOUNT_CREATION_FAILED,
                        null,
                        ['response' => $response],
                        'FTS fund Account Id could not stored, Please try again!'
                    );
                }

                $fund_account_id = $response[Constants::BODY][Constants::FUND_ACCOUNT_ID];

                $result += $response;
            }

            $content = $this->generateRequestForSourceAccount($input);

            $data = $this->createSourceAccount(
                $bank_account_id,
                $fund_account_id,
                $content,
                $input['product'],
                $input['channel']);

            $result += $data;
        }
        catch (\Throwable $exception)
        {
            $this->trace->traceException(
                $exception,
                Trace::CRITICAL,
                TraceCode::FTS_SOURCE_ACCOUNT_MAPPING_CREATION_EXCEPTION,
                $input);
        }

        return $result;
    }

    protected function generateRequestForSourceAccount(array $input)
    {
        return [
            Constants::CREDENTIALS       => $input['credentials'],
            Constants::MOZART_IDENTIFIER => $input['mozartIdentifier'],
            Constants::CONFIGURATION     => $input['config'],
        ];
    }

    public function deleteSourceAccount(array $input)
    {
        (new Validator)->validateInput('delete_source_account', $input);

        return $this->createAndSendRequest(
            parent::SOURCE_ACCOUNT_DELETE_URI,
            Requests::DELETE,
            $input);
    }

    //Bulk patch route function for fts source account update.
    public function updateSourceAccount(array $input)
    {
        return $this->createAndSendRequest(
            parent::BULK_SOURCE_ACCOUNT_UPDATE_URI,
            Requests::PATCH,
            $input);
    }

    public function getAccount()
    {
        return $this->account;
    }

    public function initiateBulkBeneficiary(array $input)
    {
        return $this->createAndSendRequest(
            parent::FUND_ACCOUNT_REGISTER_URI,
            Requests::POST,
            $input);
    }

    public function oneOffDbMigrateCron(array $input)
    {
        return $this->createAndSendRequest(
            parent::FTS_ONE_OFF_DB_MIGRATE_URL,
            Requests::PATCH,
            $input);
    }
}
