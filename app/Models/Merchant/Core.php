<?php

namespace RZP\Models\Merchant;

use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Models\Base;
use RZP\Models\BankAccount;
use RZP\Models\Merchant;
use RZP\Models\Pricing;
use RZP\Models\Terminal;
use RZP\Exception;

use Config;

class Core extends Base\Core
{
    public function create($input)
    {
        $merchant = (new Merchant\Entity)->build($input);

        $email['email'] = $input['email'];

        $merchant->getValidator()->validateInput('unique_email', $email);

        $merchant->setPricingPlan(Pricing\DefaultPlan::STARTUP_PLAN_ID);

        $this->repo->saveOrFail($merchant);

        $this->addMerchantSupportingEntities($merchant);

        return $merchant;
    }

    public function createSubMerchant($input, $aggregatorMerchant)
    {
        // We only check for email uniqueness if the email
        // address is provided
        if (isset($input['email']))
        {
            $email['email'] = $input['email'];
            (new Validator)->validateInput('unique_email', $email);
        }
        else
        {
            $input['email'] = $aggregatorMerchant->getEmail();
        }

        $subMerchant = (new Merchant\Entity)->build($input);

        $subMerchant->setPricingPlan($aggregatorMerchant->getPricingPlanId());

        $this->repo->saveOrFail($subMerchant);

        $this->addMerchantSupportingEntities($subMerchant);

        return $subMerchant;
    }

    protected function addMerchantSupportingEntities($merchant)
    {
        $this->createBalance($merchant, Mode::TEST);

        (new BankAccount\Core)->createTestBankAccount($merchant);

        (new Methods\Core)->setDefaultMethods($merchant);
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
        $merchant->edit($input);

        $plan = $this->repo->pricing->getMerchantPricingPlan($merchant);

        (new Methods\Core)->validateInternationalPricingForMerchant($merchant, $plan);

        $this->saveAndNotify($merchant);

        $this->trace->info(
            TraceCode::MERCHANT_EDIT,
            $input);

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
            $input);

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

            $dashboardInfo = $this->app['basicauth']->getDashboardHeaders();

            $user = $dashboardInfo['admin_user'] ?: $dashboardInfo['merchant'];

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
