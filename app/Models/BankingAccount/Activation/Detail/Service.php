<?php


namespace RZP\Models\BankingAccount\Activation\Detail;

use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\BankingAccount;
use RZP\Models\BankingAccount\Activation\Comment;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;

class Service extends Base\Service
{
    /* @var Core $core */
    protected $core;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;
    }

    public function createForBankingAccount(string $bankingAccountId, array $input)
    {
        /** @var BankingAccount\Entity $bankingAccount */
        $bankingAccount = $this->repo->banking_account->findByPublicId($bankingAccountId);

        $input[Entity::BANKING_ACCOUNT_ID] = $bankingAccount->getId();

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

    public function updateForBankingAccount(string $bankingAccountId, array $input)
    {
        /** @var BankingAccount\Entity $bankingAccount */
        $bankingAccount = $this->repo->banking_account->findByPublicId($bankingAccountId);

        $activationDetail = $this->repo->banking_account_activation_detail->getFromBankingAccountId($bankingAccount->getId());

        $updatedActivationDetail = $this->repo->transaction(function() use ($bankingAccount, $activationDetail, $input)
        {
            // Adding Sales POC to admin_audit_map table
            $this->addSalesPOCToBankingAccountIfApplicable($bankingAccount, $input);

            return $this->core->update($activationDetail, $input);
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
        else
        {
            throw new BadRequestValidationFailureException("sales poc id field is required");
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
                Comment\Entity::ADDED_AT => time()
            ]);
        }
    }
}
