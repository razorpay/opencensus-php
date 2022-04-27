<?php


namespace RZP\Models\BankingAccount\Activation\Detail;

use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\BankingAccount;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\BankingAccount\State;
use RZP\Exception\BadRequestException;
use RZP\Exception\IntegrationException;
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

        $activationDetail = $this->repo->transaction(function () use ($bankingAccount, $input, $validatorOP)
        {
            // Adding Sales POC to admin_audit_map table
            if (isset($input[Entity::SALES_POC_ID]) === true)
            {
                $this->addSalesPOCToBankingAccountIfApplicable($bankingAccount, $input);
            }

            if(array_key_exists(Entity::ADDITIONAL_DETAILS, $input) === true)
            {
                $input[Entity::ADDITIONAL_DETAILS] = json_encode($input['additional_details']);
            }

            $activationDetail = $this->core->create($input, $validatorOP);

            $this->addCommentIfApplicable($bankingAccount, $input);

            return $activationDetail;
        });

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
            $admin = $this->repo->admin->findByEmail($email);
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
                                            Base\PublicEntity $entity = null)
    {
        /** @var BankingAccount\Entity $bankingAccount */
        $bankingAccount = $this->repo->banking_account->findByPublicId($bankingAccountId);

        $admin = $this->app['basicauth']->getAdmin() ?? (($this->app->bound('batchAdmin') === true)? $this->app['batchAdmin'] : null);

        // while updating, the comment field of activationDetailInput is not to be updated,
        // because of the way we handle comments (only for create, it is accepted, and is present
        // in the MIS. not accepted while updating)
        // Once external/internal type is introduced,
        // comment field of activationDetail needs to get deprecated altogether.
        // This is available here only to handle updates in the following flows
        // - change in assignee team requires a comment
        // - update via batch service.

        (new BankingAccount\Core())->checkAndSendFreshDeskEmailIfFormIsSubmitted($bankingAccount, $input);

        $commentInput = $this->extractCommentInput($input);

        $callDateAndTime = $this->extractCallDateAndTime($input);

        $activationDetail = $this->repo->banking_account_activation_detail->findByBankingAccountId($bankingAccount->getId());

        if ($activationDetail === null)
        {
            // has not been created yet. Create an entry with NULLs
            $activationDetail = $this->core->create([Entity::BANKING_ACCOUNT_ID => $bankingAccount->getId()], 'create_null');
        }

        if ($isAutomatedUpdate === false)
        {
            (new Validator())->validateCommentOnAssigneeTeamChange($activationDetail, $input, $commentInput);
        }

        if (array_key_exists(Entity::ADDITIONAL_DETAILS, $input) === true)
        {
            // This is to ensure that update request comes with only those keys which has to be updated and
            // not necessarily the entire json value. It will also ensure that previous data is not lost.

            $input = $this->updateAdditionalDetailsPayload($activationDetail, $input);
        }

        $updatedActivationDetail = $this->repo->transaction(function() use ($bankingAccount,
            $activationDetail,
            $input,
            $commentInput,
            $callDateAndTime,
            $admin,
            $entity)
        {
            // Adding Sales POC to admin_audit_map table
            $this->addSalesPOCToBankingAccountIfApplicable($bankingAccount, $input);

            $isPanEdit = $this->checkIfPanEdit($activationDetail, $input);

            $isMerchantPocNameEdit = $this->checkIfMerchantPocNameEdit($activationDetail, $input);

            $isBusinessNameEdit = $this->checkIfBusinessNameEdit($activationDetail, $input);

            $isRmNotAssigned = $this->isRmNotAssigned($activationDetail, $input);

            $activationDetail = $this->core->update($activationDetail, $input);

            $this->initiatePanVerification($activationDetail, $bankingAccount, $isBusinessNameEdit, $isMerchantPocNameEdit, $isPanEdit);

            $this->checkAndPushEventForRmAssigned($bankingAccount, $input, $activationDetail, $isRmNotAssigned);

            if ($activationDetail->isAssigneeTeamUpdated() === true)
            {
                // if entity is passed, use that, else use admin.
                $entity = $entity ?? $admin;

                (new State\Core())->captureNewBankingAccountState($activationDetail->bankingAccount, $entity);

                $this->notifier->notify($activationDetail->bankingAccount, Event::ASSIGNEE_CHANGE, Event::ALERT);
            }

            $comment = null;

            if (empty($commentInput) === false)
            {
                $comment = (new Comment\Core())->create($bankingAccount, $admin, $commentInput);
            }

            if (empty($callDateAndTime) === false)
            {
                $stateLog = $this->repo->banking_account_state->getLatestStateLogByBankingAccountId($bankingAccount->getId());

                (new BankingAccount\Activation\CallLog\Core())->create($bankingAccount, $admin, $stateLog, $callDateAndTime, $comment);
            }

            return $activationDetail;
        });

        return $updatedActivationDetail->toArrayPublic();
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

    protected function addCommentIfApplicable(BankingAccount\Entity $bankingAccount, array $input)
    {
        if ((isset($input[Entity::COMMENT]) === true)
            && (empty($input[Entity::COMMENT]) === false))
        {
            $bankingAccountCommentCore = new Comment\Core;

            $admin = $this->app['basicauth']->getAdmin();

            $bankingAccountCommentCore->create($bankingAccount, $admin, [
                Comment\Entity::COMMENT => $input[Comment\Entity::COMMENT],
                Comment\Entity::SOURCE_TEAM_TYPE => 'internal',
                Comment\Entity::SOURCE_TEAM => 'sales',
                Comment\Entity::TYPE => 'internal', // TODO: check if this needs to be external
                Comment\Entity::ADDED_AT => time()
            ]);
        }
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

                $this->notifier->notify($bankingAccount, Event::RM_ASSIGNED, Event::INFO, $payload);
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
        return $bankingAccountService->isNeoStoneExperiment($bankingAccount) === true && in_array($rmNameInLowerCaseWithTrimApplied, BankingAccount\Entity::$rm_name_missing_possibilities) === false;
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

}
