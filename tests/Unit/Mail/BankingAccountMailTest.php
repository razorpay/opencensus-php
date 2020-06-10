<?php


namespace Unit\Mail;

use RZP\Tests\Functional\TestCase;
use RZP\Models\BankingAccount\Entity;
use RZP\Mail\Base\SendQueuedMailable;
use RZP\Mail\BankingAccount as BankingAccountMail;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class BankingAccountMailTest extends TestCase
{
    use RequestResponseFlowTrait;

    const MAX_PAYLOAD_SIZE = 262144;

    protected function createBankingAccount(array $attributes = [])
    {
        $data = [
            Entity::PINCODE => '560030',
            Entity::CHANNEL => 'rbl'
        ];

        $data = array_merge($data, $attributes);

        $request = [
            'method'  => 'post',
            'url'     => '/banking_accounts',
            'content' => $data
        ];

        $this->ba->proxyAuth();

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    private function getStringSizeinBytes(string $obj)
    {
        //https://stackoverflow.com/questions/11366412/how-to-get-the-size-of-the-content-of-a-variable-in-php/11367034#11367034
        return strlen($obj);
    }

    private function assertStatusChangeMailableSQSPayloadSize($mailable)
    {
        $sqsQueueclass = new \ReflectionClass('Illuminate\Queue\SqsQueue');

        $sqsQueueInstance = $sqsQueueclass->newInstanceWithoutConstructor();

        $method = $sqsQueueclass->getMethod('createPayload');

        $method->setAccessible(true);

        $bankingAccount = $this->createBankingAccount();

        $mailable = new SendQueuedMailable(new $mailable($bankingAccount['id']));

        $payload =  $method->invokeArgs($sqsQueueInstance, [$mailable]);

        $payloadSize = $this->getStringSizeinBytes($payload);

        $this->assertLessThan(self::MAX_PAYLOAD_SIZE, $payloadSize);
    }

    public function testAllStatusChangeMailableSQSPayloadSize()
    {
        $mailableClasses = [
            BankingAccountMail\StatusNotifications\Activated::class,
            BankingAccountMail\StatusNotifications\Created::class,
            BankingAccountMail\StatusNotifications\Rejected::class,
            BankingAccountMail\StatusNotifications\Unserviceable::class,
            BankingAccountMail\StatusNotifications\Processed::class,
            BankingAccountMail\StatusNotifications\Cancelled::class,
            BankingAccountMail\StatusNotifications\Processing::class
        ];

        foreach ($mailableClasses as $mailableClass)
        {
            $this->assertStatusChangeMailableSQSPayloadSize($mailableClass);
        }
    }
}
