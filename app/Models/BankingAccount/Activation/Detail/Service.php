<?php


namespace RZP\Models\BankingAccount\Activation\Detail;

use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\BankingAccount;
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

        return (new BankingAccount\Service())->fetch($id);
    }

    protected function extractCommentInput(array & $input)
    {
        if (isset($input['comment']) === true)
        {
            return array_pull($input, 'comment');
        }

        return null;
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
        $commentInput = $this->extractCommentInput($input);

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

        $updatedActivationDetail = $this->repo->transaction(function() use ($bankingAccount,
            $activationDetail,
            $input,
            $commentInput,
            $admin,
            $entity)
        {
            // Adding Sales POC to admin_audit_map table
            $this->addSalesPOCToBankingAccountIfApplicable($bankingAccount, $input);

            $activationDetail = $this->core->update($activationDetail, $input);

            $this->initiatePanVerification($activationDetail, $input);

            $this->checkAndPushEventForRmAssigned($bankingAccount, $input);

            if ($activationDetail->isAssigneeTeamUpdated() === true)
            {
                // if entity is passed, use that, else use admin.
                $entity = $entity ?? $admin;

                (new State\Core())->captureNewBankingAccountState($activationDetail->bankingAccount, $entity);

                $this->notifier->notify($activationDetail->bankingAccount, Event::ASSIGNEE_CHANGE, Event::ALERT);
            }

            if (empty($commentInput) === false)
            {
                (new Comment\Core())->create($bankingAccount, $admin, $commentInput);
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

    private function checkAndPushEventForRmAssigned(BankingAccount\Entity $bankingAccount, array $activationDetail)
    {
        $bankingAccountService = new BankingAccount\Service();

        if (isset($activationDetail[Entity::RM_NAME]) === true and $bankingAccountService->isNeoStoneExperiment($bankingAccount) === true)
        {
            $rmNameInLowerCaseWithTrimApplied = strtolower(trim($activationDetail[Entity::RM_NAME]));

            // If RM Name is not any of the possible missing strings
            if (in_array($rmNameInLowerCaseWithTrimApplied, BankingAccount\Entity::$rm_name_missing_possibilities) === false)
            {
                $payload = [
                    'ca_rm_name'          => $activationDetail[Entity::RM_NAME],
                    'ca_rm_number'        => $activationDetail[Entity::RM_PHONE_NUMBER]
                ];

                $this->notifier->notify($bankingAccount, Event::RM_ASSIGNED, Event::INFO, $payload);
            }
        }
    }

    private function initiatePanVerification(Entity $activationDetail, array $input)
    {
        $businessType = $activationDetail->getBusinessCategory();

        if ($businessType === Validator::SOLE_PROPRIETORSHIP)
        {
            // Either Business Pan or Merchant Poc Name or both are updated
            if ((isset($input[Entity::MERCHANT_POC_NAME]) === true and is_null($activationDetail->getBusinessPan()) === false) or
                (isset($input[Entity::BUSINESS_PAN]) === true and is_null($activationDetail->getMerchantPocName()) === false))
            {
                $activationDetail->setPanVerificationStatus(BvsValidationConstants::PENDING);

                $panVerifier = new requestDispatcher\PersonalPanForBankingAccount($this->merchant,($this->merchant)->merchantDetail, $activationDetail);

                $panVerifier->triggerBVSRequest();

                $this->repo->saveOrFail($activationDetail);
            }
        }
        else
        {
            if ((isset($input[Entity::BUSINESS_PAN]) === true and is_null($activationDetail->getBusinessName()) === false) or
                (isset($input[Entity::BUSINESS_NAME]) === true and is_null($activationDetail->getBusinessPan()) === false))
            {
                $activationDetail->setPanVerificationStatus(BvsValidationConstants::PENDING);

                $panVerifier = new requestDispatcher\BusinessPanForBankingAccount($this->merchant,($this->merchant)->merchantDetail, $activationDetail);

                $panVerifier->triggerBVSRequest();

                $this->repo->saveOrFail($activationDetail);

            }
        }
    }
}
