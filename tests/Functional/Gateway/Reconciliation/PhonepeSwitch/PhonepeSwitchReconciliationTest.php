<?php

namespace RZP\Tests\Functional\Gateway\Reconciliation\PhonepeSwitch;


use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Models\Payment\Processor\Wallet;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;
use RZP\Tests\Functional\Gateway\Reconciliation\Phonepe\PhonepeReconciliationTest;

class PhonepeSwitchReconciliationTest extends PhonepeReconciliationTest
{
    use ReconTrait;
    use PaymentTrait;
    use DbEntityFetchTrait;

    private $payment;

    private $sharedTerminal;

    protected $wallet = Wallet::PHONEPE_SWITCH;

    protected $method = Payment\Method::WALLET;

    protected function setUp(): void
    {
        parent::setUp();

        $this->payment = $this->getDefaultWalletPaymentArray($this->wallet);

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_phonepeswitch_terminal');

        $this->gateway = Payment\Gateway::WALLET_PHONEPESWITCH;

        $this->fixtures->merchant->enableWallet(Merchant\Account::TEST_ACCOUNT, $this->wallet);
    }
}
