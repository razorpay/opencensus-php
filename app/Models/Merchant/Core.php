<?php

namespace RZP\Models\Merchant;

use Config;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\User;
use RZP\Models\Feature;
use RZP\Constants\Mode;
use RZP\Models\Pricing;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\Terminal;
use RZP\Models\BankAccount;
use RZP\Models\Admin\Action;
use RZP\Models\Merchant\Detail;
use RZP\Models\Schedule\Task as ScheduleTask;

class Core extends Base\Core
{
    public function create($input)
    {
        $merchant = (new Merchant\Entity)->build($input);

        $merchant->setAuditAction(Action::CREATE_MERCHANT);

        $email['email'] = $input['email'];

        $merchant->getValidator()->validateInput('unique_email', $email);

        $merchant->setPricingPlan(Pricing\DefaultPlan::STARTUP_PLAN_ID);

        $this->repo->saveOrFail($merchant);

        $this->addMerchantSupportingEntities($merchant);

        if (isset($input['groups']) === true)
        {
            $this->repo->sync($merchant, 'groups', $input['groups']);
        }

        // Updating the existing customer info and setting activated to false
        $this->app['drip']->sendDripMerchantInfo($merchant, Merchant\Action::CREATED);

        return $merchant;
    }

    public function createSubMerchant($input, $aggregatorMerchant)
    {
        // We only check for email uniqueness if the email
        // address is provided
        if (isset($input['email']) === true)
        {
            $email['email'] = $input['email'];
            (new Validator)->validateInput('unique_email', $email);
        }
        else
        {
            $input['email'] = $aggregatorMerchant->getEmail();
        }

        $subMerchant = (new Merchant\Entity)->build($input);

        $subMerchant->setAuditAction(Action::CREATE_SUBMERCHANT);

        $subMerchant->setPricingPlan($aggregatorMerchant->getPricingPlanId());

        if ($aggregatorMerchant->isMarketplace() === true)
        {
            $subMerchant->parent()->associate($aggregatorMerchant);
        }

        $aggregatorOrgId = $aggregatorMerchant->getOrgId();

        if ($aggregatorOrgId !== null)
        {
           $org = $this->repo->org->findOrFailPublic($aggregatorOrgId);

            // Link sub-merchant to its aggregator's org
            $subMerchant->org()->associate($org);
        }

        $this->repo->saveOrFail($subMerchant);

        $this->addMerchantSupportingEntities($subMerchant);

        return $subMerchant;
    }

    protected function addMerchantSupportingEntities($merchant)
    {
        $this->createBalance($merchant, Mode::TEST);

        (new BankAccount\Core)->createTestBankAccount($merchant);

        (new Methods\Core)->setDefaultMethods($merchant);

        (new Detail\Service)->createMerchantDetails($merchant);

        (new ScheduleTask\Core)->createDefaultSettlementSchedule($merchant);
    }

    /**
     * Edit merchant entity
     *
     * @param \RZP\Models\Merchant\Entity $merchant
     * @param array $input
     * @return \RZP\Models\Merchant\Entity
     */
    public function edit($merchant, $input)
    {
        $merchant->setAuditAction(Action::EDIT_MERCHANT);

        $merchant->edit($input);

        $plan = $this->repo->pricing->getMerchantPricingPlan($merchant);

        (new Methods\Core)->validateInternationalPricingForMerchant($merchant, $plan);

        $this->saveAndNotify($merchant);

        // Groups have to be saved separately
        //
        // Also since we're doing a fetch again it's better we save
        // the previous version of $merchant entity first and then fetch it.
        if (isset($input['groups']) === true)
        {
            $this->repo->sync($merchant, 'groups', $input['groups']);

            // If groups has been edited, fetch the entity again with relations.
            // Simple entity edit does not contain updated relations
            $merchant = $this->repo
                             ->merchant
                             ->findOrFailPublicWithRelations($merchant->getId(), ['groups']);
        }

        $this->trace->info(
            TraceCode::MERCHANT_EDIT,
            [
                'merchant_id' => $merchant->getId(),
                'input'       => $input,
            ]);

        return $merchant;
    }

    /**
     * Edit merchant email
     *
     * @param \RZP\Models\Merchant\Entity $merchant
     * @param array $input
     * @return \RZP\Models\Merchant\Entity
     */
    public function editEmail($merchant, $input)
    {
        $this->trace->info(
            TraceCode::MERCHANT_EDIT,
            [
                'old_email' => $merchant->getEmail(),
                'new_email' => $input['email']
            ]);

        $merchant->edit($input, 'editEmail');

        $this->saveAndNotify($merchant);

        return $merchant;
    }

    /**
     * Edit merchant configuration
     *
     * @param \RZP\Models\Merchant\Entity $merchant
     * @param array $input
     * @return \RZP\Models\Merchant\Entity
     */
    public function editConfig($merchant, $input)
    {
        $this->trace->info(
            TraceCode::MERCHANT_EDIT,
            [
                'merchant_id' => $merchant->getId(),
                'input'       => $input,
            ]);

        $merchant->edit($input, 'editConfig');

        $this->saveAndNotify($merchant);

        return $merchant;
    }

    public function createBalance($merchant, $mode)
    {
        $merchantBalance = Merchant\Balance\Entity::buildFromMerchant($merchant);

        $merchantBalance->setConnection($mode);

        $this->repo->balance->createBalance($merchantBalance);

        return $merchantBalance;
    }

    public function getUsers(string $merchantId)
    {
        $users = $this->repo->user->getUsersForMerchant($merchantId);

        foreach ($users as $user)
        {
            $user[User\Entity::CONFIRMED] = ($user[User\Entity::CONFIRM_TOKEN] === null);

            unset($user[User\Entity::CONFIRM_TOKEN]);
        }

        return $users;
    }

    /**
     * Save merchant entity and notify on slack
     *
     * @param \RZP\Models\Merchant\Entity $merchant
     * @return null
     */
    protected function saveAndNotify($merchant)
    {
        $data = $this->getEditedMerchantDifference($merchant);

        $this->repo->saveOrFail($merchant);

        if (empty($data) === false)
        {
            $label   = $merchant->getBillingLabel();
            $message = $merchant->getDashboardEntityLinkForSlack($label);
            $user    = $this->getInternalUsernameOrEmail();

            $message .= ' ' . $merchant->getEntity() . ' edited by ' . $user;

            $this->app['slack']->queue(
                $message,
                $data,
                [
                    'channel'  => Config::get('slack.channels.operations_log'),
                    'username' => 'Jordan Belfort',
                    'icon'     => ':boom:'
                ]
            );
        }
    }

    /**
     * Get difference between the original and updated attributes
     *
     * @param \RZP\Models\Merchant\Entity $merchant
     * @return array|null
     */
    protected function getEditedMerchantDifference($merchant)
    {
        $original = $merchant->getOriginalAttributesAgainstDirty();

        if ($original !== null)
        {
            $dirtyAttributes = $merchant->getDirty();

            $data = array();

            foreach ($original as $key => $value)
            {
                $data[$key] = '*Old*: ' . $value . PHP_EOL . '*New*: ' . $dirtyAttributes[$key];
            }

            return $data;
        }
    }
}
