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

    public function __construct()
    {
        parent::__construct();

        $this->repo = new Merchant\Repository;
    }

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

    public function edit($merchant, $input)
    {
        $merchant->edit($input);

        $data = $this->getEditedMerchantDifference($merchant);

        $this->repo->saveOrFail($merchant);

        if (empty($data) === false)
        {
            $this->postToSlack($merchant, $data);
        }

        $this->trace->info(
            TraceCode::MERCHANT_EDIT,
            [$input]);

        return $merchant;
    }

    public function editEmail($merchant, $input)
    {
        $this->trace->info(
            TraceCode::MERCHANT_EDIT,
            ['old_email' => $merchant->getEmail()],
            ['new_email' => $input['email']]);

        $merchant->edit($input, 'editEmail');

        $this->repo->saveOrFail($merchant);

        return $merchant;
    }

    public function editConfig($merchant, $input)
    {
        $this->trace->info(
            TraceCode::MERCHANT_EDIT,
            [$input]);

        $merchant->edit($input, 'editConfig');

        $this->repo->saveOrFail($merchant);

        // Proxy Auth, take care not to return the entire merchant entity
        return $this->repo->findOrFailPublic($merchant->id, Entity::CONFIG_LIST);
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

    protected function postToSlack($merchant, $data)
    {
        $message = $this->getMerchantDashboardSlackText($merchant);

        $this->slackPost($message, $data, ['channel' => '#operations_log']);
    }

    protected function getEditedMerchantDifference($merchant)
    {
        $dirtyAttributes    = $merchant->getDirty();

        $originalAttributes = array_intersect_key($merchant->getOriginal(), $dirtyAttributes);

        $data = array();

        foreach ($originalAttributes as $key => $value)
        {
            $data[$key] = $value;
            $data['Updated '.$key] = $dirtyAttributes[$key];
        }

        return $data;
    }

    protected function getMerchantDashboardSlackText($merchant)
    {
        $id = $merchant->id;
        $link = "https://dashboard.razorpay.com/admin#/app/merchants/$id/detail";

        $label = $merchant->getBillingLabel();

        return "<$link|$label> ($id)";
    }
}
