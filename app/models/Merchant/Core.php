<?php

namespace Models\Merchant;

use Constants\Mode;
use Models\Base;
use Models\Merchant;
use Models\Pricing;
use Models\Terminal;
use Trace\TraceCode;
use EE\Exception;
use Services\SlackPoster;

class Core extends Base\Core
{
    use SlackPoster;

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

        (new Merchant\BankAccount\Core)->createTestBankAccount($merchant);

        (new Methods\Core)->setDefaultMethods($merchant);
    }

    /**
     * Edit merchant entity
     *
     * @param \Models\Merchant\Entity $merchant
     * @param array $input
     * @return \Models\Merchant\Entity
     */
    public function edit($merchant, $input)
    {
        $merchant->edit($input);

        $this->saveAndNotify($merchant);

        $this->trace->info(
            TraceCode::MERCHANT_EDIT,
            $input);

        return $merchant;
    }

    /**
     * Edit merchant email
     *
     * @param \Models\Merchant\Entity $merchant
     * @param array $input
     * @return \Models\Merchant\Entity
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
     * @param \Models\Merchant\Entity $merchant
     * @param array $input
     * @return \Models\Merchant\Entity
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

        (new Merchant\Balance\Repository)->createBalance($merchantBalance);

        return $merchantBalance;
    }

    public function addOrUpdateMerchantFeatures($merchant, $input)
    {
        $this->trace->info(
            TraceCode::MERCHANT_EDIT,
            array('old_features' => $merchant->getFeatures(),
                  'new_features' => $input[Entity::FEATURES]));

        $merchant->edit($input);

        $this->repo->saveOrFail($merchant);

        return $merchant;
    }

    /**
     * Save merchant entity and notify on slack
     *
     * @param \Models\Merchant\Entity $merchant
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

            $this->slackPost($message, $data, ['channel' => '#operations_log',
                                               'username' => 'Jordan Belfort',
                                               'icon' => ':boom:']);
        }
    }

    /**
     * Get difference between the original and updated attributes
     *
     * @param \Models\Merchant\Entity $merchant
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
