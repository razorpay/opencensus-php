<?php

namespace RZP\Models\FundAccount;

use Carbon\Carbon;
use Lib\PhoneBook;

use RZP\Exception;
use RZP\Models\Vpa;
use RZP\Models\Base;
use RZP\Models\Card;
use RZP\Trace\Tracer;
use RZP\Models\Payout;
use RZP\Models\Contact;
use RZP\Models\Feature;
use RZP\Models\FundAccount;
use RZP\Constants\Mode;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Traits\TrimSpace;
use RZP\Constants\Timezone;
use RZP\Models\BankAccount;
use RZP\Models\WalletAccount;
use RZP\Constants\HyperTrace;
use RZP\Constants\Entity as E;
use RZP\Services\FTS\Constants;
use RZP\Exception\LogicException;
use RZP\Constants as RZPConstants;
use Razorpay\Trace\Logger as Trace;
use RZP\Services\FTS\CreateAccount;
use RZP\Exception\BadRequestException;
use RZP\Models\Contact\Entity as ContactEntity;
use RZP\Services\Pagination\Entity as PaginationEntity;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\WalletAccount\Validator as WalletAccountValidator;
use RZP\Models\FundAccount\DetailsPropagator\Core as DetailsPropagator;

/**
 * Class Core
 *
 * @package RZP\Models\FundAccount
 */
class Core extends Base\Core
{
    use TrimSpace;

    /*
     * These regex are based on the validators used in fund account creation for bank account and vpa type fund
     * accounts, if those validators are changed, these regex should be updated accordingly as well.
     */
    const REGEX_FOR_REMOVING_WHITE_SPACES_AND_SPECIAL_CHARACTERS = "/[^a-zA-Z0-9]+/";

    const REGEX_FOR_REMOVING_WHITE_SPACES_AND_SPECIAL_CHARACTERS_FROM_VPA_USERNAME = "/[^a-zA-Z0-9.-]+/";

    const REGEX_FOR_REMOVING_WHITE_SPACES_AND_SPECIAL_CHARACTERS_FROM_BANK_ACCOUNT_NAME =
        "/[^a-zA-Z0-9-&\'._()\/]+/";

    const DEFAULT_COUNTRY_CODE = '+91';

    protected $vendorPaymentService;

    public function __construct()
    {
        parent::__construct();

        $this->vendorPaymentService = $this->app['vendor-payment'];
    }

    /**
     * @param array $input
     * @param Merchant\Entity $merchant
     * @param Base\PublicEntity|null $source
     * @param bool $createDuplicate
     * @param string|null $batchId
     * @param bool $allowRZPFeesFundAccountCreation
     *
     * @return Entity
     *
     * @throws BadRequestException
     */
    public function create(array $input,
                           Merchant\Entity $merchant,
                           Base\PublicEntity $source = null,
                           bool $createDuplicate = false,
                           string $batchId = null,
                           bool $allowRZPFeesFundAccountCreation = false): Entity
    {
        $traceRequest = $this->unsetSensitiveCardDetails($input);

        $input = $this->trimSpaces($input);

        $this->trace->info(TraceCode::FUND_ACCOUNT_CREATE_REQUEST, $traceRequest);

        if ((isset($input[Entity::ACCOUNT_TYPE]) === true) and
            (strtolower($input[Entity::ACCOUNT_TYPE]) ===  Entity::WALLET))
        {
            $input = $this->constructWalletAccountFundAccountRequest($input);
        }

        if (isset($input[Entity::IDEMPOTENCY_KEY]) === true)
        {
            $result = $this->repo->fund_account->fetchByIdempotentKey($input[Entity::IDEMPOTENCY_KEY],
                $merchant->getId(),
                $batchId);

            if ($result !== null)
            {
                return $result;
            }
        }

        // allowRZPFeesFundAccountCreation is only set to true when fund account is created at merchant activation.
        if ((empty($source) === false) and
            ($source->getEntityName() === Entity::CONTACT))
        {
            if ($allowRZPFeesFundAccountCreation === false)
            {
                // If the corresponding contact is of type 'rzp_fees', we won't allow the merchant
                // to create the fund account

                $contactType = $source->getType();

                if ((Contact\Type::isInInternal($contactType) === true) and
                    ($contactType === Contact\Type::RZP_FEES))
                {
                    throw new BadRequestException(
                        ErrorCode::BAD_REQUEST_INTERNAL_FUND_ACCOUNT_CREATION_NOT_PERMITTED,
                        null,
                        [
                            'contact_id' => $source->getId(),
                            'input'      => $traceRequest
                        ]);
                }
            }

            $this->internalContactChecks($source, $traceRequest);
        }

        if (($merchant->getId() === Merchant\Account::MEDLIFE) or
            ($merchant->getId() === Merchant\Account::OKCREDIT))
        {
            $this->modifyRequestForBackwardCompatibility($input);
        }

        (new Validator)->setStrictFalse()->validateInput('create', $input);

        $accountDetails = $this->getAccountDetailsForInput($input);

        $uniqueHash = null;

        $uniqueConsistentHash = null;

        if ($merchant->isFeatureEnabled(Feature\Constants::SKIP_CONTACT_DEDUP_FA_BA) === true)
        {
            $uniqueConsistentHash = $this->generateUniqueHashForConsistentFundAccount($input[Entity::ACCOUNT_TYPE],
                                                                                      $merchant,
                                                                                      $accountDetails,
                                                                                      $source);
        }
        $uniqueHash = $this->generateUniqueHashForFundAccount($input[Entity::ACCOUNT_TYPE],
                                                              $merchant,
                                                              $accountDetails,
                                                              $source);

        $hash = (empty($uniqueConsistentHash) === true)? $uniqueHash : $uniqueConsistentHash;

        if (($source instanceof Contact\Entity) and
            ($createDuplicate === false))
        {
            $fundAccount = $this->checkAndGetFundAccountUsingHashOrFallback($merchant,
                                                                            $input,
                                                                            $uniqueHash,
                                                                            $uniqueConsistentHash,
                                                                            $source,
                                                                            $batchId);

            if (empty($fundAccount) === false)
            {
                return $fundAccount;
            }
        }

        $fundAccount = (new Entity);

        // This needs to be done before the build since validator
        // uses the merchant association to check for a feature.
        $fundAccount->merchant()->associate($merchant);

        $fundAccount = $fundAccount->build($input);

        $account = $this->createAccount($input, $merchant, $source);

        $fundAccount->source()->associate($source);

        $fundAccount->account()->associate($account);

        if (empty($batchId) === false)
        {
            $fundAccount->setBatchId($batchId);
        }

        if (empty($hash) === false)
        {
            $fundAccount->setUniqueHash($hash);
        }

        $this->repo->saveOrFail($fundAccount);

        $mode = app('rzp.mode') ? app('rzp.mode') : Mode::LIVE;

        DetailsPropagator::dispatchToQueue($mode, $fundAccount->getPublicId());

        $this->createFTSAccountForFundAccount($input, $fundAccount, $source);

        $this->trace->info(TraceCode::FUND_ACCOUNT_CREATED,
            [
                E::FUND_ACCOUNT => $fundAccount->getId(),
            ]);

        Metric::pushCreateMetrics($fundAccount);

        return $fundAccount;
    }

    public function createForCompositePayout(array $input,
                                             Merchant\Entity $merchant,
                                             Contact\Entity $contact,
                                             array $traceData,
                                             bool $compositePayoutSaveOrFail = true,
                                             array $metadata = []): Entity
    {
        $this->trace->info(TraceCode::FUND_ACCOUNT_CREATE_REQUEST_FOR_COMPOSITE_PAYOUT, [
            'input'             => $traceData,
            'save_or_fail_flag' => $compositePayoutSaveOrFail,
            'metadata'          => $metadata
        ]);

        if ((isset($input[Entity::ACCOUNT_TYPE]) === true) and
            (strtolower($input[Entity::ACCOUNT_TYPE]) ===  Entity::WALLET))
        {
            $input = $this->constructWalletAccountFundAccountRequest($input);
        }

        $uniqueHash = null;

        $uniqueConsistentHash = null;

        $accountDetails = $this->getAccountDetailsForInput($input);

        if ($merchant->isFeatureEnabled(Feature\Constants::SKIP_CONTACT_DEDUP_FA_BA) === true)
        {
            $uniqueConsistentHash = $this->generateUniqueHashForConsistentFundAccount($input[Entity::ACCOUNT_TYPE],
                                                                                      $merchant,
                                                                                      $accountDetails,
                                                                                      $contact);
        }
        $uniqueHash = $this->generateUniqueHashForFundAccount($input[Entity::ACCOUNT_TYPE],
                                                              $merchant,
                                                              $accountDetails,
                                                              $contact);

        $hash = (empty($uniqueConsistentHash) === true)? $uniqueHash : $uniqueConsistentHash;

        $fundAccount = $this->checkAndGetFundAccountUsingHashOrFallback($merchant,
                                                                        $input,
                                                                        $uniqueHash,
                                                                        $uniqueConsistentHash,
                                                                        $contact);

        if (empty($fundAccount) === false)
        {
            return $fundAccount;
        }

        $fundAccount = (new Entity);

        // This needs to be done before the build since validator
        // uses the merchant association to check for a feature.
        $fundAccount->merchant()->associate($merchant);

        $fundAccount = $fundAccount->build($input);

        $account = $this->createAccount($input, $merchant, $contact, $compositePayoutSaveOrFail);

        $fundAccount->source()->associate($contact);

        $fundAccount->account()->associate($account);

        if (empty($hash) === false)
        {
            $fundAccount->setUniqueHash($hash);
        }

        if (empty($metadata) === false)
        {
            if (array_key_exists(Entity::ID, $metadata) === true)
            {
                $fundAccount->setId($metadata[Entity::ID]);
            }

            if (array_key_exists(Entity::CREATED_AT, $metadata) === true)
            {
                $fundAccount->setCreatedAt($metadata[Entity::CREATED_AT]);
            }
        }

        if ($compositePayoutSaveOrFail === true)
        {
            $this->repo->saveOrFailWithoutEsSync($fundAccount);

            Metric::pushCreateMetrics($fundAccount);
        }
        else
        {
            $fundAccount->setId(Base\UniqueIdEntity::generateUniqueId());

            $fundAccount->setCreatedAt(Carbon::now(Timezone::IST)->getTimestamp());
        }

        $this->trace->info(TraceCode::FUND_ACCOUNT_CREATED_FOR_COMPOSITE_PAYOUT,
                           [
                               E::FUND_ACCOUNT     => $fundAccount->getId(),
                               'save_or_fail_flag' => $compositePayoutSaveOrFail
                           ]);

        return $fundAccount;
    }

    /**
     * @param Merchant\Entity $merchant
     * @param array $input
     * @param string $uniqueHash
     * @param Base\PublicEntity|null $source
     * @param string|null $batchId
     * @param string|null $uniqueConsistentHash
     *
     * @return Entity|null
     */
    protected function checkAndGetFundAccountUsingHashOrFallback(Merchant\Entity $merchant,
                                                                 array $input,
                                                                 string $uniqueHash = null,
                                                                 string $uniqueConsistentHash = null,
                                                                 Base\PublicEntity $source = null,
                                                                 string $batchId = null): ?Entity
    {
        $fundAccount = null;

        $hash = (empty($uniqueConsistentHash) === true)? $uniqueHash : $uniqueConsistentHash;

        if (empty($uniqueConsistentHash) === false)
        {
            $fundAccount = $this->repo->fund_account->getFundAccountWithSimilarDetailsFromHash($uniqueConsistentHash);

            if (empty($fundAccount) === false)
            {
                $this->trace->info(
                    TraceCode::DUPLICATE_FUND_ACCOUNT_FOUND_USING_HASH,
                    [
                        Entity::ID          => $fundAccount->getId(),
                        Entity::BATCH_ID    => $batchId,
                        Entity::UNIQUE_HASH => $uniqueConsistentHash,
                    ]);

                return $fundAccount;
            }

            $fundAccount = $this->repo->fund_account->getFundAccountWithSimilarDetailsFromHash($uniqueHash);

            if (empty($fundAccount) === false)
            {
                $this->trace->info(
                    TraceCode::DUPLICATE_FUND_ACCOUNT_FOUND_USING_HASH,
                    [
                        Entity::ID                            => $fundAccount->getId(),
                        Entity::BATCH_ID                      => $batchId,
                        Entity::UNIQUE_HASH . '_expected'     => $uniqueConsistentHash,
                        Entity::UNIQUE_HASH . '_of_duplicate' => $fundAccount->getUniqueHash(),
                    ]);

                $fundAccount = $this->updateDuplicateFundAccountWithHash(
                    $fundAccount,
                    $uniqueConsistentHash,
                    $merchant,
                    $source
                );

                return $fundAccount;
            }
        }
        else
        {
            $fundAccount = $this->repo->fund_account->getFundAccountWithSimilarDetailsFromHash($uniqueHash);

            if (empty($fundAccount) === false)
            {
                $this->trace->info(
                    TraceCode::DUPLICATE_FUND_ACCOUNT_FOUND_USING_HASH,
                    [
                        Entity::ID          => $fundAccount->getId(),
                        Entity::BATCH_ID    => $batchId,
                        Entity::UNIQUE_HASH => $uniqueHash,
                    ]);

                return $fundAccount;
            }
        }

        if (empty($fundAccount) === true)
        {
            $fundAccount = $this->repo->fund_account->getFundAccountWithSimilarDetails($input, $merchant, $source);

            if (empty($fundAccount) === false)
            {
                $this->trace->info(
                    TraceCode::DUPLICATE_FUND_ACCOUNT_FOUND_USING_FALLBACK,
                    [
                        Entity::ID                            => $fundAccount->getId(),
                        Entity::BATCH_ID                      => $batchId,
                        Entity::UNIQUE_HASH . '_of_input'     => $hash,
                        Entity::UNIQUE_HASH . '_of_duplicate' => $fundAccount->getUniqueHash(),
                    ]);

                if (empty($hash) === false)
                {
                    $fundAccount = $this->updateDuplicateFundAccountWithHash($fundAccount, $hash, $merchant, $source);
                }

                return $fundAccount;
            }
        }

        return null;
    }

    /**
     * We were accepting the account details object in the `details` key, and then changed to accept this in a
     * key with a name corresponding to the account_type -> `bank_account` or `vpa`.
     *
     * This function handles this backward compatibilty modification of the request.
     *
     * UPDATE : We are deprecating `details` in all fund_account API requests. This function
     * is now used by fund_account_validation so that there is no change in the fund_account_validation APIs
     *
     * Consumers can send the details in only `bank_account`|`vpa`
     * Internally, the fund_account_validation API can still send details in `bank_account`|`vpa`|`details`
     *
     * @param array $input
     */
    public function modifyRequestForBackwardCompatibility(array & $input)
    {
        //
        // If the `details` key is unset, we assume the details are present in the new structure
        // under `bank_account` or `vpa`
        //
        if (isset($input[Entity::DETAILS]) === false)
        {
            return;
        }

        //
        // `account_type` is a required field, so if unset we just return
        // and let this fail at the Entity build validation stage.
        //
        if (isset($input[Entity::ACCOUNT_TYPE]) === false)
        {
            return;
        }

        $accountType = $input[Entity::ACCOUNT_TYPE];

        $input[$accountType] = $input[Entity::DETAILS];

        unset($input[Entity::DETAILS]);
    }

    protected function createAccount(array $input,
                                     Merchant\Entity $merchant,
                                     Base\PublicEntity $source = null,
                                     bool $compositePayoutSaveOrFail = true): Base\PublicEntity
    {
        $accountType = $input[Entity::ACCOUNT_TYPE];

        $accountInput = $input[$accountType];

        $account = null;

        switch ($accountType)
        {
            case Type::BANK_ACCOUNT:
                $account = (new BankAccount\Core)->createBankAccountForFundAccount($accountInput, $merchant, $source);
                break;

            case Type::VPA:
                $account = (new Vpa\Core)->createForSource($accountInput, $source, $compositePayoutSaveOrFail);
                break;

            case Type::CARD:
                $isScroogeRequest = false;

                if (isset($accountInput[Card\Entity::TOKEN]) === true)
                {
                    $isScroogeRequest = true;
                    // we will fetch the card details from vault token and modify the input so that rest of the
                    // account creation flow can be used same as account creation with card number.
                    $accountInput = (new Card\Core)->fillCardDetailsWithVaultToken($accountInput, $merchant);
                }

                $this->transformAccountInputForCard($accountInput);

                $traceRequest = $this->unsetSensitiveDetailsForTracing($accountInput, $isScroogeRequest);

                $this->trace->info(TraceCode::TRANSFORM_INPUT_FOR_CARD_FUND_ACCOUNT_CREATION, $traceRequest);

                $this->checkIfTokenPanIsValid($accountInput);

                if ((isset($accountInput[Card\Entity::TOKEN_ID]) === true) and
                    ($merchant->isFeatureEnabled(Feature\Constants::ALLOW_NON_SAVED_CARDS) === true))
                {
                    $token = $this->repo->token->findByPublicId($accountInput[Card\Entity::TOKEN_ID]);

                    // token entity will always have an associated card entity because of foreign key constraint
                    $account = $token->card;

                    // For now, we are blocking the saved card flow if the token entity is created by a different merchant
                    // or if the card characteristics don't match with that of a network Tokenised Card
                    $this->checkIfSavedCardFlowWithTokenIdIsAllowed($token, $account, $merchant);

                    (new Card\Core)->checkIfCardIsSupportedAndEnqueueForBeneficiaryRegistration($account, $merchant);

                    break;
                }

                $this->setDummyValuesForCardIfNeeded($accountInput);

                $account = (new Card\Core)->createForFundAccount($accountInput, $merchant, $compositePayoutSaveOrFail);
                break;

            case Type::WALLET_ACCOUNT:
                $account = (new WalletAccount\Core)->createForSource($accountInput, $source);
                break;

            default:
                throw new LogicException('Creation logic not defined for fund account type: ' . $accountType);
        }

        return $account;
    }

    protected function setDummyValuesForCardIfNeeded(&$accountInput)
    {
        // Card number is validated as part of fund_account create validator itself.
        $network = Card\Network::detectNetwork(substr($accountInput[Card\Entity::NUMBER], 0, 6));

        // cvv needs to be passed otherwise card creation will fail if it's
        // not present, hence passing a dummy value. It's not stored anyways.
        $accountInput[Card\Entity::CVV] = $accountInput[Card\Entity::CVV] ?? Card\Entity::getDummyCvv($network);

        // If the expiry is sent, we use that to validate and such.
        // If the expiry is not sent, we use a dummy expiry.
        // We do not expose expiry in either way.
        // Expiry is mandatory for card creation for non tokenised flow.
        // For tokenised = true, the expiry dates actually correspond to token expiry dates and
        // hence can be set to null
        if ((isset($accountInput[Card\Entity::TOKENISED]) === true) and
            (boolval($accountInput[Card\Entity::TOKENISED]) === true))
        {
            $accountInput[Card\Entity::EXPIRY_MONTH] = $accountInput[Card\Entity::EXPIRY_MONTH] ?? null;
            $accountInput[Card\Entity::EXPIRY_YEAR]  = $accountInput[Card\Entity::EXPIRY_YEAR] ?? null;
        }
        else
        {
            $accountInput[Card\Entity::EXPIRY_MONTH] = $accountInput[Card\Entity::EXPIRY_MONTH] ?? Card\Entity::DUMMY_EXPIRY_MONTH;
            $accountInput[Card\Entity::EXPIRY_YEAR]  = $accountInput[Card\Entity::EXPIRY_YEAR] ?? Card\Entity::DUMMY_EXPIRY_YEAR;
        }

        // If name is sent, we use that. We also expose it.
        // If name is not sent, we use a dummy name. We do not expose it.
        // Name is mandatory for card creation.
        $accountInput[Card\Entity::NAME] = $accountInput[Card\Entity::NAME] ?? Card\Entity::DUMMY_NAME;
    }

    public function transformAccountInputForCard(&$accountInput)
    {
        $inputType = $accountInput[Card\Entity::INPUT_TYPE] ?? null;

        //Setting default input_type to card
        if (isset($inputType) === false)
        {
            $inputType = Card\InputType::CARD;
        }

        switch ($inputType)
        {
            case Card\InputType::CARD:
                $accountInput[Card\Entity::TOKENISED] = false;

                break;

            case Card\InputType::SERVICE_PROVIDER_TOKEN:
                $accountInput[Card\Entity::TOKENISED] = true;

                break;

            case Card\InputType::RAZORPAY_TOKEN:
            default:
                break;
        }

        unset($accountInput[Card\Entity::INPUT_TYPE]);
    }

    public function checkIfTokenPanIsValid($accountInput)
    {
        if ((isset($accountInput[Card\Entity::TOKENISED]) === true) and
            (boolval($accountInput[Card\Entity::TOKENISED]) === true) and
            (array_key_exists(Card\Entity::NUMBER, $accountInput) === true))
        {
            $tokenizedRange = substr($accountInput[Card\Entity::NUMBER], 0, 9);

            $iinNumber = Card\IIN\IIN::getTransactingIinforRange($tokenizedRange) ?? null;

            if ($iinNumber === null)
            {
                $this->trace->error(TraceCode::INVALID_TOKEN_PAN_PROVIDED,
                                    [
                                        'tokenized_range' => $tokenizedRange,
                                        'is_tokenised'    => $accountInput[Card\Entity::TOKENISED]
                                    ]);

                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_CARD_NOT_SUPPORTED_FOR_FUND_ACCOUNT,
                    null,
                    [],
                    "Token not supported for fund account creation."
                );
            }
        }
    }

    public function checkIfSavedCardFlowWithTokenIdIsAllowed($token, $card, $merchant)
    {
        $isSavedCardAllowed = (($card->isNetworkTokenisedCard() === true) and
                               (empty($card->getTokenIin()) === false) and
                               (empty($card->getTrivia()) === true));

        $isTokenEntityAllowed = ($token->merchant->getId() === $merchant->getId());

        if (($isSavedCardAllowed === false) or
            ($isTokenEntityAllowed === false))
        {
            $this->trace->error(TraceCode::TOKEN_ENTITY_BELONGS_TO_DIFFERENT_MERCHANT,
                                [
                                    'merchant_match'               => $isTokenEntityAllowed,
                                    'correct_card_characteristics' => $isSavedCardAllowed,
                                    'token_merchant_id'            => $token->merchant->getId(),
                                    'payout_merchant_id'           => $merchant->getId(),
                                    'is_network_tokenised_card'    => $card->isNetworkTokenisedCard(),
                                    'trivia'                       => $card->getTrivia(),
                                    'token_iin'                    => $card->getTokenIin()
                                ]);

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_CARD_NOT_SUPPORTED_FOR_FUND_ACCOUNT,
                null,
                [],
                "Token not supported for fund account creation."
            );
        }
    }

    public function update(Entity $fundAccount, array $input): Entity
    {
        $this->trace->info(
            TraceCode::FUND_ACCOUNT_UPDATE_REQUEST,
            [
                'id'     => $fundAccount->getId(),
                'entity' => $fundAccount->toArray(),
                'input'  => $input,
            ]);

        // If the corresponding contact is of type 'rzp_fees', we won't allow the merchant to update the fund account
        if (($fundAccount->getSourceType() === Entity::CONTACT) and
            (empty($fundAccount->getSourceId()) === false))
        {
            $contactType = $fundAccount->contact->getType();

            if ((Contact\Type::isInInternal($contactType) === true) and
                ($this->isTaxPaymentContactRequest($fundAccount->contact) === false))
            {
                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_INTERNAL_FUND_ACCOUNT_UPDATE_NOT_PERMITTED,
                    null,
                    [
                        'fund_account_id' => $fundAccount->getId(),
                        'input' => $input
                    ]);
            }
        }

        $fundAccount->edit($input);

        $this->repo->saveOrFail($fundAccount);

        return $fundAccount;
    }

    /**
     * This function will check that this is trying to create the TaxPayment internal contact
     * Also checks if the request source is valid
     *
     * @param Entity $contact
     * @return bool
     */
    protected function isTaxPaymentContactRequest(ContactEntity $contact): bool
    {
        if (($contact->getType() === Contact\Type::TAX_PAYMENT_INTERNAL_CONTACT) and
            ($this->app['basicauth']->isVendorPaymentApp() === true))
        {
            return true;
        }

        return false;
    }

    public function delete(Entity $fundAccount)
    {
        // If we ever decide to make this public. Will need to make sure that Internal fund account cannot be deleted.
        $this->trace->info(TraceCode::FUND_ACCOUNT_DELETE_REQUEST, ['id' => $fundAccount->getId()]);

        return $this->repo->deleteOrFail($fundAccount);
    }

    /**
     * @param string $id
     * @param Merchant\Entity $merchant
     * @return Entity
     */
    public function findByPublicIdAndMerchant(string $id, Merchant\Entity $merchant): Entity
    {
        return $this->repo->fund_account->findByPublicIdAndMerchant($id, $merchant);
    }

    /**
     * Unset sensitive card details
     *
     * @param array $input
     * @return array
     */
    public function unsetSensitiveCardDetails(array $input)
    {
        if ((isset($input[Entity::CARD]) === true) and
            (is_array($input[Entity::CARD]) === true))
        {
            if (empty($input[Entity::CARD][Card\Entity::NUMBER]) === false)
            {
                $input[Entity::CARD][Card\Entity::IIN] = substr($input[Entity::CARD][Card\Entity::NUMBER], 0, 6);
            }

            unset($input[Entity::CARD][Card\Entity::CVV]);
            unset($input[Entity::CARD][Card\Entity::NUMBER]);
            unset($input[Entity::CARD][Card\Entity::NAME]);
            unset($input[Entity::CARD][Card\Entity::EXPIRY_YEAR]);
            unset($input[Entity::CARD][Card\Entity::EXPIRY_MONTH]);
        }

        return $input;
    }

    protected function createFTSAccountForFundAccount(array $input,
                                                      Entity $fundAccount,
                                                      Base\PublicEntity $source = null)
    {
        try
        {
            if ($source !== null)
            {
                $account = $fundAccount->account;

                (new CreateAccount($this->app))->callFtsCreateAccount($account, Constants::PAYOUT);
            }
        }
        catch (\Exception $exception)
        {
            $this->trace->traceException(
                $exception,
                Trace::CRITICAL,
                TraceCode::FTS_CREATE_ACCOUNT_FAILED,
                [
                    'data' => $input,
                ]);
        }
    }

    public function getRZPFeesFundAccountDataForChannel($contact, string $channel)
    {
        $fundAccountData = [
            'account_type'  => 'bank_account',
            'contact_id'    => $contact->getPublicId(),
            'bank_account'  => [
                'name'              => $this->config['banking_account.razorpayx_fee_details.name'],
                'ifsc'              => $this->config['banking_account']['razorpayx_fee_details'][$channel]['ifsc'],
                'account_number'    => $this->config['banking_account']['razorpayx_fee_details'][$channel]['account_number'],
            ]
        ];

        return $fundAccountData;
    }

    public function createRZPFeesFundAccount(Merchant\Entity $merchant, $contact, string $channel)
    {
        $this->trace->info(TraceCode::RZP_FEES_FUND_ACCOUNT_CREATE_REQUEST,
                           [
                               'contact_id' => $contact->getId(),
                               'channel'    => $channel
                           ]);

       $fundAccountData = $this->getRZPFeesFundAccountDataForChannel($contact, $channel);

        $this->create($fundAccountData, $merchant, $contact, false, null, true);
    }

    protected function internalContactChecks(Base\PublicEntity $source = null, array $traceRequest = [])
    {
        // we have to validate that this operation is allowed
        if ((Contact\Type::isInInternalNonRZPFees($source->getType()) === true) and
            Contact\Type::validateInternalAppAllowedContactType($source->getType(),$this->app['basicauth']->getInternalApp()) === false)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_INTERNAL_FUND_ACCOUNT_CREATION_NOT_PERMITTED,
                null,
                [
                    'contact_id' => $source->getId(),
                    'input'      => $traceRequest
                ]);
        }
    }

    /**
     * Remove leading and trailing space from bank account beneficiary_name
     *
     * @param PaginationEntity $paginationEntity
     */
    public function trimBeneficiaryName(PaginationEntity $paginationEntity)
    {
        $this->trace->info(
            TraceCode::START_BENEFICIARY_NAME_TRIMMING,
            [
                'created_from'  => $paginationEntity->getCurrentStartTime(),
                'created_till'  => $paginationEntity->getCurrentEndTime()
            ]
        );

        $fundAccounts = $this->repo->fund_account->fetchBankAccountsHavingSpaceInBeneficiaryName(
            $paginationEntity->getFinalMerchantList(),
            $paginationEntity->getCurrentStartTime(),
            $paginationEntity->getCurrentEndTime(),
            $paginationEntity->getLimit()
        );

        $fundAccountIds = $fundAccounts->getIds();

        while (count($fundAccounts) > 0)
        {
            foreach ($fundAccounts as $fundAccount)
            {
                try
                {
                    $bankAccount = $this->repo->bank_account->findOrFail($fundAccount->getAccountId());

                    $beneficiaryName = $bankAccount->getBeneficiaryName();

                    $trimmedBeneficiaryName = trim(str_replace('\n', ' ', $beneficiaryName));

                    $bankAccount->setBeneficiaryName($trimmedBeneficiaryName);

                    $bankAccount->saveOrFail();

                    $this->trace->info(
                        TraceCode::BENEFICIARY_NAME_TRIMMED,
                        [
                            Entity::ACCOUNT_ID  => $bankAccount->getId()
                        ]
                    );
                }
                catch (\Throwable $exception)
                {
                    $this->trace->traceException(
                        $exception,
                        Trace::ERROR,
                        TraceCode::BENEFICIARY_NAME_TRIM_FAILED,
                        [
                            Entity::ACCOUNT_ID  => $bankAccount->getId()
                        ]
                    );
                }
            }

            $newFundAccounts = $this->repo->fund_account->fetchBankAccountsHavingSpaceInBeneficiaryName(
                $paginationEntity->getFinalMerchantList(),
                $paginationEntity->getCurrentStartTime(),
                $paginationEntity->getCurrentEndTime(),
                $paginationEntity->getLimit()
            );


            $newFundAccountIds = $newFundAccounts->getIds();

            $nonCommonIdsFromLastFundAccounts = array_diff($newFundAccountIds, $fundAccountIds);

            if ((count($newFundAccounts) === 0) or
                (count($nonCommonIdsFromLastFundAccounts) > 0))
            {
                $fundAccountIds = $newFundAccountIds;

                $fundAccounts = $newFundAccounts;
            }
            else
            {
                $data = [
                    'created_from'  => $paginationEntity->getCurrentStartTime(),
                    'created_till'  => $paginationEntity->getCurrentEndTime()
                ];

                $this->trace->info(
                    TraceCode::BENEFICIARY_NAME_TRIM_FOR_MERCHANTS_FAILED,
                    $data
                );

                return;
            }
        }

        $this->trace->info(
            TraceCode::BENEFICIARY_NAME_TRIMMED_FOR_MERCHANTS,
            [
                'created_from'  => $paginationEntity->getCurrentStartTime(),
                'created_till'  => $paginationEntity->getCurrentEndTime()
            ]
        );
    }

    /**
     * Remove leading and trailing space from bank account account_number
     *
     * @param PaginationEntity $paginationEntity
     */
    public function trimAccountNumber(PaginationEntity $paginationEntity)
    {
        $this->trace->info(
            TraceCode::START_ACCOUNT_NUMBER_TRIMMING,
            [
                'created_from'  => $paginationEntity->getCurrentStartTime(),
                'created_till'  => $paginationEntity->getCurrentEndTime()
            ]
        );

        $fundAccounts = $this->repo->fund_account->fetchBankAccountsHavingSpaceInAccountNumber(
            $paginationEntity->getFinalMerchantList(),
            $paginationEntity->getCurrentStartTime(),
            $paginationEntity->getCurrentEndTime(),
            $paginationEntity->getLimit()
        );

        $fundAccountIds = $fundAccounts->getIds();

        while (count($fundAccounts) > 0)
        {
            foreach ($fundAccounts as $fundAccount)
            {
                try
                {
                    $bankAccount = $this->repo->bank_account->findOrFail($fundAccount->getAccountId());

                    $accountNumber = $bankAccount->getAccountNumber();

                    $trimmedAccountNumber = trim(str_replace('\n', '', $accountNumber));

                    $bankAccount->setAccountNumber($trimmedAccountNumber);

                    $bankAccount->saveOrFail();

                    $this->trace->info(
                        TraceCode::ACCOUNT_NUMBER_TRIMMED,
                        [
                            Entity::ACCOUNT_ID  => $bankAccount->getId()
                        ]
                    );
                }
                catch (\Throwable $exception)
                {
                    $this->trace->traceException(
                        $exception,
                        Trace::ERROR,
                        TraceCode::ACCOUNT_NUMBER_TRIM_FAILED,
                        [
                            Entity::ACCOUNT_ID  => $bankAccount->getId()
                        ]
                    );
                }
            }

            $newFundAccounts = $this->repo->fund_account->fetchBankAccountsHavingSpaceInAccountNumber(
                $paginationEntity->getFinalMerchantList(),
                $paginationEntity->getCurrentStartTime(),
                $paginationEntity->getCurrentEndTime(),
                $paginationEntity->getLimit()
            );

            $newFundAccountIds = $newFundAccounts->getIds();

            $nonCommonIdsFromLastFundAccounts = array_diff($newFundAccountIds, $fundAccountIds);

            if ((count($newFundAccounts) === 0) or
                (count($nonCommonIdsFromLastFundAccounts) > 0))
            {
                $fundAccountIds = $newFundAccountIds;

                $fundAccounts = $newFundAccounts;
            }
            else
            {
                $data = [
                    'created_from'  => $paginationEntity->getCurrentStartTime(),
                    'created_till'  => $paginationEntity->getCurrentEndTime()
                ];

                $this->trace->info(
                    TraceCode::ACCOUNT_NUMBER_TRIM_FOR_MERCHANTS_FAILED,
                    $data
                );

                return;
            }
        }

        $this->trace->info(
            TraceCode::ACCOUNT_NUMBER_TRIMMED_FOR_MERCHANTS,
            [
                'created_from'  => $paginationEntity->getCurrentStartTime(),
                'created_till'  => $paginationEntity->getCurrentEndTime()
            ]
        );
    }

    /*
     * $accountDetails should have different keys for different account types
     * Bank account => name, account number, name
     */
    protected function generateUniqueHashForConsistentFundAccount(string $accountType,
                                                                  Merchant\Entity $merchant,
                                                                  array $accountDetails,
                                                                  $source)
    {
        $merchantId = $merchant->getId();

        $sourceEntityName = (empty($source) === false) ? $source->getEntityName() : '';

        /*
         * Hash input structure for bank account type fund accounts -
         * {merchant_id}|{source_entity_name}|{account_input_suffix}
         *
         * In case (empty(source) === true) hash input reduces to -
         * {merchant_id}|||bank_account|{account_input_suffix}
         * We use '' as source_entity_name in that case.
         */
        $uniqueHashInput = $merchantId . '|' . $sourceEntityName;

        switch ($accountType)
        {
            case Type::BANK_ACCOUNT:
                $uniqueHashInputBankAccountSuffix = $this->getUniqueConsistentHashInputSuffixForBankAccount($accountDetails);

                $uniqueHashInput = $uniqueHashInput . '|' . $uniqueHashInputBankAccountSuffix;

                break;

            default:
                $uniqueHashInput = null;

                break;
        }

        $uniqueHash = $uniqueHashInput;

        if (empty($uniqueHash) === false)
        {
            $uniqueHash = hash('sha3-256', $uniqueHash);
        }

        return $uniqueHash;
    }

    /*
     * $accountDetails should have different keys for different account types
     * Bank account => name, account number, name
     * Vpa => address
     * Card => we won't be creating hash for it so even empty array works
     */
    protected function generateUniqueHashForFundAccount(string $accountType,
                                                        Merchant\Entity $merchant,
                                                        array $accountDetails,
                                                        $source)
    {
        $merchantId = $merchant->getId();

        $sourceEntityName = (empty($source) === false) ? $source->getEntityName() : '';

        $sourceId = (empty($source) === false) ? $source->getId() : '';

        /*
         * Hash input structure for bank account/vpa type fund accounts -
         * {merchant_id}|{source_entity_name}|{source_id}|{account_input_suffix}
         *
         * In case (empty(source) === true) hash input reduces to -
         * {merchant_id}|||bank_account|{account_input_suffix}
         * We use '' as source_entity_name and source_id in that case.
         */
        $uniqueHashInput = $merchantId . '|' . $sourceEntityName . '|' . $sourceId;

        switch ($accountType)
        {
            case Type::BANK_ACCOUNT:
                $uniqueHashInputBankAccountSuffix = $this->getUniqueHashInputSuffixForBankAccount($accountDetails);

                $uniqueHashInput = $uniqueHashInput . '|' . $uniqueHashInputBankAccountSuffix;

                break;

            case Type::VPA:
                $uniqueHashInputVpaSuffix = $this->getUniqueHashInputSuffixForVpa($accountDetails);

                $uniqueHashInput = $uniqueHashInput . '|' . $uniqueHashInputVpaSuffix;

                break;

            default:
                $uniqueHashInput = null;

                break;
        }

        $uniqueHash = $uniqueHashInput;

        if (empty($uniqueHash) === false)
        {
            $uniqueHash = hash('sha3-256', $uniqueHash);
        }

        return $uniqueHash;
    }

    /*
     * Hash input suffix structure for bank account type fund accounts -
     * bank_account|{account_number}|{ifsc} (first 4 characters of ifsc)
     */
    protected function getUniqueConsistentHashInputSuffixForBankAccount(array $bankAccountDetails) : string
    {
        $accountNumber =
            $this->removeWhitespacesAndSpecialCharacters($bankAccountDetails[BankAccount\Entity::ACCOUNT_NUMBER]);

        $ifsc = substr(strtoupper(
            $this->removeWhitespacesAndSpecialCharacters($bankAccountDetails[BankAccount\Entity::IFSC])),0,4);

        $uniqueHashInput = Type::BANK_ACCOUNT . '|' . $accountNumber . '|' . $ifsc;

        return $uniqueHashInput;
    }

    /*
     * Hash input suffix structure for bank account type fund accounts -
     * bank_account|{account_number}|{ifsc}|{name}
     */
    protected function getUniqueHashInputSuffixForBankAccount(array $bankAccountDetails) : string
    {
        $accountNumber =
            $this->removeWhitespacesAndSpecialCharacters($bankAccountDetails[BankAccount\Entity::ACCOUNT_NUMBER]);

        $ifsc = strtoupper($this->removeWhitespacesAndSpecialCharacters($bankAccountDetails[BankAccount\Entity::IFSC]));

        $customRegexForName = self::REGEX_FOR_REMOVING_WHITE_SPACES_AND_SPECIAL_CHARACTERS_FROM_BANK_ACCOUNT_NAME;

        $name = $this->removeWhitespacesAndSpecialCharacters($bankAccountDetails[BankAccount\Entity::NAME],
                                                             $customRegexForName);

        $uniqueHashInput = Type::BANK_ACCOUNT . '|' . $accountNumber . '|' . $ifsc . '|' . $name;

        return $uniqueHashInput;
    }

    /*
     * Hash input suffix structure for vpa type fund accounts -
     * vpa|{username}|{handle}
     */
    protected function getUniqueHashInputSuffixForVpa(array $vpaAccountDetails) : string
    {
        list($username, $handle) = explode(Vpa\Entity::AROBASE, $vpaAccountDetails[Vpa\Entity::ADDRESS]);

        $customRegexForUsername = self::REGEX_FOR_REMOVING_WHITE_SPACES_AND_SPECIAL_CHARACTERS_FROM_VPA_USERNAME;

        $username = $this->removeWhitespacesAndSpecialCharacters($username, $customRegexForUsername);

        $handle = $this->removeWhitespacesAndSpecialCharacters($handle);

        $uniqueHashInput = Type::VPA . '|' . $username . '|' . $handle;

        // This is being done because our username and handle are treated case insensitively in the mysql database on
        // prod and hence the hash should also be made for a standard case of the string so that it can be matched in
        // the future to find duplicates for any case combination of the strings of username and handle. Ref:
        // https://razorpay.slack.com/archives/C01B8T2HUM7/p1621835137092000?thread_ts=1621604710.069500&cid=C01B8T2HUM7
        $uniqueHashInput = strtolower($uniqueHashInput);

        return $uniqueHashInput;
    }

    protected function removeWhitespacesAndSpecialCharacters(string $input,
                                                             string $customRegex = null) : string
    {
        $regexForRemovingWhitespaceAndSpecialCharacters = self::REGEX_FOR_REMOVING_WHITE_SPACES_AND_SPECIAL_CHARACTERS;

        if (empty($customRegex) === false)
        {
            $regexForRemovingWhitespaceAndSpecialCharacters = $customRegex;
        }

        $input = preg_replace($regexForRemovingWhitespaceAndSpecialCharacters, "", $input); // nosemgrep : php.lang.security.preg-replace-eval.preg-replace-eval

        return $input;
    }

    protected function getAccountDetailsForInput(array $input)
    {
        switch ($input[Entity::ACCOUNT_TYPE])
        {
            case Type::BANK_ACCOUNT :
                $accountDetails = $input[Entity::BANK_ACCOUNT];

                break;

            case Type::VPA :
                $accountDetails = $input[Entity::VPA];

                break;

            case Type::CARD :
                $accountDetails = $input[Entity::CARD];

                break;

            default :
                $accountDetails = [];

                break;
        }

        return $accountDetails;
    }

    protected function updateDuplicateFundAccountWithHash(Entity $fundAccount,
                                                          string $uniqueHash,
                                                          Merchant\Entity $merchant,
                                                          $source) : Entity
    {
        $accountType = $fundAccount->getAccountType();

        $accountDetails = $this->getAccountDetailsForFundAccount($fundAccount);

        if ($merchant->isFeatureEnabled(Feature\Constants::SKIP_CONTACT_DEDUP_FA_BA))
        {
            $uniqueHashForExistingFundAccount = $this->generateUniqueHashForConsistentFundAccount($accountType,
                                                                                                  $merchant,
                                                                                                  $accountDetails,
                                                                                                  $source);
        }
        else
        {
            $uniqueHashForExistingFundAccount = $this->generateUniqueHashForFundAccount($accountType,
                                                                                        $merchant,
                                                                                        $accountDetails,
                                                                                        $source);
        }

        if ($uniqueHash === $uniqueHashForExistingFundAccount)
        {
            $fundAccount->setUniqueHash($uniqueHash);

            $fundAccount->setConnection($this->mode);

            $this->repo->saveOrFail($fundAccount);

            $this->trace->info(
                TraceCode::EXISTING_FUND_ACCOUNT_HASH_UPDATED,
                [
                    Entity::ID          => $fundAccount->getId(),
                    Entity::UNIQUE_HASH => $uniqueHash,
                ]);
        }

        else
        {
            $this->trace->info(
                TraceCode::HASH_MISMATCH_FOR_INPUT_AND_DUPLICATE_FUND_ACCOUNT,
                [
                    Entity::ID                            => $fundAccount->getId(),
                    Entity::UNIQUE_HASH . '_of_input'     => $uniqueHash,
                    Entity::UNIQUE_HASH . '_of_duplicate' => $uniqueHashForExistingFundAccount,
                ]);

            $this->trace->count(Metric::HASH_MISMATCH_FOR_INPUT_AND_DUPLICATE_FUND_ACCOUNT_TOTAL);

            Tracer::startSpanWithAttributes(HyperTrace::HASH_MISMATCH_FOR_INPUT_AND_DUPLICATE_FUND_ACCOUNT_TOTAL,
                                            [
                                                'mode'    => $this->app['rzp.mode'],
                                                'product' => $this->app['basicauth']->getProduct()
                                            ]);
        }

        return $fundAccount;
    }

    protected function getAccountDetailsForFundAccount(Entity $fundAccount)
    {
        switch ($fundAccount->getAccountType())
        {
            case Type::BANK_ACCOUNT :
                /** @var BankAccount\Entity $bankAccount */
                $bankAccount = $fundAccount->account;

                $accountDetails = [
                    BankAccount\Entity::NAME           => $bankAccount->getBeneficiaryName(),
                    BankAccount\Entity::IFSC           => $bankAccount->getIfscCode(),
                    BankAccount\Entity::ACCOUNT_NUMBER => $bankAccount->getAccountNumber(),
                ];

                break;

            case Type::VPA :
                /** @var Vpa\Entity $vpa */
                $vpa = $fundAccount->account;

                $accountDetails = [
                    Vpa\Entity::ADDRESS => $vpa->getAddress(),
                ];

                break;

            default :
                $accountDetails = [];

                break;
        }

        return $accountDetails;
    }

    public function constructWalletAccountFundAccountRequest(array $input)
    {
        if (isset($input[Entity::WALLET]) === false)
        {
            throw new Exception\BadRequestValidationFailureException('Account type doesn\'t match the details provided');
        }

        (new WalletAccountValidator)->setStrictFalse()
                        ->validateInput(WalletAccountValidator::BEFORE_CREATE_FUND_ACCOUNT_WALLET_ACCOUNT, $input[Entity::WALLET]);

        $inputWalletProvider = $input[Entity::WALLET][WalletAccount\Entity::PROVIDER];
        $isMerchantDisabledForWalletProvider = $this->checkMerchantDisabledForWalletProvider($inputWalletProvider);

        if ($isMerchantDisabledForWalletProvider === false)
        {
            $input[Entity::ACCOUNT_TYPE] = Entity::WALLET_ACCOUNT;

            $input[Entity::WALLET_ACCOUNT] = array_pull($input, Entity::WALLET);

            $input[Entity::WALLET_ACCOUNT][WalletAccount\Entity::PHONE] = $this->reformatPhoneNo($input[Entity::WALLET_ACCOUNT][WalletAccount\Entity::PHONE]);

            unset($input[Entity::WALLET]);

            return $input;
        }
        else
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_WALLET_ACCOUNT_FUND_ACCOUNT_CREATION_NOT_PERMITTED, null,
            [
                Entity::MERCHANT_ID => $this->merchant->getId(),
                'input' => $input,
            ]);
        }
    }

    public function reformatPhoneNo(string $phone)
    {
        $phonebook = new PhoneBook($phone, true);

            $phoneNumber = $phonebook->getPhoneNumber();
            $code        = $phoneNumber->getCountryCode();
            $code        = (($code === null) or ($code === 91)) ? self::DEFAULT_COUNTRY_CODE : (string) $code;
            $number      = $phoneNumber->getNationalNumber();

        return $code . strval($number);
    }

    public function checkMerchantDisabledForWalletProvider(string $walletProvider) : bool
    {
        switch ($walletProvider)
        {
            case WalletAccount\Provider::AMAZONPAY_PROVIDER:
                if ((new WalletAccount\Service)->isWalletAccountAmazonPayFeatureDisabled() === true)
                {
                    return true;
                }
                return false;

            default:
                return false;
        }
    }

    public function unsetSensitiveDetailsForTracing($accountInput, $isScroogeRequest = false)
    {
        if (empty($accountInput[Card\Entity::NUMBER]) === false)
        {
            $accountInput[Card\Entity::IIN] = substr($accountInput[Card\Entity::NUMBER], 0, 6);
        }

        unset($accountInput[Card\Entity::CVV]);
        unset($accountInput[Card\Entity::NUMBER]);

        $accountInput['is_expiry_month_set'] = (isset($accountInput[Card\Entity::EXPIRY_MONTH]) === true);
        unset($accountInput[Card\Entity::EXPIRY_MONTH]);

        $accountInput['is_expiry_year_set'] = (isset($accountInput[Card\Entity::EXPIRY_YEAR]) === true);
        unset($accountInput[Card\Entity::EXPIRY_YEAR]);

        $accountInput['is_scrooge_request'] = $isScroogeRequest;

        return $accountInput;
    }

    public function fetchMultiple($merchant, $input = [])
    {
        $fundAccounts = $this->repo->fund_account->fetch($input, $merchant->getId());
        if ($this->isVendorPaymentViaCorporateCardEnabled() == false) {
            return $fundAccounts;
        }
        return $this->getBulkAppSpecificInformation($fundAccounts);
    }

    public function fetchFundAccountForPayoutServiceProcessing(string $merchantId, array $input): array
    {
        try {
            // Check if razorx enabled
            $razorxResponse = $this->app['razorx']->getTreatment($merchantId,
                                                                 Merchant\RazorxTreatment::PS_FUND_ACCOUNT_CONSUME_FROM_PAYLOAD,
                                                                 RZPConstants\Mode::LIVE);

            if ($razorxResponse !== 'on')
            {
                return [false, null];
            }

            if (isset($input[Payout\Entity::FUND_ACCOUNT_ID]) === false)
            {
                return [false, null];
            }

            $fundAccountId = $input[Payout\Entity::FUND_ACCOUNT_ID];

            $entity = (new FundAccount\Repository)->findByPublicIdAndMerchant($fundAccountId, $this->merchant);

            $entity->load('contact');

            $entity->setIsPSPayout(true);
            $entity->contact->setIsPSPayout(true);

            return [true, $entity->toArrayPublic()];

        }
        catch (\Exception $ex)
        {
            $this->trace->error(
                TraceCode::FUND_ACCOUNT_FETCH_FOR_PAYOUT_SERVICE_EXCEPTION,
                [
                    'error' => $ex->getMessage()
                ]);
        }

        return [false, null];
    }

    public function getBulkAppSpecificInformation(Base\PublicCollection $fundAccounts) : Base\PublicCollection
    {
        $fundAccountIds = [];
        /**
         * @var Entity[] $fundAccounts
         */

        foreach ($fundAccounts as $fundAccount)
        {
            if ($fundAccount->getSourceType() == Entity::CONTACT &&
                $fundAccount->getAccountType() == Entity::BANK_ACCOUNT &&
                $fundAccount->contact->getType() == Contact\Type::VENDOR
            )
            {
                $fundAccountIds[] = $fundAccount->getId();
            }
        }

        if (empty($fundAccountIds)) {
            return $fundAccounts;
        }
        $maxRetries = 3;
        for ($index = 0; $index < $maxRetries; $index++) {
            try {
                $startTimeMs = round(microtime(true) * 1000);

                $vendorFundAccounts = $this->vendorPaymentService->getVendorFundAccounts(
                    $this->merchant,
                    ['fund_account_ids' => $fundAccountIds]
                )["vendor_fund_accounts"];

                $endTimeMs = round(microtime(true) * 1000);

                $totalFetchTime = $endTimeMs - $startTimeMs;

                $this->trace->info(TraceCode::VENDOR_SERVICE_FETCH_DURATION, [
                    'duration_ms' => $totalFetchTime,
                    'merchant_id' => $this->merchant->getId()
                ]);
            } catch (\Exception $exception) {
                $this->trace->traceException($exception);
                if ($index == $maxRetries) {
                    return $fundAccounts;
                }
                continue;
            }
            break;
        }

        $fundAccountIdVendorFundAccountMap = [];
        foreach ($vendorFundAccounts as $vendorFundAccount) {
            $fundAccountIdVendorFundAccountMap[$vendorFundAccount['fund_account_id']] = $vendorFundAccount;
        }

        foreach ($fundAccounts as $fundAccount) {
            if (array_key_exists($fundAccount->getId(), $fundAccountIdVendorFundAccountMap))
            {
                $vendorFundAccount = $fundAccountIdVendorFundAccountMap[$fundAccount->getId()];
                // set status here
                if (isset($vendorFundAccount[Entity::GSTIN_VERIFICATION_STATUS]) == true) {
                    $fundAccount->setGstinVerificationStatus($vendorFundAccount[Entity::GSTIN_VERIFICATION_STATUS]);
                }
                if (isset($vendorFundAccount[Entity::FUND_ACCOUNT_VERIFICATION_STATUS]) == true) {
                    $fundAccount->setFundAccountVerificationStatus($vendorFundAccount[Entity::FUND_ACCOUNT_VERIFICATION_STATUS]);
                }
                if (isset($vendorFundAccount[Entity::PAN_VERIFICATION_STATUS]) == true) {
                    $fundAccount->setPanVerificationStatus($vendorFundAccount[Entity::PAN_VERIFICATION_STATUS]);
                }
                if (isset($vendorFundAccount[Entity::NOTES]) == true) {
                    $fundAccount->setNotes($vendorFundAccount[Entity::NOTES]);
                }
            }
        }
        return $fundAccounts;
    }

    public function isVendorPaymentViaCorporateCardEnabled() :bool {
        try {
            $properties = [
                "id" => $this->merchant->getId(),
                "experiment_id" => $this->app['config']->get('app.vendor_payment_via_corp_card_experiment_id'),
            ];
            $response = $this->app['splitzService']->evaluateRequest($properties);
            $variables = $response['response']['variant']['variables'];
            foreach ($variables as $variable) {
                if ($variable['key'] == "result" && $variable['value'] == "on") {
                    return true;
                }
            }
        } catch (\Exception $e) {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::VENDOR_PAYMENT_VIA_CORP_SPLITZ_ERROR
            );
        }
        return false;
    }
}
