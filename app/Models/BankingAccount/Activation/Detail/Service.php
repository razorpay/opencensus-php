<?php


namespace RZP\Models\BankingAccount\Activation\Detail;

use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Models\Base;
use RZP\Models\BankingAccount;
use RZP\Models\BankingAccount\State;
use RZP\Models\BankingAccount\Activation\Comment;
use RZP\Models\BankingAccount\Activation\Notification\Event;
use RZP\Models\BankingAccount\Activation\Notification\Notifier;


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

    public function createForBankingAccount(string $bankingAccountId, array $input)
    {
        /** @var BankingAccount\Entity $bankingAccount */
        $bankingAccount = $this->repo->banking_account->findByPublicId($bankingAccountId);

        $input[Entity::BANKING_ACCOUNT_ID] = $bankingAccount->getId();

        (new Validator)->setStrictFalse()->validateInput(Validator::SALES_POC_ID, $input);

        $activationDetail = $this->repo->transaction(function () use ($bankingAccount, $input)
        {
            // Adding Sales POC to admin_audit_map table
            $this->addSalesPOCToBankingAccountIfApplicable($bankingAccount, $input);

            $activationDetail = $this->core->create($input);

            $this->addCommentIfApplicable($bankingAccount, $input);

            return $activationDetail;
        });

        return $activationDetail->toArrayPublic();
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
            $activationDetail = $this->core->create([Entity::BANKING_ACCOUNT_ID => $bankingAccount->getId()], true);
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
}
