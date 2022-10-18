<?php

namespace RZP\Models\Payout\Processor\DownstreamProcessor;

use App;

use RZP\Models\Admin;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\Payout\Entity;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Settlement\Channel;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Merchant\Balance\AccountType;

class DownstreamProcessor
{
    protected $app;

    protected $type;

    protected $mode;

    protected $payout;

    protected $ftaAccount;

    public function __construct(string $type, Entity $payout, string $mode, PublicEntity $ftaAccount = null)
    {
        $this->app = App::getFacadeRoot();

        $this->type = $type;

        $this->mode = $mode;

        $this->payout = $payout;

        $this->ftaAccount = $ftaAccount;
    }

    public function process()
    {
        $subProcessor = $this->getSubProcessorClass();

        $subProcessor->process($this->payout, $this->ftaAccount);
    }

    public function processIcici2FA()
    {
        (new FundAccountPayout\Direct\Icici)->processIcici2FAPayout($this->payout, $this->ftaAccount);
    }

    public function processTransaction()
    {
        $subProcessor = $this->getSubProcessorClass();

        return $subProcessor->processTransaction($this->payout);
    }

    public function processPayoutThroughLedger()
    {
        $subProcessor = $this->getSubProcessorClass();

        return $subProcessor->processPayoutThroughLedger($this->payout, $this->ftaAccount);
    }

    /**
     * Should not be used separately. Please check process func.
     */
    public function processCreateFundTransferAttempt()
    {
        $subProcessor = $this->getSubProcessorClass();

        $subProcessor->processCreateFundTransferAttempt($this->payout, $this->ftaAccount);
    }

    public function processFetchPricingInfoForPayoutsService()
    {
        $subProcessor = $this->getSubProcessorClass();

        $subProcessor->setFeeAndTaxForPayout($this->payout);
    }

    public function processAdjustFeeAndTaxesIfCreditsAvailable()
    {
        $subProcessor = $this->getSubProcessorClass();

        $subProcessor->adjustFeeAndTaxesIfCreditsAvailable($this->payout);
    }

    public function getSubProcessorClass()
    {
        $subProcessor = __NAMESPACE__ . '\\' . studly_case($this->type);

        if (snake_case($this->type) === 'fund_account_payout')
        {
            $accountType = $this->getAccountTypeForFundTransfer();

            $channel = $this->payout->getChannel();

            if (empty($channel) === true)
            {
                $channel = $this->getChannelForFundTransfer($accountType);
            }

            $subProcessor = $subProcessor . '\\' . studly_case($accountType) . '\\' . studly_case($channel);

            if (class_exists($subProcessor) === false)
            {
                $subProcessor =
                    __NAMESPACE__ . '\\' . studly_case($this->type) . '\\' . studly_case($accountType) . '\\' . 'Base';
            }
        }

        return new $subProcessor;
    }

    public function getAccountTypeForFundTransfer()
    {
        return $this->payout->balance->getAccountType() ?? AccountType::SHARED;
    }

    /**
     * Adding for backward compatibility .
     * Relevant Slack Thread : https://razorpay.slack.com/archives/CE4DMABE3/p1579599527095500
     *
     * @param $accountType
     *
     * @return string
     */
    protected function getChannelForFundTransfer($accountType): string
    {
        if ($accountType === AccountType::DIRECT)
        {
            return $this->getChannelForDirectAccountFundTransfer();
        }

        return $this->getChannelForSharedAccountFundTransfer();
    }

    protected function getChannelForDirectAccountFundTransfer()
    {
        return $this->payout->balance->getChannel();
    }

    /*
     * One MID can't have more than one variant for same experiment, so there will be no clash.
     */
    protected function getChannelForSharedAccountFundTransfer()
    {
        $merchant = $this->payout->merchant;

        $mode = $this->payout->getMode();

        if ($mode === FundTransferMode::CARD)
        {
            return Channel::M2P;
        }

        $razorxFeature = strtoupper(sprintf("%s_MODE_PAYOUT_FILTER", $mode));

        $variant = $this->app->razorx->getTreatment(
            $merchant->getId(),
            constant(RazorxTreatment::class . '::' . $razorxFeature),
            $this->mode,
            Entity::RAZORX_RETRY_COUNT
        );

        if (strtolower($variant) === 'control')
        {
            return Channel::YESBANK;
        }

        return constant(Channel::class . '::' . strtoupper($variant));
    }
}
