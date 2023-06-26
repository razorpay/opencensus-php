<?php

namespace RZP\Models\BankingAccount\BankLms;

use RZP\Constants\Mode;
use RZP\Exception\LogicException;
use RZP\Models\BankingAccount;
use RZP\Models\BankingAccount\Activation\Comment;
use RZP\Models\BankingAccount\Activation\Detail as ActivationDetail;
use RZP\Exception\BadRequestException;
use RZP\Exception\InvalidArgumentException;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Jobs\BankingAccount\BankingAccountRblMisReport;
use RZP\Models\BankingAccountService\BasDtoAdapter;
use \RZP\Models\Merchant;
use \RZP\Models\User;
use RZP\Trace\TraceCode;

class Service extends BankingAccount\Service
{
    /** @var Core $core*/
    protected $core;

    protected $validator;

    protected $repository;

    /** @var Merchant\Entity $partnerBankMerchant */
    protected $partnerBankMerchant;

    /** @var  BranchMaster $branchMaster*/
    protected $branchMaster;

    /** @var RmMaster $rmMaster*/
    protected $rmMaster;

    const ACTIVITY_TYPE = 'activity_type';
    const COMMENT = 'comment';
    const STATE_CHANGE = 'state_change';

    /**
     * @throws BadRequestException
     */
    public function __construct($pincodeSearch = null, $core = null)
    {
        parent::__construct($pincodeSearch, $core);

        $this->core = new Core();

        $this->validator = new Validator();

        $this->branchMaster = new BranchMaster();

        $this->rmMaster = new RmMaster();

        $partnerBankMerchantId = $this->validator->validateOnlyOneCaBankPartnerAndReturn();

        $this->partnerBankMerchant = is_null($partnerBankMerchantId) ? null : $this->repo->merchant->findByPublicId($partnerBankMerchantId);

        $this->repository = new Repository();
    }

    /**
     * @param $input
     *
     * @return array
     * @throws BadRequestException
     */
    public function transformNormalMerchantToBankPartner($input): array
    {
        $this->validator->validateInput(Validator::CREATE_BANK_CA_ONBOARDING_PARTNER_TYPE, $input);

        $partnerBankMerchantId = $this->validator->validateOnlyOneCaBankPartnerAndReturn();

        if ($partnerBankMerchantId !== null) {
            return ['success' => false, 'reason' => 'One CA Bank Partner Merchant is Already There'];
        }

        return $this->core->transformNormalMerchantToBankPartner($input);
    }

    /**
     * @throws BadRequestValidationFailureException
     * @throws LogicException
     * @throws BadRequestException
     * To be used by Admin
     */
    public function attachCaApplicationMerchantToBankPartnerBulk(array $input): array
    {
        foreach ($input['banking_account_ids'] as $banking_account_id)
        {
            $this->attachCaApplicationMerchantToBankPartner([BankingAccount\Entity::BANKING_ACCOUNT_ID => $banking_account_id]);
        }
        return ['success' => true];
    }

    /**
     * @return  array
     */
    public function fetchBranchList(): array
    {
        $data = $this->branchMaster->getBranches();
        return [
            'count' => sizeof($data),
            'data' => $data
        ];
    }

    /**
     * @return  array
     */
    public function fetchRmList(): array
    {
        $data = $this->rmMaster->getRms();
        return [
            'count' => sizeof($data),
            'data' => $data
        ];
    }

    /**
     * @throws LogicException
     * @throws BadRequestException
     * @throws BadRequestValidationFailureException
     */
    public function attachCaApplicationMerchantToBankPartner(array $input): array
    {
        if ($this->partnerBankMerchant === null)
        {
            return ['success' => false];
        }

        $this->validator->validateInput(Validator::ATTACH_CA_MERCHANT_TO_BANK_PARTNER, $input);

        $bankingAccount = $this->repo->banking_account->findByPublicId($input[BankingAccount\Entity::BANKING_ACCOUNT_ID]);

        return $this->core->attachCaApplicationMerchantToBankPartner($this->partnerBankMerchant, $bankingAccount);
    }

    /**
     * @param array $input
     *
     * @return void
     * @throws BadRequestException
     */
    public function detachCaApplicationMerchantFromBankPartner(array $input)
    {
        if ($this->partnerBankMerchant === null)
        {
            return;
        }

        $this->validator->validateInput(Validator::DETACH_CA_MERCHANT_FROM_BANK_PARTNER, $input);

        $bankingAccount = $this->repo->banking_account->findByPublicId($input[BankingAccount\Entity::BANKING_ACCOUNT_ID]);

        $subMerchantIds = (new Repository())->fetchSubMerchantForPartnerAndSubMerchantId($this->partnerBankMerchant, $bankingAccount->merchant);

        if (count($subMerchantIds) !== 0)
        {
            $this->core->detachCaApplicationMerchantFromBankPartner($this->partnerBankMerchant, $bankingAccount->merchant);
        }
    }


    /**
     * @param string $bankingAccountId
     * @param array  $input
     *
     * @return mixed
     * @throws BadRequestException
     */
    public function assignBankPartnerPocToApplication(string $bankingAccountId, array $input)
    {
        $this->validator->validateInput(Validator::ASSIGN_BANK_PARTNER_POC_TO_APPLICATION, $input);

        $bankPocUserId = $input[BankingAccount\Activation\Detail\Entity::BANK_POC_USER_ID];

        $this->validator->validateUserBelongsToPartnerBankMerchant($bankPocUserId, $this->partnerBankMerchant);

        [$existsInApi, $bankingAccount] = $this->checkAndGetBankingAccountId($bankingAccountId);

        if ($existsInApi == false)
        {

            $this->trace->info(
                TraceCode::BANKING_ACCOUNT_SERVICE_RBL_ON_BAS_REQUEST,
                [
                    'banking_account_id' => $bankingAccountId,
                    'route'              => $this->app['router']->currentRouteName(),
                ]);

            return $this->bankingAccountService->assignBankPocForRblPartnerLms($bankingAccountId, $bankPocUserId);
        }

        $this->validator->validateMerchantIsAttachedToPartner($bankingAccount->merchant, $this->partnerBankMerchant);

        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_ASSIGN_BANK_POC,
            [
                'id'      => $bankingAccount->getId(),
                'input'   => $input,
            ]);

        $bankingAccount = $this->core->assignBankPartnerPocToApplication($bankingAccount, $bankPocUserId);

        return $bankingAccount->toArrayCaPartnerBankPoc();
    }

    /**
     * @throws BadRequestValidationFailureException
     * @throws InvalidArgumentException
     * @throws BadRequestException
     */
    public function fetchMultipleBankingAccountEntity(array $input): array
    {
        $originalSkip  = $input[BankingAccount\Constants::SKIP] ?? 0;
        $originalCount = $input[BankingAccount\Constants::COUNT] ?? 10;
        $sortDirection = $input[Constants::SORT_SENT_TO_BANK_DATE] ?? 'desc';

        $rowsToFetch   = $originalSkip + $originalCount;

        // Fetch skip + count rows from both sources
        // We can apply skip and count after merging data from both sources
        $input[BankingAccount\Constants::SKIP]  = 0;
        $input[BankingAccount\Constants::COUNT] = $rowsToFetch;

        // Fetch from DB
        $bankingAccountsFromDb = $this->core->fetchMultipleBankingAccountEntity($input, $this->partnerBankMerchant);
        $bankingAccountsFromDbAsArray = $bankingAccountsFromDb->toArrayCaPartnerBankPoc()['items'];

        // Fetch from BAS
        $bankingAccountsFromBas = $this->bankingAccountService->getMultipleApplicationsForRblPartnerLms($input);

        // Merge data from both sources
        $bankingAccounts = $this->mergeBankingAccountArrays($bankingAccountsFromDbAsArray, $bankingAccountsFromBas,
                                                            $originalSkip, $originalCount, Entity::SENT_TO_BANK_DATE,
                                                            $sortDirection);

        return (new BasDtoAdapter())->arrayAsPublicCollection($bankingAccounts);
    }

    /**
     * @param string $bankingAccountId
     * @param array  $input
     *
     * @return array
     * @throws BadRequestException
     */
    public function fetchBankingAccountById(string $bankingAccountId, array $input): array
    {
        [$existsInApi, $bankingAccount] = $this->checkAndGetBankingAccountId($bankingAccountId);

        if ($existsInApi == false)
        {
            $this->trace->info(
                TraceCode::BANKING_ACCOUNT_SERVICE_RBL_ON_BAS_REQUEST,
                [
                    'banking_account_id' => $bankingAccountId,
                    'route'              => $this->app['router']->currentRouteName(),
                ]);

            return $this->bankingAccountService->getApplicationForRblPartnerLms($bankingAccountId);
        }
        $this->validator->validateMerchantIsAttachedToPartner($bankingAccount->merchant, $this->partnerBankMerchant);

        $entity = $this->core->fetchBankingAccountById($bankingAccountId, $input);

        return $entity->toArrayCaPartnerBankPoc();
    }

    /**
     * @param Entity                 $bankingAccount
     * @param array                  $input
     * @param Base\PublicEntity|null $entity
     * @param bool                   $isAutomatedUpdate
     * @param bool                   $fromDashboard
     *
     * @return Entity
     * @throws BadRequestException
     * @throws LogicException
    */
    public function updateLeadDetails(string $bankingAccountId, array $input)
    {
        // Moving validation logic to before we check if the account exist,
        // because some banking accounts may exist in BAS
        $this->validator->validateInput(Validator::PARTNER_LMS_EDIT, $input);

        if (array_key_exists('activation_detail', $input))
        {
            $activationDetailInput = $input['activation_detail'];
            $this->validator->validateInput(Validator::EDIT_ACTIVATION_DETAIL_BY_BANK, $activationDetailInput);

            if (array_key_exists(ActivationDetail\Entity::RBL_ACTIVATION_DETAILS, $activationDetailInput))
            {
                $rblActivationDetails = $activationDetailInput[ActivationDetail\Entity::RBL_ACTIVATION_DETAILS];
                $this->validator->validateInput(Validator::RBL_ACTIVATION_DETAILS, $rblActivationDetails);
            }
        }

        [$existsInApi, $bankingAccount] = $this->checkAndGetBankingAccountId($bankingAccountId);

        if ($existsInApi === false)
        {
            return $this->updateApplicationOnBas($bankingAccountId, $input);
        }

        $bankingAccount = $this->repo->banking_account->findByPublicId($bankingAccountId);

        $this->validator->validateMerchantIsAttachedToPartner($bankingAccount->merchant, $this->partnerBankMerchant);

        $this->update($bankingAccountId, $input, true);

        $entity = $this->core->fetchBankingAccountById($bankingAccountId, $input);

        return $entity->toArrayCaPartnerBankPoc();
    }

    /**
     * @throws BadRequestException
     * @throws InvalidArgumentException|BadRequestValidationFailureException
     */
    public function fetchBankingAccountsActivationActivityById(string $bankingAccountId, array $input): array
    {
        [$existsInApi, $bankingAccount] = $this->checkAndGetBankingAccountId($bankingAccountId);

        if ($existsInApi == false)
        {
            $this->trace->info(
                TraceCode::BANKING_ACCOUNT_SERVICE_RBL_ON_BAS_REQUEST,
                [
                    'banking_account_id' => $bankingAccountId,
                    'route'              => $this->app['router']->currentRouteName(),
                ]);

            return $this->bankingAccountService->getActivityForRblPartnerLms($bankingAccountId, $input);
        }

        $this->validator->validateMerchantIsAttachedToPartner($bankingAccount->merchant, $this->partnerBankMerchant);

        $activity = [];

        $comments = $this->core->fetchBankingAccountsActivationCommentById($bankingAccount, [
            Comment\Fetch::FOR_SOURCE_TEAM_TYPE => 'external',
            Comment\Fetch::EXPAND => [Comment\Entity::USER, Comment\Entity::ADMIN],
            Comment\Fetch::COUNT => 100,
        ]);

        if ($comments->getHasMore())
        {
            $this->trace->error(TraceCode::BANKING_ACCOUNT_COMMENT_FETCH_LIMIT_EXCEEDED, [
                'banking_account_id' => $bankingAccount['id'],
            ]);
        }

        $comments = $comments->toArrayPublicWithExpand();

        foreach ($comments['items'] as $comment)
        {
            $comment[self::ACTIVITY_TYPE] = self::COMMENT;
            array_push($activity, $comment);
        }

        $users = (new Merchant\Service())->getUsersWithFilters([
            Merchant\Entity::MERCHANT_ID => $this->partnerBankMerchant->getId(),
        ]);

        $userIds = array_map(function($user) {
            return $user[User\Entity::ID];
        }, $users);

        $states = (new BankingAccount\State\Repository())->getBankingAccountsStateByUserIds($bankingAccount->getId(), $userIds);

        $states = $states->toArrayPublicWithExpand();

        foreach ($states['items'] as $state)
        {
            $state[self::ACTIVITY_TYPE] = self::STATE_CHANGE;
            array_push($activity, $state);
        }

       $this->sortActivity($activity, $input['sort'] ?? 'asc');

        $response = [
            'count' => count($activity),
            'items' => $activity,
        ];

        return $response;
    }

    /**
     * @throws BadRequestException
     * @throws InvalidArgumentException|BadRequestValidationFailureException
     */
    public function fetchBankingAccountsActivationCommentById(string $bankingAccountId, array $input): array
    {
        [$existsInApi, $bankingAccount] = $this->checkAndGetBankingAccountId($bankingAccountId);

        if ($existsInApi == false)
        {
            $this->trace->info(
                TraceCode::BANKING_ACCOUNT_SERVICE_RBL_ON_BAS_REQUEST,
                [
                    'banking_account_id' => $bankingAccountId,
                    'route'              => $this->app['router']->currentRouteName(),
                ]);

            return $this->bankingAccountService->getCommentsForRblPartnerLms($bankingAccountId);
        }
        $this->validator->validateMerchantIsAttachedToPartner($bankingAccount->merchant, $this->partnerBankMerchant);

        // Necessary filters
        $input[Comment\Fetch::FOR_SOURCE_TEAM_TYPE] = 'external';

        $entities = $this->core->fetchBankingAccountsActivationCommentById($bankingAccount, $input);

        return $entities->toArrayPublicWithExpand();
    }

    /**
     * @throws BadRequestException
     * @throws InvalidArgumentException|BadRequestValidationFailureException
     */
    public function createBankingAccountsActivationComment(string $bankingAccountId, array $input)
    {
        [$existsInApi, $bankingAccount] = $this->checkAndGetBankingAccountId($bankingAccountId);

        if ($existsInApi == false)
        {
            $this->trace->info(
                TraceCode::BANKING_ACCOUNT_SERVICE_RBL_ON_BAS_REQUEST,
                [
                    'banking_account_id' => $bankingAccountId,
                    'route'              => $this->app['router']->currentRouteName(),
                ]);

            return $this->bankingAccountService->addCommentForRblPartnerLms($bankingAccountId, $input);
        }

        $this->validator->validateMerchantIsAttachedToPartner($bankingAccount->merchant, $this->partnerBankMerchant);

        // Necessary filters
        $input[Comment\Entity::SOURCE_TEAM] = 'bank';
        $input[Comment\Entity::SOURCE_TEAM_TYPE] = 'external';

        $this->validator->validateInput(Validator::ADD_COMMENT, $input);

        return $this->core->createBankingAccountsActivationComment($bankingAccount, $input);
    }

    /**
     * @param array $input
     *
     * @return array
     * @throws BadRequestException|BadRequestValidationFailureException
     */
    public function downloadActivationMis(array $input): array
    {
        $this->validator->validateInput(Validator::DOWNLOAD_MIS_FROM_PARTNER_BANK, $input);

        return $this->core->downloadActivationMis($this->partnerBankMerchant, $input);
    }

    /**
     * @param array $input
     *
     * @return array
     * @throws BadRequestException|BadRequestValidationFailureException
     */
    public function requestActivationMisReport(array $input): array
    {
        $this->validator->validateInput(Validator::REQUEST_MIS_FROM_PARTNER_BANK, $input);

        $user = $this->auth->getUser()->toArray();

        BankingAccountRblMisReport::dispatch(Mode::LIVE, $input, $user);

        return [
            'status' => 'success',
            'message' => 'Report will be sent over email in a few mins.'
        ];
    }

    /**
     * @param array $input
     *
     * @return array
     * @throws BadRequestException|BadRequestValidationFailureException
     */
    public function sendActivationMisReport(array $input)
    {
        return $this->core->sendActivationMisReport($this->partnerBankMerchant, $input);
    }

    public function setPartnerMerchantBasicAuth()
    {
        $this->app['basicauth']->setMerchant($this->partnerBankMerchant);
    }

    public function sortActivity(array &$activity, $sortDirection = 'asc')
    {
        usort($activity, function ($a, $b) use ($sortDirection) {
            $a_createdAt = $a[Comment\Entity::CREATED_AT];
            $b_createdAt = $b[Comment\Entity::CREATED_AT];

            if ($a[self::ACTIVITY_TYPE] === self::COMMENT)
            {
                $a_createdAt = $a[Comment\Entity::ADDED_AT];
            }

            if ($b[self::ACTIVITY_TYPE] === self::COMMENT)
            {
                $b_createdAt = $b[Comment\Entity::ADDED_AT];
            }

            if ($sortDirection === 'asc')
            {
                return $a_createdAt - $b_createdAt;
            }
            else // default
            {
                return $b_createdAt - $a_createdAt;
            }
        });
    }

}
