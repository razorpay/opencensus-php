<?php

namespace RZP\Models\BankAccount;

use Mail;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Feature;
use RZP\Constants\Mode;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Traits\TrimSpace;
use RZP\Models\BankAccount;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\Service;
use RZP\Models\Admin\Permission;
use RZP\Models\Merchant\Document;
use RZP\Models\Settlement\Bucket;
use RZP\Exception\BadRequestException;
use RZP\Models\Workflow\Action\MakerType;
use RZP\Models\Comment\Core as CommentCore;
use RZP\Models\Payment\Processor\Netbanking;
use RZP\Models\Merchant\Document\FileHandler;
use RZP\Models\Settlement\OndemandFundAccount;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Services\Segment\EventCode as SegmentEvent;
use RZP\Models\Workflow\Service as WorkflowService;
use RZP\Models\Merchant\AutoKyc\Bvs\Core as BvsCore;
use RZP\Models\Settlement\SettlementServiceMigration;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;
use RZP\Models\Merchant\Document\Core as DocumentCore;
use RZP\Services\Pagination\Entity as PaginationEntity;
use RZP\Models\Merchant\Detail\DeDupe\Core as DedupeCore;
use RZP\Models\Contact\Validator as fundAccountValidator;
use RZP\Models\Workflow\Action\Core as WorkFlowActionCore;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant as BvsConstant;
use RZP\Notifications\Dashboard\Events as MerchantDashboardEvent;
use RZP\Models\Merchant\Detail\PennyTesting as DetailsPennyTesting;
use RZP\Models\Workflow\Observer\Constants as WorkflowObserverConstants;
use RZP\Notifications\Dashboard\Handler as DashboardNotificationHandler;
use RZP\Models\Merchant\BvsValidation\Constants as BvsValidationConstants;
use RZP\Models\Merchant\AutoKyc\Bvs\DocumentStatusUpdater\BankAccount as BankAccountStatusUpdater;
use RZP\Models\Merchant\AutoKyc\Bvs\requestDispatcher\BankAccount as BankAccountRequestDispatcher;

class Core extends Base\Core
{
    use TrimSpace;

    const BANK_ACCOUNT_UPDATE_WORKFLOW_COMMENT = 'verification_status : %s, account_status : %s, account_holder_names : %s';

    const BANK_ACCOUNT_UPDATE_WORKFLOW_COMMENT_FOR_DEDUPE = 'dedupe_status: true, matchedMIDs = {%s}';

    public function createOrChangeBankAccount($input,
                                              $merchant,
                                              $isWorkflowRequired = true,
                                              $sendAccountChangeRequestMail = true)
    {
        if(($this->app['basicauth']->isAdminAuth() === false) and
            (isset($input[Entity::TYPE]) === true) and
            ($input[Entity::TYPE] === Type::ORG_SETTLEMENT))
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_REQUIRED_PERMISSION_NOT_FOUND);
        }

        if((isset($input[Entity::TYPE]) === true) and
            ($input[Entity::TYPE] === Type::ORG_SETTLEMENT) and
            ($merchant->org->isFeatureEnabled(Feature\Constants::ORG_POOL_ACCOUNT_SETTLEMENT) === false))
        {
          throw new BadRequestException(ErrorCode::BAD_REQUEST_REQUIRED_PERMISSION_NOT_FOUND);
        }

        $type = $input[Entity::TYPE] ?? null;

        $oldBankAccount = $this->repo->bank_account->getBankAccount($merchant,$type);

        if ($oldBankAccount === null) {
            if ($type !== Type::ORG_SETTLEMENT) {
                $ba = $this->createBankAccount($input, $merchant, $this->mode);

                $this->updateOndemandFundAccountIfRequired($ba);

                if ($this->settlementServiceRamp($ba->getMerchantId()) === true) {
                    if ($this->app['basicauth']->isAdminAuth() === true) {
                        app('settlements_dashboard')->createBankAccount($ba, $this->mode);
                    } else {
                        app('settlements_api')->migrateBankAccount($ba, $this->mode);
                    }
                }

                return $ba;
            }
        }

        $newBankAccount = $this->buildBankAccount($input, $merchant, $this->mode);

        $newBankAccount->associateMerchant($merchant,$type);

        if (($oldBankAccount !== null) and
            ($newBankAccount->equals($oldBankAccount)))
        {
            $this->trace->info(
                TraceCode::MISC_TRACE_CODE,
                [
                    'new' => $newBankAccount->toArrayTrace(),
                    'old' => $oldBankAccount->toArrayTrace(),
                ]);

            return $oldBankAccount;
        }

        if((isset($input[Entity::TYPE])=== true) and
            ($input[Entity::TYPE] === Type::ORG_SETTLEMENT))
        {
            return $this->addOrUpdateOrgSettlementAccount($oldBankAccount,$newBankAccount);
        }

        $ba = $this->changeBankAccount($input,
            $merchant,
            $oldBankAccount,
            $isWorkflowRequired,
            $sendAccountChangeRequestMail);

        if ($this->settlementServiceRamp($ba->getMerchantId()) === true)
        {
            if( $this->app['basicauth']->isAdminAuth() === true )
            {
                app('settlements_dashboard')->createBankAccount($ba, $this->mode);
            }
            else
            {
                app('settlements_api')->migrateBankAccount($ba, $this->mode);
            }
        }

        if($ba->getType() === BankAccount\Type::MERCHANT)
        {
            (new Merchant\Core)->updateVirtualAccountForFundAddition($merchant);
        }

        $this->sendBankAccountChangeNotification($ba, $merchant);

        return $ba;
    }

    public function addOrUpdateOrgSettlementAccount($orgSettlementBA,$newBankAccount) {

        if ($orgSettlementBA !== null)
        {
            $this->repo->delete($orgSettlementBA);
        }

        $this->repo->saveOrFail($newBankAccount);

        return $newBankAccount;
    }


    public function addOrUpdateBankAccountForCustomer($input, $customer)
    {
        $currentAccounts = $this->repo->bank_account->getBankAccountsForCustomer($customer);

        $newBankAccount = $this->buildBankAccount($input, $customer->merchant, $this->mode);

        $newBankAccount->associateCustomer($customer);

        foreach ($currentAccounts as $existingAccount)
        {
            if ($newBankAccount->equals($existingAccount))
            {
                $this->trace->info(
                    TraceCode::MISC_TRACE_CODE,
                    [
                        'new' => $newBankAccount->toArray(),
                        'old' => $existingAccount->toArray(),
                    ]);

                return $existingAccount;
            }
        }

        $this->repo->saveOrFail($newBankAccount);

        (new Beneficiary)->enqueueForBeneficiaryRegistration($newBankAccount);

        return $newBankAccount;
    }

    /**
     * `source` entity can be customer|contact
     *
     * @param array             $input
     * @param MerchantEntity    $merchant
     * @param Base\PublicEntity $source
     *
     * @return Entity
     */
    public function createBankAccountForFundAccount(array $input,
                                                    Merchant\Entity $merchant,
                                                    Base\PublicEntity $source = null): Entity
    {
        (new Validator)->validateIfscCode($input, $this->mode);

        $trimmedInput = $this->trimSpaces($input);

        $ba = $this->createBankAccountForSource(
                        $trimmedInput,
                        $merchant,
                        $source,
                        'add_fund_account_bank_account');

        return $ba;
    }

    public function editBankAccount(Entity $bankAccount, array $input)
    {
        $this->trace->info(
            TraceCode::BANK_ACCOUNT_EDIT,
            [
                'edit_input' => $input,
                'bank_account' => $bankAccount->toArray()
            ]);

        $bankAccount = $bankAccount->edit($input);

        $this->repo->saveOrFail($bankAccount);

        $this->updateOndemandFundAccountIfRequired($bankAccount);

        return $bankAccount;
    }

    public function updateOndemandFundAccountIfRequired($bankAccount)
    {
        $merchantId = $bankAccount[Entity::MERCHANT_ID];

        if ($bankAccount->getType() === Type::MERCHANT)
        {
            /** @var Merchant\Entity $merchant */
            $merchant = $this->repo->merchant->find($merchantId);

            if ($merchant->isFeatureEnabled(Feature\Constants::ES_ON_DEMAND) === true)
            {
                (new OndemandFundAccount\Service)->dispatchSettlementOndemandFundAccountUpdateJob($merchantId);
            }
        }
    }

    /**
     * This takes the oldBank Account as it's last parameter
     *
     * @param  array              $input Input Array with new bank account details
     * @param  MerchantEntity     $merchant
     * @param  BankAccount\Entity $oldBankAccount
     *
     * @return mixed
     * @throws Exception\ServerErrorException
     */
    protected function changeBankAccount(array $input,
                                         MerchantEntity $merchant,
                                         BankAccount\Entity $oldBankAccount,
                                         $isWorkflowRequired = true,
                                         $sendAccountChangeRequestMail = true)
    {
        $detail = $this->formatBankAccountForMerchantDetail($input);


        $newBankAccount = $this->buildBankAccount($input, $merchant, $this->mode);

        $newBankAccount->associateMerchant($merchant);

        $newBankAccount->generateBeneficiaryCode();

        $oldBankAccountArray = $oldBankAccount->toArrayPublic();
        $newBankAccountArray = $newBankAccount->toArrayPublic();

        // add code for check of Bank File here and change the entities
        // Upload the file and get the file id
        $this->fillAddressProofUrl($input, $merchant, $newBankAccountArray, $oldBankAccountArray);

        return $this->repo->transaction(
            function() use ($merchant,
                $oldBankAccountArray,
                $newBankAccountArray,
                $oldBankAccount,
                $input,
                $detail,
                $isWorkflowRequired,
                $sendAccountChangeRequestMail)
            {
                //
                // Creating a bank account entity to send email. This will be rolled back if workflow if enabled.
                // Hence creating only single entity.
                //
                $ba = $this->createBankAccount($input, $merchant, $this->mode);

                //
                // Send Email if it is not a workflow execution flow, since we want to send the request received
                // email only once and not again after the workflow has been approved.
                //
                if (($this->app['api.route']->isWorkflowExecuteOrApproveCall() === false) and
                    ($sendAccountChangeRequestMail === true))
                {
                    $this->sendBankAccountChangeNotification($ba, $merchant, MerchantDashboardEvent::BANK_ACCOUNT_CHANGE_REQUEST);
                }

                if ($isWorkflowRequired === true)
                {
                    $this->app['workflow']
                         ->setPermission(Permission\Name::EDIT_MERCHANT_BANK_DETAIL)
                         ->setEntityAndId($oldBankAccount->getEntity(), $oldBankAccount->getId())
                         ->handle($oldBankAccountArray, $newBankAccountArray);
                }


                $this->repo->delete($oldBankAccount);

                $merchantDetails = $merchant->merchantDetail;

                if ($merchantDetails !== null)
                {
                    if (isset($input[Detail\Entity::ADDRESS_PROOF_URL]) === true)
                    {
                        $this->handleAddressProofUrl($input, $merchantDetails, $merchant);
                    }

                    // Doing a fill only for ADDRESS_PROOF_URL because Details\Validator
                    // expects it to be a file object where as we're passing a File ID.
                    $merchantDetails->fill($input);

                    $merchantDetails->edit($detail);

                    $this->repo->merchant_detail->saveOrFail($merchantDetails);
                }

                $this->updateOndemandFundAccountIfRequired($ba);

                return $ba;
            });
    }

    /**
     *
     * @param array          $input
     * @param DetailEntity   $merchantDetails
     * @param MerchantEntity $merchant
     *
     * @throws Exception\BadRequestException
     * @throws Exception\BadRequestValidationFailureException
     * @throws Exception\LogicException
     */
    function handleAddressProofUrl(array &$input, Merchant\Detail\Entity $merchantDetails, Merchant\Entity $merchant): void
    {
        $params = [
            Detail\Entity::ADDRESS_PROOF_URL => $input[Detail\Entity::ADDRESS_PROOF_URL]
        ];

        $detailService = new Detail\Service();

        $detailService->deleteExistingDocuments($params, $merchantDetails);

        $documentParams[Detail\Entity::ADDRESS_PROOF_URL] = [
            Document\Constants::FILE_ID => $input[Detail\Entity::ADDRESS_PROOF_URL],
            Document\Constants::SOURCE  => (new FileHandler\Factory())->getDocumentSource($input[Detail\Entity::ADDRESS_PROOF_URL], $merchant->getId())
        ];

        (new DocumentCore)->storeInMerchantDocument($merchant, $merchant, $documentParams);
    }


    protected function uploadAddressProof($merchant, $input)
    {
        (new Validator)->validateAddressProofUploadOverProxyAuth();

        $merchantDetailService = new Detail\Service();

        $merchantDetails = $merchantDetailService->core()->getMerchantDetails($merchant);

        $fileInputs = [
            Detail\Entity::ADDRESS_PROOF_URL => $input[Detail\Entity::ADDRESS_PROOF_URL]
        ];

        $fileAttributes = $merchantDetailService->storeActivationFile($merchantDetails, $fileInputs);

        if ((is_array($fileAttributes) === false) or
            (isset($fileAttributes[Detail\Entity::ADDRESS_PROOF_URL]) === false))
        {
            throw new Exception\ServerErrorException(
                'Address Proof URL upload failed.',
                ErrorCode::SERVER_ERROR);
        }

        $input[Detail\Entity::ADDRESS_PROOF_URL] = $fileAttributes[Detail\Entity::ADDRESS_PROOF_URL][Document\Constants::FILE_ID];

        return $input;
    }

    public function updateBeneficiaryCodes()
    {
        $bas = $this->repo->bank_account->fetchBankAccountsWithoutBeneCode();

        foreach ($bas as $ba)
        {
            $ba->generateBeneficiaryCode();

            $this->repo->saveOrFail($ba);
        }

        $result['count'] = $bas->count();

        return $result;
    }

    public function createTestBankAccount($merchant)
    {
        $input = array(
            'ifsc_code'             => Entity::SPECIAL_IFSC_CODE,
            'beneficiary_name'      => 'Test ' . $merchant->getId(),
            'account_number'        => random_integer(11),
            'beneficiary_address1'  => 'Bengaluru Palace',
            'beneficiary_address2'  => 'Palace Rd, Vasanth Nagar',
            'beneficiary_city'      => 'Banglore',
            'beneficiary_state'     => 'KA',
            'beneficiary_country'   => 'IN',
            'beneficiary_pin'       => '560052',
//          TODO: @kartik.sayani - should this be changed?
            'beneficiary_mobile'    => '18002700323',
        );

        if (is_null($merchant->getEmail()) === false)
        {
            $input['beneficiary_email'] = $merchant->getEmail();
        }

        $ba = $this->createBankAccount($input, $merchant, Mode::TEST);

        // this won't be trigger until the ramp up is 100% because when the merchant is signs up then
        // only this function will call and we can not have the merchant id configured in front
        if ($this->settlementServiceRamp($merchant->getId()) === true)
        {
            if( $this->app['basicauth']->isAdminAuth() === true )
            {
                app('settlements_dashboard')->createBankAccount($ba, Mode::TEST);
            }
            else
            {
                app('settlements_api')->migrateBankAccount($ba, Mode::TEST);
            }
        }

        return $ba;
    }

    // pushToQueue is added to configure queue push and razorx call later since this function is used within
    // a DB transaction.
    public function createBankAccountForSource(
        array $input,
        Merchant\Entity $merchant,
        Base\PublicEntity $source = null,
        string $addRule,
        bool $pushToQueue=true): Entity
    {
        $ba = new BankAccount\Entity;

        $ba = $ba->build($input, $addRule);

        $ba->merchant()->associate($merchant);

        $ba->source()->associate($source);

        $this->repo->saveOrFail($ba);

        if ($pushToQueue === true) {
            (new Beneficiary)->enqueueForBeneficiaryRegistration($ba);
        }

        return $ba;
    }

    /**
     * All bank account creation happens via this function
     *
     * @param  array  $input
     * @param         $merchant
     * @param  string $mode
     *
     * @return BankAccount\Entity
     */
    protected function createBankAccount($input, $merchant, $mode)
    {
        $ba = $this->buildBankAccount($input, $merchant, $mode);

        $ba->associateMerchant($merchant);

        $ba->generateBeneficiaryCode();

        $this->repo->saveOrFail($ba);

        (new Beneficiary)->enqueueForBeneficiaryRegistration($ba);

        return $ba;
    }

    protected function buildBankAccount($input, $merchant, $mode)
    {
        $ba = new BankAccount\Entity;

        $ba->setConnection($mode);

        // if live mode and input does not already contain notes, copy test mode notes
        if (($mode === Mode::LIVE) and (empty($input[Entity::NOTES]) === true))
        {
            $testBankAccount = $this->repo->bank_account->getBankAccountOnConnection($merchant, Mode::TEST);

            if (empty($testBankAccount) === false)
            {
                $notes = $testBankAccount->getNotes();

                $input[Entity::NOTES] = $notes->toArray();
            }
        }

        $ba = $ba->build($input);

        $ba->getValidator()->validateIfscCode($input, $mode);

        $ba->merchant()->associate($merchant);

        return $ba;
    }

    protected function sendBankAccountChangeNotification($newBankAccount, $merchant, $event = MerchantDashboardEvent::BANK_ACCOUNT_CHANGE_SUCCESSFUL)
    {
        if ($this->shouldNotifyViaEmail($merchant) === false)
        {
            return;
        }

        $newBankAccount = $newBankAccount->toArray();

        $args = [
            Merchant\Constants::MERCHANT  => $merchant,
            MerchantDashboardEvent::EVENT => $event,
            Merchant\Constants::PARAMS    => array_merge($newBankAccount, [
                Entity::NAME => $merchant->getName()
            ]),
        ];

        (new DashboardNotificationHandler($args))->send();
    }

    public function buildBankAccountArrayFromMerchantDetail(DetailEntity $detail, bool $linkedAccount = false): array
    {
        $details = $detail->toArray();

        $data = [
            Entity::IFSC_CODE             => $details[DetailEntity::BANK_BRANCH_IFSC],
            Entity::BENEFICIARY_NAME      => $details[DetailEntity::BANK_ACCOUNT_NAME],
            Entity::ACCOUNT_NUMBER        => $details[DetailEntity::BANK_ACCOUNT_NUMBER],
            Entity::BENEFICIARY_COUNTRY   => 'IN',
            Entity::BENEFICIARY_MOBILE    => $details[DetailEntity::CONTACT_MOBILE],
        ];

        if(is_null($details[DetailEntity::CONTACT_EMAIL]) === false)
        {
            $data[Entity::BENEFICIARY_EMAIL] = $details[DetailEntity::CONTACT_EMAIL];
        }

        //
        // For Marketplace linked accounts, the bank fields set below are not
        // required in the activation form but needed for API validation
        // Setting default values here to overcome this
        //
        if ($linkedAccount === true)
        {
            $data[Entity::BENEFICIARY_MOBILE]   = 9999999999;
        }

        return $data;
    }

    protected function formatBankAccountForMerchantDetail(array $input): array
    {
        $detail = [
            Detail\Entity::BANK_BRANCH_IFSC          => $input[Entity::IFSC_CODE],
            Detail\Entity::BANK_ACCOUNT_NUMBER       => $input[Entity::ACCOUNT_NUMBER],
            Detail\Entity::BANK_ACCOUNT_NAME         => $input[Entity::BENEFICIARY_NAME],
        ];

        return $detail;
    }

    protected function shouldNotifyViaEmail(MerchantEntity $merchant): bool
    {
        // In dev and testing environments we want to send mail even if Mode is TEST
        if (($this->mode === Mode::TEST) and
            ($this->app->environment('dev', 'testing') === false))
        {
            return false;
        }

        // Do not email linked accounts
        if ($merchant->isLinkedAccount() === true)
        {
            return false;
        }

        return true;
    }

    public function getBankAccountEntity(string $id)
    {
        return $this->repo->bank_account->find($id);
    }

//    public function updateBankAccountWithFtsId(Entity $entity, $ftsFundAccountId)
//    {
//        $entity->setFtsFundAccountId($ftsFundAccountId);
//
//        $this->repo->saveOrFail($entity);
//    }

    public function getBankAccountByFtsFundAccountId($ftsFundAccountId)
    {
        return $this->repo->bank_account->getBankAccountByFtsFundAccountId($ftsFundAccountId);
    }

    public function settlementServiceRamp(string $merchantId)
    {
        return (new Bucket\Core)->shouldProcessViaNewService($merchantId);
    }

    public function MigrateBankAccountsToSettlementService($merchantId, $via , $mode)
    {
        $merchant = $this->repo->merchant->fetchMerchantOnConnection($merchantId, $mode);

        $ba  = $this->repo->bank_account->getBankAccountOnConnection($merchant, $mode);

        if($ba === null)
        {
            $this->trace->info(
                TraceCode::SETTLEMENT_SERVICE_BA_MIGRATION_SKIPPED,
                [
                    'merchant_id' => $merchant->getId(),
                    'mode'        => $mode,
                    'via'         => $via,
                ]);

            return;
        }

        app('settlements_api')->migrateBankAccount($ba, $mode, $via);

        $this->trace->info(
            TraceCode::SETTLEMENT_SERVICE_BA_MIGRATION_SUCCESS,
            [
                'merchant_id' => $merchant->getId(),
                'mode'        => $mode,
                'via'         => $via,
            ]);
    }

    public function bankAccountUpdate(MerchantEntity $merchant, array $input)
    {
        // If auth type is not admin then only validate feature for account update request.
        if($this->app['basicauth']->isAdminAuth() === false)
        {
            $this->validateFeatureForAccountUpdate($merchant);
        }

        if ($merchant->org->isFeatureEnabled(Feature\Constants::ORG_POOL_ACCOUNT_SETTLEMENT) === true)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_ACCOUNT_ACTION_NOT_SUPPORTED);

        }

        $this->trace->info(TraceCode::BANK_ACCOUNT_UPDATE_FUNDS_ON_HOLD, [
            Merchant\Entity::HOLD_FUNDS => $merchant->getHoldFunds()
        ]);

        // if funds are on hold and request is coming from merchant, this will throw an exception -> doesnt allow bank account update if funds are on hold
        if ($this->app['basicauth']->isAdminAuth() === false)
        {
            $this->validateMerchantFundsAreNotOnHold($merchant);
        }

        $this->validateBankAccountUpdatePennyTestingNotInProgress($merchant);

        // if not found, this will throw an exception -> doesnt allow bank account update if it doesnt exist now
        (new Service)->getOwnBankAccount();

        // here we are rolling back the transaction as there is no need to save the new bank account
        // if penny testing suceeds, we will create it at that time
        // we just need a bank account entity (in memory) to trigger penny testing/for sending mail
        $newBankAccount = $this->repo->beginTransactionAndRollback(function() use ($input, $merchant) {
            return $this->createBankAccount($input, $merchant, $this->mode);
        });

        $this->validateNotLaxmiVilasBank($newBankAccount);

        $validationId = $this->triggerBankAccountBvsValidation($input, $merchant);

        $this->repo->merchant_detail->saveOrFail($merchant->merchantDetail);

        $data = $this->makeBankAccountUpdatePennyTestingData($input, $newBankAccount, $validationId);

        $this->saveBankAccountUpdatePennyTestingData($merchant, $data);

        $this->sendBankAccountChangeNotification($newBankAccount, $merchant, MerchantDashboardEvent::BANK_ACCOUNT_CHANGE_REQUEST);

        $this->trace->info(TraceCode::BANK_ACCOUNT_UPDATE_VIA_PENNY_TESTING_INITIATED, [
            Merchant\BvsValidation\Entity::VALIDATION_ID => $validationId
        ]);

        return $newBankAccount;
    }

    public function handlePennyTestingEventForBankAccountUpdate(array $favInput, MerchantEntity $merchant, string $status, array $pennyTestAndFuzzyMatchResult)
    {
        $data = $this->getBankAccountUpdatePennyTestingData($merchant);

        $cacheKey = $this->getBankAccountUpdatePennyTestingCacheKey($merchant);

        $this->app['cache']->delete($cacheKey);

        switch ($status)
        {
            case Detail\BankDetailsVerificationStatus::VERIFIED:
            {
                $this->handleBankAccountUpdateCallbackSuccess($merchant, $data);

                break;
            }
            default:
            {
                $this->createWorkflowForBankAccountUpdate($merchant, $data);
            }
        }
    }

    protected function triggerBankAccountBvsValidation($input, $merchant)
    {
        $payload = $this->getBankAccountUpdateBvsPayload($input, $merchant);

        $validation = (new BvsCore)->verify($this->merchant->getId(), $payload);

        if ($validation === null)
        {
            throw new Exception\ServerErrorException('', ErrorCode::SERVER_ERROR);
        }

        return $validation->getValidationId();
    }

    /**
     * @param $input
     * @param $merchant
     * @return array
     */
    protected function getBankAccountUpdateBvsPayload($input, $merchant): array
    {
        $merchantAttributesForFuzzyMatch = (new DetailsPennyTesting())->getAllowedMerchantAttributesDetails($merchant->merchantDetail);

        $accountHolderNames = array_values($merchantAttributesForFuzzyMatch);

        return [
            BvsConstant::CUSTOM_CALLBACK_HANDLER  => Constants::BANK_ACCOUNT_UPDATE_CALLBACK_HANDLER_BVS,
            BvsConstant::ARTEFACT_TYPE            => BvsConstant::BANK_ACCOUNT,
            BvsConstant::CONFIG_NAME              => (new BankAccountRequestDispatcher($merchant, $merchant->merchantDetail))->getConfigName(),
            BvsConstant::VALIDATION_UNIT          => BvsValidationConstants::IDENTIFIER,
            BvsConstant::DETAILS                  => [
                BvsConstant::ACCOUNT_NUMBER       => $input[BvsConstant::ACCOUNT_NUMBER],
                BvsConstant::IFSC                 => $input[Entity::IFSC_CODE],
                BvsConstant::BENEFICIARY_NAME     => $input[BvsConstant::BENEFICIARY_NAME],
                BvsConstant::ACCOUNT_HOLDER_NAMES => $accountHolderNames,
            ],
        ];
    }

    public function handleBankAccountUpdateCallback($merchant, $merchantDetails,$validation)
    {
        $this->trace->info(TraceCode::BANK_ACCOUNT_UPDATE_BVS_CALLBACK_RECEIVED, $validation->toArrayPublic());

        $data = $this->getBankAccountUpdatePennyTestingData($merchant);

        $cacheKey = $this->getBankAccountUpdatePennyTestingCacheKey($merchant);

        $status = (new BankAccountStatusUpdater($merchant, $merchantDetails,$validation))->getDocumentValidationStatus($validation);

        try
        {
            $this->pushBvsResultToSegmentForBankAccountUpdate($merchant, $validation, $status);
        }
        catch (\Throwable $exception)
        {
            $this->app['trace']->error(TraceCode::BANK_ACCOUNT_UPDATE_SEGMENT_EVENT_PUSH_FAILED, [
                Constants::ERROR_MESSAGE => $exception->getMessage()
            ]);
        }

        switch ($validation->getValidationStatus())
        {
            case BvsConstant::SUCCESS:
                $this->handleBankAccountUpdateCallbackSuccess($merchant, $data, $status);
                break;
            default:
                $this->handleBankAccountUpdateCallbackFailure($merchant, $data, $status);
        }

        $this->app['cache']->delete($cacheKey);
    }

    protected function handleBankAccountUpdateCallbackSuccess($merchant, $data, $status)
    {
        (new DetailsPennyTesting())->setBankDetailsVerificationStatusAndUpdatedAt($merchant->merchantDetail, $status);

        $this->createOrChangeBankAccount($data[Constants::BANK_ACCOUNT_UPDATE_INPUT], $merchant, false, false);

        $this->stopShowingRejectionReasonForBankAccountUpdateSelfServe($merchant->bankAccount->getId(), $merchant->bankAccount->getEntityName());

        $this->app['trace']->info(TraceCode::BANK_ACCOUNT_UPDATE_VIA_PENNY_TESTING_SUCCESS, ["merchant_id"=>$merchant->getId(),"status"=>$status]);
    }

    protected function stopShowingRejectionReasonForBankAccountUpdateSelfServe($entityId, $entity)
    {
        $action = (new WorkFlowActionCore())->fetchLastUpdatedWorkflowActionInPermissionList(
            $entityId,
            $entity,
            [Permission\Name::EDIT_MERCHANT_BANK_DETAIL]
        );

        if ((empty($action) === false) and
            ($action->isRejected() === true))
        {
            (new WorkflowService())->updateWorkflowObserverData(WorkflowAction\Entity::getSignedId($action->getId()),[
                WorkflowObserverConstants::SHOW_REJECTION_REASON_ON_DASHBOARD => 'false'
            ]);
        }
    }

    protected function handleBankAccountUpdateCallbackFailure($merchant, $data, $status)
    {
        $this->createWorkflowForBankAccountUpdate($merchant, $data);

        $this->addCommentsForBankAccountUpdateWorkFlow($merchant, $status, $data);
    }

    protected function createWorkflowForBankAccountUpdate($merchant, $data)
    {
        $segmentProperties = [
            'workflow_crated' => true,
        ];

        try
        {
            $newBankAccountArray = $data[Constants::NEW_BANK_ACCOUNT_ARRAY];
            $oldBankAccountArray = $data[Constants::OLD_BANK_ACCOUNT_ARRAY];


            // here we are rolling back the transaction as there is no need to save the new bank account
            // if penny testing suceeds, we will create it at that time
            // we just need a bank account entity (in memory) to trigger penny testing/for sending mail
            $newBankAccount = $this->repo->beginTransactionAndRollback(function () use ($data, $merchant) {
                return $this->createBankAccount($data[Constants::BANK_ACCOUNT_UPDATE_INPUT], $merchant, $this->mode);
            });

            $this->sendBankAccountChangeNotification($newBankAccount, $merchant, MerchantDashboardEvent::BANK_ACCOUNT_CHANGE_PENNY_TESTING_FAILURE);

            $oldBankAccount = $this->repo->bank_account->getBankAccount($merchant);

            if (is_null($data[Constants::ADMIN_EMAIL]) === false)
            {
                $maker = $this->repo->admin->findByEmail($data[Constants::ADMIN_EMAIL]);

                $this->app['workflow']
                    ->setWorkflowMaker($maker)
                    ->setWorkflowMakerType(MakerType::ADMIN)
                    ->setMakerFromAuth(false);
            }
            else
            {
                $this->app['workflow']
                    ->setWorkflowMaker($merchant)
                    ->setWorkflowMakerType(MakerType::MERCHANT)
                    ->setMakerFromAuth(false);
            }

            $this->app['workflow']
                ->setPermission(Permission\Name::EDIT_MERCHANT_BANK_DETAIL)
                ->setRouteName(Constants::BANK_ACCOUNT_UPDATE_POST_PENNY_TESTING_ROUTE_NAME)
                ->setRouteParams([])
                ->setInput($data)
                ->setController(Constants::BANK_ACCOUNT_UPDATE_POST_PENNY_TESTING_CONTROLLER)
                ->setMethod('POST')
                ->setEntityAndId($oldBankAccount->getEntity(), $oldBankAccount->getId())
                ->handle($oldBankAccountArray, $newBankAccountArray, true);

            $this->app['trace']->info(TraceCode::BANK_ACCOUNT_UPDATE_VIA_PENNY_TESTING_WORKFLOW_CREATED, []);

        }
        catch (\Throwable $exception)
        {
            $segmentProperties['failure_reason'] = $exception->getMessage();
        }


        try
        {
            $this->app['segment-analytics']->pushIdentifyAndTrackEvent(
                $merchant, $segmentProperties, SegmentEvent::BANK_ACCOUNT_UPDATE_WORKFLOW_CREATED);
        }
        catch (\Throwable $exception)
        {
            $this->app['trace']->error(TraceCode::BANK_ACCOUNT_UPDATE_SEGMENT_EVENT_PUSH_FAILED, [
                Constants::ERROR_MESSAGE => $exception->getMessage()
            ]);
        }



        $this->app['workflow']
            ->setInput(null)
            ->setPermission(null)
            ->setRouteName(null)
            ->setRouteParams(null)
            ->setController(null)
            ->setWorkflowMaker(null)
            ->setWorkflowMakerType(null)
            ->setMakerFromAuth(true);
    }

    /**
     * Adds a comment for bank account update workflow
     * @param $merchant
     * @param $status
     * @param $data
     */
    protected function addCommentsForBankAccountUpdateWorkFlow($merchant, $status, $data)
    {
        $validationID = $data['validation_id'];

        $oldBankAccount = $this->repo->bank_account->getBankAccount($merchant);

        $workFlowAction = (new WorkFlowActionCore())->fetchOpenActionOnEntityOperation($oldBankAccount->getId(),
            $oldBankAccount->getEntity(),
            Permission\Name::EDIT_MERCHANT_BANK_DETAIL
        )->first();

        if (is_null($workFlowAction) === true)
        {
            $this->trace->error(TraceCode::BANK_ACCOUNT_UPDATE_WORKFLOW_ACTION_NOT_FOUND, [
                'merchant_id' => $merchant->getId(),
            ]);
        }
        else
        {
            $this->addBvsValidationCommentForBankAccountUpdate($status, $merchant, $validationID, $workFlowAction);

            $this->addDedupeStatusCommentForBankAccountUpdate($merchant, $workFlowAction, $data[Constants::NEW_BANK_ACCOUNT_ARRAY]);
        }
    }

    protected function addBvsValidationCommentForBankAccountUpdate($status, $merchant, $validationID, $workFlowAction)
    {
        try
        {
            $comment = $this->getCommentForBankAccountUpdateWorkFlow($status, $merchant->getId(), $validationID);
        }
        catch (\Throwable $e)
        {
            $comment = Constants::WORKFLOW_COMMENT_ERROR_ADDING_VALIDATION_RESULT;

            $this->app['trace']->error(TraceCode::BANK_ACCOUNT_UPDATE_VALIDATION_WORKFLOW_COMMENT, [
                Constants::ERROR_MESSAGE => $e->getMessage(),
            ]);
        }

        $commentEntity = (new CommentCore())->create([
            'comment' => $comment,
        ]);

        $commentEntity->entity()->associate($workFlowAction);

        $this->repo->saveOrFail($commentEntity);
    }

    protected function addDedupeStatusCommentForBankAccountUpdate($merchant, $workFlowAction, $newBankAccountArray)
    {
        try
        {
            $comment = $this->getDedupeStatusCommentForBankAccountUpdateWorkflow($newBankAccountArray, $merchant);
        }
        catch (\Throwable $e)
        {
            $comment = Constants::WORKFLOW_COMMENT_ERROR_ADDING_DEDUPE_RESULT;

            $this->app['trace']->error(TraceCode::BANK_ACCOUNT_UPDATE_DEDUPE_WORKFLOW_COMMENT, [
                Constants::ERROR_MESSAGE => $comment,
            ]);
        }

        $commentEntity = (new CommentCore())->create([
            Constants::COMMENT => $comment,
        ]);

        $commentEntity->entity()->associate($workFlowAction);

        $this->repo->saveOrFail($commentEntity);
    }

    /**
     * format the comment using penny test, fuzzy match result and dedupe status of merchant
     * @param $status
     * @param $merchantId
     * @param $validationID
     * @return string
     */
    protected function getCommentForBankAccountUpdateWorkFlow($status, $merchantId, $validationID)
    {
        $verificationDetails = $this->getBankAccountVerificationDetailsFromBvs($merchantId, $validationID);

        $comment = sprintf(self::BANK_ACCOUNT_UPDATE_WORKFLOW_COMMENT,
            $status,
            $verificationDetails[Constants::ACCOUNT_STATUS],
            $verificationDetails[BvsConstant::ACCOUNT_HOLDER_NAMES]
        );

        $this->app['trace']->info(TraceCode::BANK_ACCOUNT_UPDATE_VALIDATION_WORKFLOW_COMMENT, [
            Constants::COMMENT => $comment,
        ]);

        return $comment;
    }


    protected function getBankAccountVerificationDetailsFromBvs($merchantId, $validationID)
    {
        $verificationDetails = (new Detail\Service())->getBvsValidationArtefactDetails($merchantId, BvsConstant::BANK_ACCOUNT, $validationID);

        $accountHolderNames = $this->getAccountHolderNamesFromEnrichmentDetailsForBankAccountUpdate(
            $verificationDetails[BvsConstant::ENRICHMENT_DETAIL_FIELDS]);

        return [
            BvsConstant::ACCOUNT_HOLDER_NAMES => implode(',', $accountHolderNames),
            Constants::ACCOUNT_STATUS         => $verificationDetails[BvsConstant::ENRICHMENT_DETAIL_FIELDS]->online_provider->details->account_status->value,
        ];
    }

    protected function getAccountHolderNamesFromEnrichmentDetailsForBankAccountUpdate($enrichmentDetails)
    {

        $accountHolderNames = $enrichmentDetails->online_provider->details->account_holder_names;

        $names = [];

        foreach ($accountHolderNames as $accountHolderName)
        {
            array_push($names, $accountHolderName->value);
        }

        return $names;
    }

    protected function getDedupeStatusCommentForBankAccountUpdateWorkflow($newBankAccountArray, $merchant)
    {
        [$status, $matchedMIDs] = $this->getDedupeStatusForBankAccountUpdate($newBankAccountArray, $merchant);

        $comment = Constants::DEDUPE_FALSE_COMMENT;

        if ($status === true)
        {
            $comment = sprintf(self::BANK_ACCOUNT_UPDATE_WORKFLOW_COMMENT_FOR_DEDUPE, implode(', ', $matchedMIDs));
        }

        $this->app['trace']->info(TraceCode::BANK_ACCOUNT_UPDATE_DEDUPE_WORKFLOW_COMMENT, [
            Constants::COMMENT => $comment,
        ]);

        return $comment;
    }

    protected function getDedupeStatusForBankAccountUpdate($newBankAccountArray, $merchant)
    {
        // store old bank detail from merchantDetail Entity
        $oldDetail = [
            Detail\Entity::BANK_ACCOUNT_NUMBER       => $merchant->merchantDetail->getBankAccountNumber(),
            Detail\Entity::BANK_ACCOUNT_NAME         => $merchant->merchantDetail->getBankAccountName(),
        ];

        $newDetail = [
            Detail\Entity::BANK_ACCOUNT_NUMBER       => $newBankAccountArray[Entity::ACCOUNT_NUMBER],
            Detail\Entity::BANK_ACCOUNT_NAME         => $newBankAccountArray[Entity::NAME],
        ];

        // dedupe status should be given for new bank account
        $this->merchant->merchantDetail->edit($newDetail);

        [$status, $matchedMIDs] = (new DedupeCore())->matchAndGetMatchedMIDs($merchant);

        // restore old detail in merchantDetail Entity
        $this->merchant->merchantDetail->edit($oldDetail);

        return [$status, $matchedMIDs];
    }

    protected function pushBvsResultToSegmentForBankAccountUpdate($merchant, Merchant\BvsValidation\Entity $validation, $status)
    {
        $ruleExecution = $validation->getRuleExecutionList();

        $segmentProperties = [];

        $segmentProperties[Constants::RESULT]         = $validation->getValidationStatus();

        $segmentProperties[Constants::FAILURE_REASON] = $validation->getErrorCode();

        $configName = (new BankAccountRequestDispatcher($merchant, $merchant->merchantDetail))->getConfigName();

        if (($configName === BvsConstant::BANK_ACCOUNT_WITH_PERSONAL_PAN) or
            ($configName === BvsConstant::BANK_ACCOUNT_WITH_BUSINESS_PAN))
        {
            $namesTobeMatched  = $ruleExecution[0][Constants::RULE_EXECUTION_RESULT][Constants::OPERANDS][Constants::OPERANDS1][Constants::OPERANDS][Constants::OPERANDS1][Constants::OPERANDS] ?? [];

            $matchedPercentage = $ruleExecution[0][Constants::RULE_EXECUTION_RESULT][Constants::OPERANDS][Constants::OPERANDS1][Constants::REMARKS][Constants::MATCH_PERCENTAGE] ?? null;

            $result = array_merge($namesTobeMatched, [
                Constants::MATCH_PERCENTAGE => $matchedPercentage
            ]);

            $segmentProperties[Constants::NAME_MATCH_RESULT] = [
                Constants::RESULT => $result,
            ];
        }
        else
        {
            $namesTobeMatched  = $ruleExecution[0][Constants::RULE_EXECUTION_RESULT][Constants::OPERANDS][Constants::OPERANDS1][Constants::OPERANDS][Constants::OPERANDS1][Constants::OPERANDS] ?? [];

            $matchedPercentage = $ruleExecution[0][Constants::RULE_EXECUTION_RESULT][Constants::OPERANDS][Constants::OPERANDS1][Constants::REMARKS][Constants::MATCH_PERCENTAGE] ?? null;

            $result1 = array_merge($namesTobeMatched, [
                Constants::MATCH_PERCENTAGE  => $matchedPercentage
            ]);

            $namesTobeMatched  = $ruleExecution[0][Constants::RULE_EXECUTION_RESULT][Constants::OPERANDS][Constants::OPERANDS2][Constants::OPERANDS][Constants::OPERANDS1][Constants::OPERANDS] ?? [];

            $matchedPercentage = $ruleExecution[0][Constants::RULE_EXECUTION_RESULT][Constants::OPERANDS][Constants::OPERANDS2][Constants::REMARKS][Constants::MATCH_PERCENTAGE] ?? null;

            $result2 = array_merge($namesTobeMatched, [
                Constants::MATCH_PERCENTAGE  => $matchedPercentage
            ]);

            $segmentProperties[Constants::NAME_MATCH_RESULT] = [
                Constants::RESULT1 => $result1,
                Constants::RESULT2 => $result2,
            ];

        }

        $this->app['segment-analytics']->pushIdentifyAndTrackEvent(
            $merchant, $segmentProperties, SegmentEvent::BANK_ACCOUNT_UPDATE_BVS_FUZZY_MATCH_RESULT);

        $this->app['segment-analytics']->pushIdentifyAndTrackEvent(
            $merchant,
            [
                Constants::PENNY_TEST_RESULT => $status
            ],
            SegmentEvent::BANK_ACCOUNT_UPDATE_PENNY_TEST_RESULT
        );
    }

    public function bankAccountUpdatePostPennyTestingWorkflow(MerchantEntity $merchant, array $input)
    {
        return $this->createOrChangeBankAccount($input[Constants::BANK_ACCOUNT_UPDATE_INPUT], $merchant, false);
    }

    public function isBankAccountUpdatePennyTestingInProgress(MerchantEntity $merchant)
    {
        $data = $this->getBankAccountUpdatePennyTestingData($merchant);

        return ($data !== null);
    }


    protected function saveBankAccountUpdatePennyTestingData(MerchantEntity $merchant, array $data)
    {
        $cacheKey = $this->getBankAccountUpdatePennyTestingCacheKey($merchant);

        $this->app->cache->put($cacheKey, $data, Constants::BANK_ACCOUNT_UPDATE_PENNY_TESTING_TTL);
    }

    protected function validateBankAccountUpdatePennyTestingNotInProgress(MerchantEntity $merchant)
    {
        if ($this->isBankAccountUpdatePennyTestingInProgress($merchant) === true)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_BANK_ACCOUNT_UPDATE_IN_PROGRESS);
        }
    }

    /**
     * If feature flag is enabled on org then don't allow merchants to update their account.
     * @param MerchantEntity $merchant
     * @throws BadRequestException
     */
    protected function validateFeatureForAccountUpdate(MerchantEntity $merchant)
    {
        if($merchant->org->isFeatureEnabled(Feature\Constants::ORG_BLOCK_ACCOUNT_UPDATE)=== true)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_CANNOT_UPDATE_BANK_ACCOUNT, null, null);
        }
    }


    protected function validateMerchantFundsAreNotOnHold(MerchantEntity $merchant)
    {
        if ($merchant->getHoldFunds() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_FUNDS_ON_HOLD,
            null,
            null,
            "Bank account can not be updated due to funds are on hold");
        }
    }

    protected function getBankAccountUpdatePennyTestingData(MerchantEntity $merchant)
    {
        $cacheKey = $this->getBankAccountUpdatePennyTestingCacheKey($merchant);

        $data = $this->app->cache->get($cacheKey);

        return $data;
    }

    protected function getBankAccountUpdatePennyTestingCacheKey(MerchantEntity $merchant)
    {
        return sprintf(Constants::BANK_ACCOUNT_UPDATE_PENNY_TESTING_CACHE_KEY, $merchant->getId());
    }


    /**
     * @param array $input
     * @param MerchantEntity $merchant
     * @param array $newBankAccountArray
     * @param array $oldBankAccountArray
     * @throws Exception\ServerErrorException
     */
    protected function fillAddressProofUrl(array &$input, MerchantEntity $merchant, array &$newBankAccountArray, array &$oldBankAccountArray): void
    {
        if (isset($input[Detail\Entity::ADDRESS_PROOF_URL]) === true)
        {
            // upload the file and then add the file id in the array
            if (is_object($input[Detail\Entity::ADDRESS_PROOF_URL]) === true)
            {
                $input = $this->uploadAddressProof($merchant, $input);
            }

            $newBankAccountArray[Detail\Entity::ADDRESS_PROOF_URL] = $input[Detail\Entity::ADDRESS_PROOF_URL];

            $oldBankAccountArray[Detail\Entity::ADDRESS_PROOF_URL] = (new Detail\Core())
                ->getMerchantDetails($merchant)
                ->getAddressProofFile();

            // to replace the file with file id in request for workflow payload
            $this->app['request']->replace($input);
        }
    }


    protected function makeBankAccountUpdatePennyTestingData(array $input, BankAccount\Entity  $newBankAccount, $validationId)
    {
        $oldBankAccountArray = (new Service())->getOwnBankAccount();

        $newBankAccountArray = $newBankAccount->toArrayPublic();

        $this->fillAddressProofUrl($input, $this->merchant, $newBankAccountArray, $oldBankAccountArray);

        $adminEmail = null;

        if ($this->app['basicauth']->isAdminAuth() === true)
        {
            $adminEmail = $this->app['basicauth']->getAdmin()->getEmail();
        }

        return [
            Constants::BANK_ACCOUNT_UPDATE_INPUT => $input,
            Merchant\Entity::MERCHANT_ID         => $newBankAccount->merchant->getId(),
            Constants::OLD_BANK_ACCOUNT_ARRAY    => $oldBankAccountArray,
            Constants::NEW_BANK_ACCOUNT_ARRAY    => $newBankAccountArray,
            BvsConstant::VALIDATION_ID           => $validationId,
            Constants::ADMIN_EMAIL               => $adminEmail,
        ];
    }

    protected function validateNotLaxmiVilasBank($newBankAccount): void
    {
        if (($newBankAccount->getBankCode() === Netbanking::LAVB_R) or
            ($newBankAccount->getBankCode() === Netbanking::LAVB_C)) {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_BANK_ACCOUNT_UPDATE_LAXMI_VILAS_BANK_PROHIBITED);
        }
    }
}
