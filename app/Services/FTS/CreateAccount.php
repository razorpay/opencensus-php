<?php

namespace RZP\Services\FTS;

use Razorpay\Trace\Logger;
use Razorpay\Trace\Logger as Trace;

use RZP\Exception;
use RZP\Models\Vpa;
use RZP\Models\Card;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\Country;
use RZP\Models\BankAccount;
use RZP\Http\RequestHeader;
use RZP\Models\WalletAccount;
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
use RZP\Models\Gateway\File\Processor\Emi\Rbl;
use RZP\Models\FundTransfer\Attempt\Type as Product;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\BankingAccount\Gateway\Rbl\Fields as RblGatewayFields;
use RZP\Models\BankingAccount\Detail\Core as BankingAccountDetailCore;
use RZP\Services\BankingAccountService;

class CreateAccount extends Base
{
    /**
     * Request key to differentiate graceful update of FTS source accounts
     */
    const GRACEFUL_UPDATE      = 'graceful_update';
    const SOURCE_ACCOUNT_CONST = 'source_account';
    const CREDENTIALS          = 'credentials';
    const BANKING_ACCOUNT_ID   = 'banking_account_id';

    const UPI_HANDLE1       = 'upi_handle1';
    const UPI_HANDLE2       = 'upi_handle2';
    const UPI_HANDLE3       = 'upi_handle3';

    const VPAS_DO_NOT_EXIST_ERROR = 'Banking Account Credentials are not generated';
    const VPAS_DO_NOT_MATCH_ERROR = 'Payer VPA does not match any of the generated VPAs';
    const VPAS_FIRST_LETTER_ERROR = 'Payer VPA must begin with Capital P';

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

    protected $sourceAccountTypeIdentifier;

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
        // If card entity's FTS fund account is being created then bu_namespace and tokenised field will
        // be passed in FTS request if the card is a network tokenised card
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

    //Create fund account and source accounts in one go for direct rbl onboarding
    public function createFundAccountAndSourceAccounts(array $sourceAccountCreds, string $product, string $channel): array
    {
        $input[Constants::BANK_ACCOUNT] = $this->getBankingAccountDetails($this->account);
        $input[Constants::CREDENTIALS]  = $sourceAccountCreds;
        $input[Constants::CHANNEL]      = strtoupper($channel);
        $input[Constants::PRODUCT]      = $product;

        $response = $this->createAndSendRequest(parent::DIRECT_SOURCE_ACCOUNTS_CREATION, 'POST', $input);

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

            case Constants::WALLET:
                $this->account = $this->walletCore->getWalletEntity($id);

                $request[Constants::WALLET] = $this->getWalletDetails($this->account);

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
        ];
    }

    public function getCardDetails(Card\Entity $card)
    {
        $cardDetails = [
            Constants::NAME         => $card->getName() ?? "",
            Constants::ISSUER_BANK  => $this->getIssuer($card),
            Constants::VAULT_TOKEN  => $this->getCardVaultToken($card),
            Constants::NETWORK_CODE => $card->getNetworkCode(),
        ];

        $tokenised = ($card->isTokenPan() === true) ? true : $card->isNetworkTokenisedCard();

        $cardDetails[Constants::TOKENISED] = $tokenised;

        $this->setNamespacesInRequest($cardDetails, $card);

        return $cardDetails;
    }

    protected function setNamespacesInRequest(&$cardDetails, $card)
    {
        if ($card->isTokenPan() === true)
        {
            $cardDetails[Constants::BU_NAMESPACE] = Card\BuNamespace::RAZORPAYX_TOKEN_PAN;
        }
        else
        {
            if ($card->isNetworkTokenisedCard() === true)
            {
                $cardDetails[Constants::BU_NAMESPACE] = null;
            }
            else
            {
                $cardDetails[Constants::BU_NAMESPACE] = Card\BuNamespace::RAZORPAYX_NON_SAVED_CARDS;
            }
        }
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
     * Method to Populate wallet details
     * in an array using entity
     *
     * @param $wallet/$walletAccount
     * @return array
     */
    public function getWalletAccountDetails(WalletAccount\Entity $walletAccount):array
    {
        return [
            Constants::PROVIDER             => $walletAccount->getProvider(),
            Constants::BENEFICIARY_MOBILE   => $walletAccount->getPhone(),
            Constants::BENEFICIARY_EMAIL    => $walletAccount->getNumber(),
            Constants::BENEFICIARY_NAME     => $walletAccount->getName(),
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

        if(array_key_exists(Constants::SOURCE_ACCOUNT_TYPE_IDENTIFIER,$content)==true)
        {
            $request += [
                    Constants::SOURCE_ACCOUNT_TYPE_IDENTIFIER => $content[Constants::SOURCE_ACCOUNT_TYPE_IDENTIFIER],
            ];
        }

        if(array_key_exists(Constants::BANK_ACCOUNT_TYPE, $content) === true)
        {
            $request += [
                Constants::BANK_ACCOUNT_TYPE => $content[Constants::BANK_ACCOUNT_TYPE],
            ];
        }

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

            case Constants::WALLET:
                $this->account = $this->walletCore->getWalletEntity($this->accountId);

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
            $startTime = microtime(true);

            Account::dispatch($this->mode, $account->getId(), $account->getEntityName(), $product)->delay(5);

            $this->trace->info(TraceCode::PAYOUT_OPTIMIZATION_FOR_COMPOSITE_TIME_TAKEN, [
                'step'       => 'composite_fts_create_account_dispatch',
                'time_taken' => (microtime(true) - $startTime) * 1000,
            ]);

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
            Constants::CREDENTIALS                     => $input['credentials'],
            Constants::MOZART_IDENTIFIER               => $input['mozartIdentifier'],
            Constants::CONFIGURATION                   => $input['config'],
            Constants::SOURCE_ACCOUNT_TYPE_IDENTIFIER  => $input['sourceAccountTypeIdentifier'],
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
        $this->trace->info(
            TraceCode::FTS_UPDATE_EXISTING_SOURCE_ACCOUNT
        );

        // If we need to append existing source account credentials instead of entirely replacing them
        if ((empty($input[self::SOURCE_ACCOUNT_CONST][self::GRACEFUL_UPDATE]) === false) and
            (boolval($input[self::SOURCE_ACCOUNT_CONST][self::GRACEFUL_UPDATE]) === true))
        {
            // Remove sensitive fields for tracing purposes
            $inputTrace = $input;
            foreach(RblGatewayFields::$sensitiveAccountDetails as $sensitiveAccountDetailKey)
            {
                unset($inputTrace[self::SOURCE_ACCOUNT_CONST][Constants::CREDENTIALS][$sensitiveAccountDetailKey]);
            }

            $this->trace->info(
                TraceCode::FTS_UPDATE_EXISTING_SOURCE_ACCOUNT_GRACEFULLY,
                [
                    'graceful_update_flag' => $input[self::SOURCE_ACCOUNT_CONST][self::GRACEFUL_UPDATE],
                    'input' => $inputTrace,
                ]
            );

            // Creating a transaction as updating banking account details and updating source account
            // should be one atomic operation
            return $this->repo->transaction(function () use ($input)
            {
                try
                {
                    return $this->updateSourceAccountGracefully($input);
                }
                catch(\Throwable $e)
                {
                    $this->trace->traceException(
                        $e,
                        Logger::CRITICAL,
                        TraceCode::FTS_UPDATE_EXISTING_SOURCE_ACCOUNT_GRACEFULLY_FAILED,
                        [
                            'source_account_id'  => $input[self::SOURCE_ACCOUNT_CONST][self::BANKING_ACCOUNT_ID],
                            'message' => $e->getMessage(),
                        ]);

                    return [
                        'message' => 'Graceful update of source account/banking account details failed',
                        'exception' => $e->getMessage(),
                    ];
                }
            });
        }

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

    /**
     * For now, we are only handling RBL CA banking accounts for UPI based banking accounts/source accounts.
     * There is this weird ask from RBL where the UPI creds will be different from other creds,
     * hence the existing RBL CA accounts need to be updated separately.
     *
     * Check "RBL UPI Creds Update" under FTS Dashboard in Admin Dashboard. Also check the existing banking accounts
     *  update LMS form for the new fields
     *
     * @param array $input
     *
     * @return array
     * @throws \RZP\Exception\RuntimeException
     * @throws \Throwable
     */
    protected function updateSourceAccountGracefully(array $input)
    {
        $this->trace->info(
            TraceCode::FTS_PROCESS_REQUEST_TO_UPDATE_SOURCE_ACCOUNT_GRACEFULLY,
            [
                'banking_account_id' => $input[self::SOURCE_ACCOUNT_CONST][self::BANKING_ACCOUNT_ID],
            ]
        );

        /**
         * This array will contain all the sensitive input details after tokenisation
         * @var array
         */
        $tokenisedInput = $this->updateBankingAccountDetails($input);

        $this->trace->info(
            TraceCode::FTS_UPDATE_EXISTING_SOURCE_ACCOUNT_TOKENISED_CREDS,
            [
                'tokenised_creds' => $tokenisedInput,
            ]
        );

        // Replace the existing input with the tokenised creds
        foreach ($tokenisedInput[Constants::CREDENTIALS] as $key => $value)
        {
            $input[self::SOURCE_ACCOUNT_CONST][Constants::CREDENTIALS][$key] = $value;
        }

        $input[self::SOURCE_ACCOUNT_CONST][Constants::FUND_ACCOUNT_ID] = $tokenisedInput[Constants::FUND_ACCOUNT_ID];
        $input[self::SOURCE_ACCOUNT_CONST][self::BANKING_ACCOUNT_ID]   = $tokenisedInput[self::BANKING_ACCOUNT_ID];

        $reformattedInput = $this->formatInputForGracefulSourceAccountUpdate($input);

        $this->trace->info(
            TraceCode::FTS_UPDATE_EXISTING_SOURCE_ACCOUNT_INPUT_FOR_FTS,
            [
                'input' => $reformattedInput,
            ]
        );

        $response = $this->createAndSendRequest(
            parent::BULK_SOURCE_ACCOUNT_UPDATE_URI,
            Requests::PATCH,
            $reformattedInput);

        $this->trace->info(
            TraceCode::FTS_PROCESS_REQUEST_SENT_TO_UPDATE_SOURCE_ACCOUNT_GRACEFULLY,
            [
                'banking_account_id' => $input[self::SOURCE_ACCOUNT_CONST][self::BANKING_ACCOUNT_ID],
                'response'          => $response,
            ]
        );

        return $response;
    }

    /**
     * -To append new creds on API monolith side as well. Banking account details table will get new records
     *
     * @param array $input
     *
     * @return mixed
     */
    protected function updateBankingAccountDetails(array $input)
    {
        $this->trace->info(
            TraceCode::FTS_PROCESS_REQUEST_TO_UPDATE_BANKING_ACCOUNT_DETAILS,
            [
                'banking_account_id' => $input[self::SOURCE_ACCOUNT_CONST][self::BANKING_ACCOUNT_ID],
            ]
        );

        // Need to append new creds on API monolith side as well. Banking account details table will get new records.
        return $this->processRequestToUpdateBankingAccountDetailsGracefully($input[self::SOURCE_ACCOUNT_CONST]);
    }

    protected function processRequestToUpdateBankingAccountDetailsGracefully($srcAccDetails)
    {
        // Fetch all the objects related to the relevant banking account
        $bankingAccountId     = $srcAccDetails[self::BANKING_ACCOUNT_ID];

        /** @var BankingAccount\Entity */
        $bankingAccountEntity = $this->repo->banking_account->findOrFailPublic($bankingAccountId);
        $this->channel        = $bankingAccountEntity->getChannel();
        $processor            = $this->bankingAccountCore->getProcessor($this->channel);

        // This if-clause is put here to specifically cater to RBL UPI onboarding for now, will be made more generic
        // if required in the future.
        if(($this->channel === Channel::RBL) and
           (empty($srcAccDetails[Constants::CREDENTIALS][RblGatewayFields::PAYER_VPA]) === false))
        {
            $this->validateVPAWithGeneratedRblCredentials($bankingAccountEntity->getId(), $srcAccDetails[Constants::CREDENTIALS][RblGatewayFields::PAYER_VPA]);

            // Right now, we are only expecting the VPA of a merchant to be created once.
            // Since the VPA address is generated manually, update requests for VPAs shall be rare.
            // Hence, not checking for already existing VPAs for now.
            $this->vpaCore->createForSource(
                [
                    (new Vpa\Entity())::ADDRESS => $srcAccDetails[Constants::CREDENTIALS][RblGatewayFields::PAYER_VPA],
                ],
                $bankingAccountEntity
            );
        }

        // Note, This fund account ID will probably only be non-empty for activated banking accounts.
        // I checked the DB records, newly created entities didn't have a FTS fund account ID.
        // To be safe, only update RBL UPI creds thru this flow if the status of the banking account entity is activated
        $ftsFundAccountId = $bankingAccountEntity->getFtsFundAccountId();

        // This call will tokenise the input and then store them in the DB/vault as needed. Check out the function
        // implementation for details.
        $tokenisedCreds = (new BankingAccountDetailCore())->updateBankingAccountDetails(
            $srcAccDetails[Constants::CREDENTIALS],
            $bankingAccountEntity,
            $processor
        );

        return [
            Constants::FUND_ACCOUNT_ID => $ftsFundAccountId,
            self::BANKING_ACCOUNT_ID   => $bankingAccountId,
            Constants::CREDENTIALS     => $tokenisedCreds,
        ];
    }

    public function validateVPAWithGeneratedRblCredentials(string $bankingAccountId, string $vpa)
    {
        /** @var BankingAccountService|\RZP\Services\Mock\BankingAccountService $bas */
        $bas = app('banking_account_service');

        $credentials = $bas->getGeneratedRblCredentials($bankingAccountId);

        if (empty($credentials[self::UPI_HANDLE1]) &&
            empty($credentials[self::UPI_HANDLE2]) &&
            empty($credentials[self::UPI_HANDLE3]))
        {
            throw new Exception\BadRequestValidationFailureException(self::VPAS_DO_NOT_EXIST_ERROR,
                null,
                [
                    'entered_vpa'             => $vpa,
                ]);
        }

        if (in_array(strtolower($vpa), [
            strtolower($credentials[self::UPI_HANDLE1]),
            strtolower($credentials[self::UPI_HANDLE2]),
            strtolower($credentials[self::UPI_HANDLE3]),
        ]) === false)
        {
            throw new Exception\BadRequestValidationFailureException(self::VPAS_DO_NOT_MATCH_ERROR,
                null,
                [
                    'entered_vpa'             => $vpa,
                    'available_vpas' => [
                        $credentials[self::UPI_HANDLE1],
                        $credentials[self::UPI_HANDLE2],
                        $credentials[self::UPI_HANDLE3],
                    ]
                ]);
        }

        if ($vpa[0] !== 'P')
        {
            throw new Exception\BadRequestValidationFailureException(self::VPAS_FIRST_LETTER_ERROR,
                null,
                [
                    'entered_vpa'             => $vpa,
                ]);
        }
    }

    /**
     * Modifies the graceful_update request to adhere to API<>FTS Contract
     * @param array $input
     *
     * @return array[]
     */
    protected function formatInputForGracefulSourceAccountUpdate(array $input)
    {
        $input = $input[self::SOURCE_ACCOUNT_CONST];

        return [
            $input[self::BANKING_ACCOUNT_ID] => [
                Constants::CREDENTIALS     => $input[Constants::CREDENTIALS],
                Constants::FUND_ACCOUNT_ID => $input[Constants::FUND_ACCOUNT_ID],
                self::GRACEFUL_UPDATE      => true,
            ],
        ];
    }
}
