<?php

namespace RZP\Tests\Unit\Fetch;

use RZP\Base\JitValidator;
use RZP\Models\PaymentsUpi;
use RZP\Constants\Entity as E;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Unit\Mock\BasicAuth;
use RZP\Tests\Unit\MocksAppServices;
use RZP\Exception\BadRequestException;
use RZP\Exception\ExtraFieldsException;
use RZP\Http\BasicAuth\Type as AuthType;
use RZP\Exception\BadRequestValidationFailureException;

class PaymentsUpiEntityTest extends TestCase
{
    use MocksAppServices;

    /**
     * @var PaymentsUpi\Vpa\Entity
     */
    protected $vpa;

    /**
     * @var PaymentsUpi\BankAccount\Entity
     */
    protected $bankAccount;

    /**
     * @var PaymentsUpi\Vpa\BankAccount\Entity
     */
    protected $vpaBankAccount;

    public function testEntitiesLiveConnections()
    {
        $this->app['basicauth']->setModeAndDbConnection('live');

        $this->createVpa();

        $this->assertSame('payments_upi_live', $this->vpa->getConnectionName());

        $this->createBankAccount();

        $this->assertSame('payments_upi_live', $this->bankAccount->getConnectionName());

        $this->createVpaBankAccount();

        $this->assertSame('payments_upi_live', $this->vpaBankAccount->getConnectionName());
    }

    public function testEntitiesTestConnections()
    {
        $this->app['basicauth']->setModeAndDbConnection('test');

        $this->createVpa();

        $this->assertSame('payments_upi_test', $this->vpa->getConnectionName());

        $this->createBankAccount();

        $this->assertSame('payments_upi_test', $this->bankAccount->getConnectionName());

        $this->createVpaBankAccount();

        $this->assertSame('payments_upi_test', $this->vpaBankAccount->getConnectionName());
    }

    public function testEntitiesLiveReadConnections()
    {
        $this->app['basicauth']->setModeAndDbConnection('live');

        PaymentsUpi\Vpa\Entity::first();

        PaymentsUpi\BankAccount\Entity::first();

        PaymentsUpi\Vpa\BankAccount\Entity::first();

        // This just confirms that no exception is thrown while fetching
        // If we do any write Laravel will stick to write connection
        $this->assertTrue(true);
    }

    public function testEntitiesLiveTestConnections()
    {
        $this->app['basicauth']->setModeAndDbConnection('test');

        PaymentsUpi\Vpa\Entity::first();

        PaymentsUpi\BankAccount\Entity::first();

        PaymentsUpi\Vpa\BankAccount\Entity::first();

        // This just confirms that no exception is thrown while fetching
        // If we do any write Laravel will stick to write connection
        $this->assertTrue(true);
    }

    public function testVpaEntity()
    {
        $this->createVpa();

        $this->vpa->setStatus(PaymentsUpi\Vpa\Status::VALID);
        $this->vpa->setReceivedAt(1234567890);

        $this->vpa->save();

        $this->assertArraySubset([
            PaymentsUpi\Vpa\Entity::STATUS          => PaymentsUpi\Vpa\Status::VALID,
            PaymentsUpi\Vpa\Entity::RECEIVED_AT     => 1234567890,
        ], $this->vpa->toArray());
    }

    /************************ Helpers ****************************/

    protected function createVpa(array $values = [])
    {
        $this->vpa = new PaymentsUpi\Vpa\Entity();

        $this->vpa->forceFill(array_merge([
            'username'  => 'vishnu',
            'handle'    => 'icici'
        ], $values));

        $this->vpa->save();
    }

    protected function createBankAccount(array $values = [])
    {
        $this->bankAccount = new PaymentsUpi\BankAccount\Entity();

        $this->bankAccount->forceFill(array_merge([
            'bank_code'         => 'RZPC',
            'ifsc_code'         => 'RZP10000000',
            'account_number'    => '000000100000011',
        ], $values));

        $this->bankAccount->save();
    }

    protected function createVpaBankAccount(array $values = [])
    {
        $this->vpaBankAccount = new PaymentsUpi\Vpa\BankAccount\Entity();

        $this->vpaBankAccount->forceFill(array_merge([
            'vpa_id'            => $this->vpa->getId(),
            'bank_account_id'   => $this->bankAccount->getId(),
        ], $values));

        $this->vpaBankAccount->save();
    }

}
