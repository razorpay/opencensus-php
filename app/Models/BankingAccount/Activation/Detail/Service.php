<?php


namespace RZP\Models\BankingAccount\Activation\Detail;

use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\Admin\Org;
use RZP\Models\BankingAccount;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\BankingAccount\State;
use RZP\Exception\BadRequestException;
use RZP\Models\BankingAccount\Activation\Comment;
use RZP\Models\BankingAccount\Activation\Notification\Event;
use RZP\Models\BankingAccount\Activation\Notification\Notifier;
use RZP\Models\Merchant\AutoKyc\Bvs\requestDispatcher;
use RZP\Models\Merchant\BvsValidation\Constants as BvsValidationConstants;

class Service extends Base\Service
{
    /* @var Core $core */
    protected $core;

    /** @var $notifier Notifier */
    protected $notifier;

    public function __construct(Notifier $notifier)
    {
        parent::__construct();

        $this->core = new Core;

        $this->notifier = $notifier;
    }

    public function createForBankingAccount(string $bankingAccountId, array $input, string $validatorOP = 'create_normal')
    {
        /** @var BankingAccount\Entity $bankingAccount */
        $bankingAccount = $this->repo->banking_account->findByPublicId($bankingAccountId);

        $input[Entity::BANKING_ACCOUNT_ID] = $bankingAccount->getId();

        if ($validatorOP === 'create_normal')
        {
            (new Validator)->setStrictFalse()->validateInput(Validator::SALES_POC_ID, $input);
        }

        /** @var Entity $activationDetail */
        $activationDetail = $this->repo->transaction(function () use ($bankingAccount, $input, $validatorOP)
        {
            // Adding Sales POC to admin_audit_map table
            if (isset($input[Entity::SALES_POC_ID]) === true)
            {
                $this->addSalesPOCToBankingAccountIfApplicable($bankingAccount, $input);
            }

            $input = $this->computePoeAndPoaStatus($bankingAccount, null, $input);

            $input = $this->updateInputForSkipMidOfficeCallAndAppointmentSource($bankingAccount, null, $input);

            if(array_key_exists(Entity::ADDITIONAL_DETAILS, $input) === true)
            {
                $input[Entity::ADDITIONAL_DETAILS] = json_encode($input[Entity::ADDITIONAL_DETAILS]);
            }

            $activationDetail = $this->core->create($input, $validatorOP);

            $this->addCommentIfApplicable($bankingAccount, $input);

            return $activationDetail;
        });

        $this->upsertSalesforceLeadDetails($bankingAccount, $activationDetail);

        return $activationDetail->toArrayPublic();
    }

    /**
     * @param string $id
     * @param array  $input
     *
     * @return array
     */
    public function verifyOtpForContact(string $id, array $input): array
    {
        $bankingAccount = $this->repo->banking_account->findByPublicId($id);

        $activationDetail = $this->repo->banking_account_activation_detail->findByBankingAccountId($bankingAccount->getId());

        $this->core->verifyOtpForContact($input, $this->auth->getMerchant(), $this->auth->getUser(), $activationDetail);

        $userService = new \RZP\Models\User\Service();

        $userService->verifyContactForRblIfOwner($input);

        return (new BankingAccount\Service())->fetch($id);
    }

    protected function extractCallDateAndTime(array & $input)
    {
        if (isset($input['call_log']) === true)
        {
            return array_pull($input, 'call_log');
        }

        return null;
    }

    protected function extractCommentInput(array & $input)
    {
        if (isset($input['comment']) === true)
        {
            return array_pull($input, 'comment');
        }

        return null;
    }

    public function getSlotBookingDetailsForBankingAccount(string $bankingAccountId): array
    {
        $bankingAccount = $this->repo->banking_account->findByPublicId($bankingAccountId);

        $activationDetail = $this->repo->banking_account_activation_detail->findByBankingAccountId($bankingAccount->getId());

        $response[Entity::BOOKING_DATE_AND_TIME] = $activationDetail->getBookingDateAndTime();

        $additionalDetails = json_decode($activationDetail->getAdditionalDetails(), true);

        if($additionalDetails === null)
        {
            return [];
        }

        $bookingId = null;

        if (array_key_exists('booking_id', $additionalDetails) === true)
        {
            $bookingId = $additionalDetails['booking_id'];
        }

        $response['booking_id'] = $bookingId;

        $response['assigned_staff_name'] = $activationDetail->getAssigneeName();

        return $response;
    }

    /**
     * @throws BadRequestException
     */
    public function addSlotBookingDetailsForBankingAccount(string $bankingAccountId, array $input): array
    {
        $bankingAccount = $this->repo->banking_account->findByPublicId($bankingAccountId);

        $activationDetail = $this->repo->banking_account_activation_detail->findByBankingAccountId($bankingAccount->getId());

        (new Validator())->validateInput('add_slot_booking_detail', $input);

        $email = array_pull($input, Entity::ADMIN_EMAIL);

        if(array_key_exists(Entity::ADDITIONAL_DETAILS, $input) === true)
        {
            $input = $this->updateAdditionalDetailsPayload($activationDetail, $input);
        }

        try
        {
            $admin = $this->repo->admin->findByOrgIdAndEmail(Org\Entity::RAZORPAY_ORG_ID, $email);
        }
        catch (\Throwable $e)
        {
            return [
                'error'   => $e->getMessage(),
            ];
        }

        $updatedActivationDetail = $this->repo->transaction(function() use ($bankingAccount,
            $activationDetail, $input, $admin)
        {
            (new BankingAccount\Core())->addReviewerToBankingAccount($bankingAccount, $admin->getPublicId());

            return $this->core->update($activationDetail, $input);
        });

        return $updatedActivationDetail->toArrayPublic();
    }


    public function updateForBankingAccount(string $bankingAccountId,
                                            array $input,
                                            bool $isAutomatedUpdate = false,
                                            Base\PublicEntity $entity = null, bool $captureState = true)
    {
        $bankingAccountService = new BankingAccount\Service();

        /** @var BankingAccount\Entity $bankingAccount */
        [$existsInApi, $bankingAccount] = $bankingAccountService->checkAndGetBankingAccountId($bankingAccountId);

        if ($existsInApi === false)
        {
            return $bankingAccountService->updateApplicationOnBas($bankingAccountId, [
                'activation_detail' => $input
            ]);
        }

        // banking_account_activation_details should not be updated if banking_account is in terminated status
        (new BankingAccount\Validator())->validateAccountNotTerminated($bankingAccount, ErrorCode::BAD_REQUEST_BANKING_ACCOUNT_ACTIVATION_DETAILS_UPDATE_NOT_ALLOWED);

        $admin = $this->app['basicauth']->getAdmin() ?? (($this->app->bound('batchAdmin') === true)? $this->app['batchAdmin'] : null);

        $bankingAccountCore = new BankingAccount\Core();

        $admin = $admin !== null ? $admin : $bankingAccountCore->getAdminFromHeadersForMobApp();
        // while updating, the comment field of activationDetailInput is not to be updated,
        // because of the way we handle comments (only for create, it is accepted, and is present
        // in the MIS. not accepted while updating)
        // Once external/internal type is introduced,
        // comment field of activationDetail needs to get deprecated altogether.
        // This is available here only to handle updates in the following flows
        // - change in assignee team requires a comment
        // - update via batch service.

        if ($this->app['basicauth']->isMobApp() === false)
        {
            $commentInput = $this->extractCommentInput($input);
        } else
        {
            $commentInput = $input['comment'] ?? null;
        }

        $callDateAndTime = $this->extractCallDateAndTime($input);

        $activationDetail = $this->repo->banking_account_activation_detail->findByBankingAccountId($bankingAccount->getId());

        if ($activationDetail === null)
        {
            // has not been created yet. Create an entry with NULLs
            $activationDetail = $this->core->create([Entity::BANKING_ACCOUNT_ID => $bankingAccount->getId()], 'create_null');
        }

        $addressPrefilledViaGstin = $this->core->checkAddressUpdate($bankingAccount->bankingAccountActivationDetails, $input);
        // TODO: Remove dupe code
        // To eliminate dirty reads and reduce nested Db txns we are saving value in to entity
        if (is_null($addressPrefilledViaGstin) === false)
        {
            /** @var Entity $activationDetail */
            $activationDetail = $bankingAccount->bankingAccountActivationDetails;
            $activationDetailInput = $this->updateAdditionalDetailsPayload($activationDetail,[
                Entity::ADDITIONAL_DETAILS => [
                'gstin_prefilled_address' => $addressPrefilledViaGstin
            ]]);
            $activationDetail->setAdditionalDetails($activationDetailInput[Entity::ADDITIONAL_DETAILS]);
            array_set($input,'additional_details.gstin_prefilled_address',$addressPrefilledViaGstin);
        }

        $skipDwt = $this->core->computeSkipDwt($bankingAccount->bankingAccountActivationDetails, $input,$bankingAccount->merchant);
        if (is_null($skipDwt) === false)
        {
            /** @var Entity $activationDetail */
            $activationDetail = $bankingAccount->bankingAccountActivationDetails;
            $activationDetailInput = $this->updateAdditionalDetailsPayload($activationDetail,[
                Entity::ADDITIONAL_DETAILS => [
                'skip_dwt' => $skipDwt
            ]]);
            $activationDetail->setAdditionalDetails($activationDetailInput[Entity::ADDITIONAL_DETAILS]);
            array_set($input,'additional_details.skip_dwt',$skipDwt);
        }

        $bankingAccountCore->checkAndSendFreshDeskEmailIfFormIsSubmitted($bankingAccount, $input);

        $bankingAccountCore->moveSubstatusIfSkipDwtExpEligible($bankingAccount, $input);

        $this->calculateCustomerBookingAppointmentDate($activationDetail, $input);

        $this->validateAndUpdateAssigneeTeam($activationDetail, $bankingAccount, $input, $isAutomatedUpdate);

        if ($isAutomatedUpdate === false)
        {
            (new Validator())->validateCommentOnAssigneeTeamChange($activationDetail, $input, $commentInput);
        }

        $input = $this->computePoeAndPoaStatus($bankingAccount, $activationDetail, $input);

        $input = $this->updateInputForSkipMidOfficeCallAndAppointmentSource($bankingAccount, $activationDetail, $input);

        if (array_key_exists(Entity::ADDITIONAL_DETAILS, $input) === true)
        {
            // This is to ensure that update request comes with only those keys which has to be updated and
            // not necessarily the entire json value. It will also ensure that previous data is not lost.

            $input = $this->updateAdditionalDetailsPayload($activationDetail, $input);
        }

        if (array_key_exists(Entity::RBL_ACTIVATION_DETAILS, $input) === true)
        {
            // This is to ensure that update request comes with only those keys which has to be updated and
            // not necessarily the entire json value. It will also ensure that previous data is not lost.

            $input = $this->updateRblActivationDetailsPayload($activationDetail, $input);
        }

        $updatedActivationDetail = $this->repo->transaction(function() use ($bankingAccount,
            $activationDetail,
            $input,
            $commentInput,
            $callDateAndTime,
            $admin,
            $entity,
            $captureState)
        {
            // Adding Sales POC to admin_audit_map table
            $this->addSalesPOCToBankingAccountIfApplicable($bankingAccount, $input);

            // Adding Ops MX POC to admin_audit_map table
            $this->addOpxMxPOCToBankingAccountIfApplicable($bankingAccount, $input);

            $isPanEdit = $this->checkIfPanEdit($activationDetail, $input);

            $isMerchantPocNameEdit = $this->checkIfMerchantPocNameEdit($activationDetail, $input);

            $isBusinessNameEdit = $this->checkIfBusinessNameEdit($activationDetail, $input);

            $isRmNotAssigned = $this->isRmNotAssigned($activationDetail, $input);

            $activationDetail = $this->core->update($activationDetail, $input);

            $this->initiatePanVerification($activationDetail, $bankingAccount, $isBusinessNameEdit, $isMerchantPocNameEdit, $isPanEdit);

            $this->checkAndPushEventForRmAssigned($bankingAccount, $input, $activationDetail, $isRmNotAssigned);

            $this->upsertSalesforceLeadDetails($bankingAccount, $activationDetail);

            $this->checkAndSendDocket($bankingAccount, $entity, $activationDetail);

            $this->checkAndMoveToSTB($bankingAccount, $entity, $activationDetail, $input);

            if ($activationDetail->isAssigneeTeamUpdated() === true  && $captureState === true)
            {
                // if entity is passed, use that, else use admin.
                $entity = $entity ?? $admin;

                (new State\Core())->captureNewBankingAccountState($activationDetail->bankingAccount, $entity);

                $this->notifier->notify($activationDetail->bankingAccount->toArray(), Event::ASSIGNEE_CHANGE, Event::ALERT);
            }

            $comment = null;

            if (empty($commentInput) === false)
            {
                $entity = $entity ?? $admin;

                // Hack because MOB uses this route to create the comment once, even though it is an update
                if ($this->app['basicauth']->isMobApp() === true)
                {
                    $commentPayload = [
                        Comment\Entity::COMMENT => $commentInput,
                        Comment\Entity::SOURCE_TEAM_TYPE => 'internal',
                        Comment\Entity::SOURCE_TEAM => 'sales',
                        Comment\Entity::TYPE => 'internal', // TODO: check if this needs to be external
                        Comment\Entity::ADDED_AT => time()
                    ];
                    $commentInput = $commentPayload;
                }
                $comment = (new Comment\Core())->create($bankingAccount, $entity, $commentInput);
            }

            if (empty($callDateAndTime) === false)
            {
                $stateLog = $this->repo->banking_account_state->getLatestStateLogByBankingAccountId($bankingAccount->getId());

                (new BankingAccount\Activation\CallLog\Core())->create($bankingAccount, $admin, $stateLog, $callDateAndTime, $comment);
            }

            return $activationDetail;
        });

        $bankingAccountCore->moveSubstatusToInitiateDocketIfDwtCompletedTimestampFilled($bankingAccount, $updatedActivationDetail);

        return $updatedActivationDetail->toArrayPublic();
    }

    /**
     * Handle assignee for API Registration i.e bank_ops
     */
    public function validateAndUpdateAssigneeTeam(Entity $activationDetail, BankingAccount\Entity $bankingAccount, array & $input, &$isAutomatedUpdate)
    {

        $ldapIDMailDate = empty($input[Entity::LDAP_ID_MAIL_DATE]) ?
            $activationDetail->getLDAPIDMailDate() :
            $input[Entity::LDAP_ID_MAIL_DATE];

        // Parallel assignees required until LDAP ID Mail date is not filled
        // This should work only in Account Opening & API Onboarding Stages
        if (
            empty($ldapIDMailDate) &&
            BankingAccount\Status::isStatusUnderParallelAssignee($bankingAccount->getStatus())
        )
        {
            $isAutomatedUpdate = true;
            $input[Entity::ASSIGNEE_TEAM] = Entity::BANK_OPS;
        }

        // If entering LDAP_ID_MAIL_DATE for the first time, change assignee automatically to bank
        if (empty($input[Entity::LDAP_ID_MAIL_DATE]) === false && empty($activationDetail->getLDAPIDMailDate()) === true)
        {
            $isAutomatedUpdate = true;
            $input[Entity::ASSIGNEE_TEAM] = Entity::BANK;
        }
    }

    protected function addSalesPOCToBankingAccountIfApplicable(BankingAccount\Entity $bankingAccount, array &$input)
    {
        if (empty($input[Entity::SALES_POC_ID]) === false)
        {
            $salesPocId = $input[Entity::SALES_POC_ID];

            $bankingAccountCore = new BankingAccount\Core;

            $bankingAccountCore->addSalesPOCToBankingAccount($bankingAccount, $salesPocId);

            unset($input[Entity::SALES_POC_ID]);
        }
        else if(isset($input[Entity::SALES_POC_EMAIL]) === true && isset($input[Entity::SALES_TEAM]) === true)
        {
            try
            {
                $spoc = (new \RZP\Models\Admin\Admin\Repository)->findByEmail($input['sales_poc_email']);
            }
            catch(\Exception $e)
            {
                throw new BadRequestException(ErrorCode::BAD_REQUEST_ERROR, null, [], 'no records found for the sales poc email that is mentioned in the batch upload sheet');
            }

            $salesPocId = $spoc->getPublicId();

            $bankingAccountCore = new BankingAccount\Core;

            $bankingAccountCore->addSalesPOCToBankingAccount($bankingAccount, $salesPocId);

            unset($input[Entity::SALES_POC_EMAIL]);
        }
    }

    protected function addOpxMxPOCToBankingAccountIfApplicable(BankingAccount\Entity $bankingAccount, array &$input)
    {
        if (isset($input[BankingAccount\Entity::OPS_MX_POC_ID]) === true)
        {
            $opsMxPOCId = $input[BankingAccount\Entity::OPS_MX_POC_ID];

            $bankingAccountCore = new BankingAccount\Core;

            $bankingAccountCore->addOpsMxPOCToBankingAccount($bankingAccount, $opsMxPOCId);

            unset($input[BankingAccount\Entity::OPS_MX_POC_ID]);
        }
    }

    protected function addCommentIfApplicable(BankingAccount\Entity $bankingAccount, array $input)
    {
        if ((isset($input[Entity::COMMENT]) === true)
            && (empty($input[Entity::COMMENT]) === false))
        {
            $bankingAccountCommentCore = new Comment\Core;
            $bankingAccountCore = new BankingAccount\Core();

            $admin = $this->app['basicauth']->getAdmin();

            $admin = $admin !== null ? $admin : $bankingAccountCore->getAdminFromHeadersForMobApp();

            $bankingAccountCommentCore->create($bankingAccount, $admin, [
                Comment\Entity::COMMENT => $input[Comment\Entity::COMMENT],
                Comment\Entity::SOURCE_TEAM_TYPE => 'internal',
                Comment\Entity::SOURCE_TEAM => 'sales',
                Comment\Entity::TYPE => 'internal', // TODO: check if this needs to be external
                Comment\Entity::ADDED_AT => time()
            ]);
        }
    }

    private function checkAndSendDocket($bankingAccount, $entity, Entity $activationDetail)
    {
        // This is to prevent recursive updates

        $additionalDetails = json_decode($activationDetail->getAdditionalDetails() ?? '{}', true);

        if (array_key_exists(Entity::SENT_DOCKET_AUTOMATICALLY, $additionalDetails))
        {
            return;
        }

        $bankingAccountCore = new BankingAccount\Core();

        $bankingAccountCore->sendDocketIfApplicable($bankingAccount, $entity ?? $bankingAccount->merchant);
    }

    private function checkAndMoveToSTB($bankingAccount, $entity, Entity $activationDetail, array $input)
    {

        if (array_key_exists(Entity::ADDITIONAL_DETAILS, $input) === true)
        {
            $additionalDetailsInput = $input[Entity::ADDITIONAL_DETAILS];

            $additionalDetailsInput = json_decode($additionalDetailsInput, true);

            // If any of these dates is not present, no need to continue
            if (!(array_key_exists(Entity::DOCKET_ESTIMATED_DELIVERY_DATE, $additionalDetailsInput) ||
                array_key_exists(Entity::DOCKET_DELIVERED_DATE, $additionalDetailsInput)))
            {
                return;
            }
        }
        else
        {
            return;
        }

        $additionalDetails = json_decode($activationDetail->getAdditionalDetails() ?? '{}', true);

        // If both are still empty, no need to process
        if (empty($additionalDetails[Entity::DOCKET_ESTIMATED_DELIVERY_DATE]) && empty($additionalDetails[Entity::DOCKET_DELIVERED_DATE]))
        {
            return;
        }

        $bankingAccountService = new BankingAccount\Service();

        $bankingAccountService->moveToSTBIfApplicable($bankingAccount, $entity ?? $bankingAccount->merchant);
    }

    private function checkAndPushEventForRmAssigned(BankingAccount\Entity $bankingAccount, array $activationDetail, Entity $activationDetailDbEntity, bool $isRmNotAssigned)
    {
        $bankingAccountService = new BankingAccount\Service();

        if (isset($activationDetail[Entity::RM_NAME]) === true)
        {
            $rmNameInLowerCaseWithTrimApplied = strtolower(trim($activationDetail[Entity::RM_NAME]));

            // If Neo-stone experiment and RM Name is not any of the possible missing strings
            if($this->isValidCaseForNotifyingCustomerThroughEmailOnRmAssign($bankingAccountService, $bankingAccount, $rmNameInLowerCaseWithTrimApplied))
            {
                $payload = [
                    'ca_rm_name'          => $activationDetail[Entity::RM_NAME],
                    'ca_rm_number'        => $activationDetail[Entity::RM_PHONE_NUMBER]
                ];

                $this->notifier->notify($bankingAccount->toArray(), Event::RM_ASSIGNED, Event::INFO, $payload);
            }

            if($this->isValidCaseForNotifyingCustomerThroughSmsOnRmAssign($activationDetailDbEntity, $activationDetail, $rmNameInLowerCaseWithTrimApplied, $isRmNotAssigned))
            {
                $payload = [
                    'receiver' => $activationDetailDbEntity[Entity::MERCHANT_POC_PHONE_NUMBER],
                    'source'   => "api",
                    'template' => 'sms.account.rm_assigned_banking_ca',
                    'sender'   => "RZPAYX",
                    'params'   => [
                        'rm_name'         => $activationDetail[Entity::RM_NAME],
                        'rm_phone_number' => $activationDetail[Entity::RM_PHONE_NUMBER]
                    ],
                ];

                $orgId = $bankingAccount->getMerchantOrgId();

                // appending orgId in stork context to be used on stork to select org specific sms gateway.
                if (empty($orgId) === false)
                {
                    $payload['stork']['context']['org_id'] = $orgId;
                }

                $this->trace->info(
                    TraceCode::BANKING_ACCOUNT_SMS_RM_ASSIGNED_FOR_CA,
                    [
                        'merchant_id'     => $bankingAccount->getMerchantId(),
                        'rm_name'         => $activationDetail[Entity::RM_NAME],
                        'rm_phone_number' => $activationDetail[Entity::RM_PHONE_NUMBER]
                    ]);

                try
                {
                    $this->app->raven->sendSms($payload);
                }
                catch (\Exception $ex)
                {
                    $this->trace->traceException(
                        $ex,
                        Trace::ERROR,
                        TraceCode::BANKING_ACCOUNT_SMS_RM_ASSIGNED_FOR_CA_FAILED,
                        [
                            'merchant_id' => $bankingAccount->getMerchantId(),
                        ]);
                }
            }

        }
    }
    private function initiatePanVerification(Entity $activationDetail, BankingAccount\Entity $bankingAccount, bool $isBusinessNameEdit, bool $isMerchantPocNameEdit, bool $isPanEdit)
    {
        $businessType = $activationDetail->getBusinessCategory();

        $merchant = $bankingAccount->merchant;

        $merchantDetail = $merchant->merchantDetail;

        if ($businessType === Validator::SOLE_PROPRIETORSHIP)
        {
            // Either Business Pan or Merchant Poc Name or both are updated
            if (($isPanEdit and is_null($activationDetail->getMerchantPocName()) === false) or ($isMerchantPocNameEdit and is_null($activationDetail->getBusinessPan()) === false))
            {
                $activationDetail->setPanVerificationStatus(BvsValidationConstants::PENDING);

                $panVerifier = new requestDispatcher\PersonalPanForBankingAccount($merchant, $merchantDetail, $activationDetail);

                $panVerifier->triggerBVSRequest();

                $this->repo->saveOrFail($activationDetail);
            }
        }
        else
        {
            if (($isPanEdit and is_null($activationDetail->getBusinessName()) === false) or ($isBusinessNameEdit and is_null($activationDetail->getBusinessPan()) === false))
            {
                $activationDetail->setPanVerificationStatus(BvsValidationConstants::PENDING);

                $panVerifier = new requestDispatcher\BusinessPanForBankingAccount($merchant, $merchantDetail, $activationDetail);

                $panVerifier->triggerBVSRequest();

                $this->repo->saveOrFail($activationDetail);

            }
        }
    }

    private function checkIfPanEdit(Entity $activationDetail, array $input): bool
    {
        if (isset($input[Entity::BUSINESS_PAN]) === false)
        {
            return false;
        }

        return ($input[Entity::BUSINESS_PAN] !== $activationDetail->getBusinessPan());
    }

    private function checkIfMerchantPocNameEdit(Entity $activationDetail, array $input): bool
    {
        if (isset($input[Entity::MERCHANT_POC_NAME]) === false)
        {
            return false;
        }

        return ($input[Entity::MERCHANT_POC_NAME] !== $activationDetail->getMerchantPocName());
    }

    private function checkIfBusinessNameEdit(Entity $activationDetail, array $input): bool
    {
        if (isset($input[Entity::BUSINESS_NAME]) === false)
        {
            return false;
        }

        return ($input[Entity::BUSINESS_NAME] !== $activationDetail->getBusinessName());
    }

    /**
     * @param Entity $activationDetailEntity
     * @param array $activationDetail
     * @param string $rmNameInLowerCaseWithTrimApplied
     * @param bool $isRmNotAssigned
     * @return bool
     * return true - If merchant poc phone number is not empty and RM Phone number is not empty and RM Name is not any of the possible missing strings.
     */
    private function isValidCaseForNotifyingCustomerThroughSmsOnRmAssign(Entity $activationDetailEntity, array $activationDetail, string $rmNameInLowerCaseWithTrimApplied, bool $isRmNotAssigned)
    {
        return empty($activationDetailEntity[Entity::MERCHANT_POC_PHONE_NUMBER]) === false && empty($activationDetail[Entity::RM_PHONE_NUMBER]) === false
            && in_array($rmNameInLowerCaseWithTrimApplied, BankingAccount\Entity::$rm_name_missing_possibilities) === false && $isRmNotAssigned === true;
    }

    /**
     * @param BankingAccount\Service $bankingAccountService
     * @param BankingAccount\Entity $bankingAccount
     * @param string $rmNameInLowerCaseWithTrimApplied
     * @return bool
     * return true - If its neostone experiment enabled for this merchant and RM Name is not any of the possible missing strings.
     */
    private function isValidCaseForNotifyingCustomerThroughEmailOnRmAssign(BankingAccount\Service $bankingAccountService, BankingAccount\Entity $bankingAccount, string $rmNameInLowerCaseWithTrimApplied): bool
    {
        return $bankingAccountService->isNeoStoneExperiment($bankingAccount->toArray()) === true && in_array($rmNameInLowerCaseWithTrimApplied, BankingAccount\Entity::$rm_name_missing_possibilities) === false;
    }

    private function isRmNotAssigned(Entity $activationDetail, array $input)
    {
        return empty($input[Entity::RM_NAME]) === false && empty($activationDetail[Entity::RM_NAME]) === true;
    }

    /**
     * @param Entity $activationDetail
     * @param array  $input
     *
     * @return array
     * @throws BadRequestException
     */
    public function updateAdditionalDetailsPayload(Entity $activationDetail, array $input): array
    {
        $previousAdditionalDetails = json_decode($activationDetail->getAdditionalDetails(), true);

        $currentAdditionalDetails = $input[Entity::ADDITIONAL_DETAILS];

        $dateFields = [
            Entity::API_ONBOARDED_DATE,
            Entity::API_ONBOARDING_LOGIN_DATE,
            Entity::BANK_POC_ASSIGNED_DATE,
            Entity::DOCKET_DELIVERED_DATE,
            Entity::DOCKET_REQUESTED_DATE,
            Entity::DOCKET_ESTIMATED_DELIVERY_DATE,
        ];

        // Convert date strings to epoch
        foreach ($dateFields as $dateField)
        {
            if (array_key_exists($dateField, $currentAdditionalDetails))
            {
                if (strtotime($currentAdditionalDetails[$dateField]))
                {
                    $currentAdditionalDetails[$dateField] =
                        strtoepoch($currentAdditionalDetails[$dateField], 'd-M-Y', true);
                }
            }
        }

        if (array_key_exists(Entity::DWT_SCHEDULED_TIMESTAMP, $currentAdditionalDetails) === true &&
            array_key_exists(Entity::OPS_FOLLOW_UP_DATE, $currentAdditionalDetails) === false)
        {
            $currentAdditionalDetails[Entity::OPS_FOLLOW_UP_DATE] = $currentAdditionalDetails[Entity::DWT_SCHEDULED_TIMESTAMP];
        }

        if ($previousAdditionalDetails)
        {
            if (!is_array($previousAdditionalDetails))
            {
                $previousAdditionalDetails = json_decode($previousAdditionalDetails, true);
            }

            $input[Entity::ADDITIONAL_DETAILS] = json_encode(array_merge($previousAdditionalDetails, $currentAdditionalDetails), true);
        }
        else
        {
            $input[Entity::ADDITIONAL_DETAILS] = json_encode($currentAdditionalDetails);
        }

        return $input;
    }

    /**
     * @param Entity $activationDetail
     * @param array  $input
     *
     * @return array
     * @throws BadRequestException
     */
    public function updateRblActivationDetailsPayload(Entity $activationDetails, array $input): array
    {
        $previousRblActivationDetails = json_decode($activationDetails->getRblActivationDetails(), true);

        $currentRblActivationDetails = $input[Entity::RBL_ACTIVATION_DETAILS];

        if ($previousRblActivationDetails)
        {
            if (!is_array($previousRblActivationDetails))
            {
                $previousRblActivationDetails = json_decode($previousRblActivationDetails, true);
            }

            $input[Entity::RBL_ACTIVATION_DETAILS] = json_encode(array_merge($previousRblActivationDetails, $currentRblActivationDetails), true);
        }
        else
        {
            $input[Entity::RBL_ACTIVATION_DETAILS] = json_encode($currentRblActivationDetails);
        }

        return $input;
    }

    public function calculateCustomerBookingAppointmentDate($activationDetails, &$activationDetailInput)
    {

        $customerAppointmentDate = Entity::CUSTOMER_APPOINTMENT_DATE;

        $customerBookingAppointmentDate = Entity::CUSTOMER_APPOINTMENT_BOOKING_DATE;

        if (array_key_exists($customerAppointmentDate, $activationDetailInput))
        {
            if ($activationDetails[$customerAppointmentDate] !== $activationDetailInput[$customerAppointmentDate])
            {
                $activationDetailInput[Entity::RBL_ACTIVATION_DETAILS][$customerBookingAppointmentDate] = Carbon::now()->timestamp;
            }
        }

    }

    public function updateInputForSkipMidOfficeCallAndAppointmentSource(BankingAccount\Entity $bankingAccount, Entity $activationDetail = null, array $input): array
    {
        $additionalDetails = Entity::ADDITIONAL_DETAILS;

        // If sales pitch is completed, we can't update the decision
        if ($activationDetail && empty($activationDetail->getAdditionalDetails()) === false)
        {
            $details = json_decode($activationDetail->getAdditionalDetails(), true);

            if (empty($details[Entity::SALES_PITCH_COMPLETED]) === false)
            {
                return $input;
            }
        }

        // No change
        if ($bankingAccount->isFasterDocCollectionEnabled() === false)
        {
            return $input;
        }

        if (empty($input[$additionalDetails]) == false && is_string($input[$additionalDetails]))
        {
            $input[$additionalDetails] = json_decode($input[$additionalDetails], true);
        }

        $skipMidOfficeCall = $this->shouldSkipMidOfficeCall($bankingAccount, $activationDetail, $input);

        if($skipMidOfficeCall)
        {
            $input[$additionalDetails][Entity::SKIP_MID_OFFICE_CALL] = 1;
            $input[$additionalDetails][Entity::APPOINTMENT_SOURCE] = Entity::SALES;
        }
        else
        {
            $input[$additionalDetails][Entity::SKIP_MID_OFFICE_CALL] = 0;
            $input[$additionalDetails][Entity::APPOINTMENT_SOURCE] = Entity::MID_OFFICE;
        }

        return $input;
    }

    public function shouldSkipMidOfficeCall(BankingAccount\Entity $bankingAccount, Entity $activationDetail = null, array $input)
    {
        $bankingAccountService = new BankingAccount\Service();

        if($bankingAccountService->isFosLead($bankingAccount) === true)
        {
            return true;
        }

        $additionalDetails = $activationDetail ? json_decode($activationDetail->getAdditionalDetails(), true) : [];

        if (empty($input[Entity::ADDITIONAL_DETAILS]) === false)
        {
            $additionalDetails = array_merge($additionalDetails, $input[Entity::ADDITIONAL_DETAILS]);
        }

        $poe = Entity::extractFieldFromJSONField($additionalDetails, Entity::PROOF_OF_ENTITY);

        $poeVerified = $poe ? $poe['status'] === 'verified' : false;

        $poa = Entity::extractFieldFromJSONField($additionalDetails, Entity::PROOF_OF_ADDRESS);

        $poaVerified = $poa ? $poa['status'] === 'verified' : false;

        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_FASTER_DOC_COLLECTION_CHECK,
            [
                'banking_account_id'    => $bankingAccount->getId(),
                'additional_details'    => $activationDetail ? $activationDetail->getAdditionalDetails() : null,
                'input'                 => $input,
                'check'                 => $additionalDetails,
            ]);

        // If PoE or PoA is not verified
        if (!($poeVerified && $poaVerified))
        {
            return false;
        }

        $expected = [
            Entity::RBL_NEW_ONBOARDING_FLOW_DECLARATIONS => [
                Entity::SIGNATORIES_AVAILABLE_AT_PREFERRED_ADDRESS => 1,
                Entity::AVAILABLE_AT_PREFERRED_ADDRESS_TO_COLLECT_DOCS => 1
            ]
        ];

        $match = check_array_selective_equals_recursive($expected, $additionalDetails);

        return $match;
    }

    protected function upsertSalesforceLeadDetails(BankingAccount\Entity $bankingAccount, Entity $activationDetails)
    {
        $salesforceUpsertInput = [
            'merchant_id'                       => $bankingAccount->getMerchantId(),
            'product_name'                      => 'Current_Account',
            'PoE_verified'                      => $activationDetails->isPoEVerified() ? 'true' : 'false',
            'PoA_verified'                      => $activationDetails->isPoAVerified() ? 'true' : 'false',
            'Appointment_during_Sales_pitch'    => $activationDetails->getSkipMidOfficeCall() === 1 ? 'true' : 'false',
            'PoA_document'                      => $activationDetails->getPoASource() ?? 'N/A',
        ];

        $this->app->salesforce->sendCaLeadDetails($salesforceUpsertInput);
    }

    private function computePoeAndPoaStatus(BankingAccount\Entity $bankingAccount, Entity|null $activationDetail, array $input): array
    {
        $inputAdditionalDetails = $input[Entity::ADDITIONAL_DETAILS] ?? [];
        if (is_string($inputAdditionalDetails))
        {
            $inputAdditionalDetails = json_decode($inputAdditionalDetails, true);
        }

        $existingAdditionalDetails = optional($activationDetail)->getAdditionalDetails() ?? '{}';
        $existingAdditionalDetails = json_decode($existingAdditionalDetails, true);

        $updatedAdditionalDetails = array_merge($existingAdditionalDetails,$inputAdditionalDetails);

        $businessCategory = $input['business_category'] ?? '';
        $verifiedConstitutions = $updatedAdditionalDetails['verified_constitutions'] ?? [];
        if (!empty($businessCategory))
        {
            foreach ($verifiedConstitutions as $constitution)
            {
                if ($businessCategory === $this->fromBasConstitutionToApiConstitution($constitution['constitution']))
                {
                    $inputAdditionalDetails['proof_of_entity'] = [
                      'status' => 'verified',
                      'source' =>  $constitution['source']
                    ];
                }
            }
        }

        $merchantDocumentsAddress = $input['merchant_documents_address'] ?? '';
        $verifiedAddresses = $updatedAdditionalDetails['verified_addresses'] ?? [];
        if (!empty($merchantDocumentsAddress))
        {
            foreach ($verifiedAddresses as $address)
            {
                if ($merchantDocumentsAddress === $address['address'])
                {
                    $inputAdditionalDetails['proof_of_address'] = [
                        'status' => 'verified',
                        'source' =>  $address['source']
                    ];
                }
            }
        }

        $input[Entity::ADDITIONAL_DETAILS] = $inputAdditionalDetails;

        return $input;
    }

    private function fromBasConstitutionToApiConstitution(string $constitution): string
    {
        return match ($constitution) {
            'PUBLIC_LIMITED', 'PRIVATE_LIMITED' => Validator::PRIVATE_PUBLIC_LIMITED_COMPANY,
            'LLP' => Validator::LIMITED_LIABILITY_PARTNERSHIP,
            'ONE_PERSON_COMPANY' => Validator::ONE_PERSON_COMPANY,
            'PROPRIETORSHIP' => Validator::SOLE_PROPRIETORSHIP,
            'PARTNERSHIP' => Validator::PARTNERSHIP,
            'SOCIETY' => Validator::SOCIETY,
            'TRUST' => Validator::TRUST,
            default => '',
        };
    }

}
