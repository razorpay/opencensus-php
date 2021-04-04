<?php


namespace Unit\Mail;

use RZP\Tests\Functional\TestCase;
use RZP\Models\BankingAccount\Entity;
use RZP\Mail\Base\SendQueuedMailable;
use RZP\Mail\BankingAccount as BankingAccountMail;
use RZP\Tests\Functional\RequestResponseFlowTrait;

/*
 *  TODO test this automatically for all Mailables and Queueables
 */
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

    private function assertStatusChangeMailableSQSPayloadSize($mailableObj)
    {
        $sqsQueueclass = new \ReflectionClass('Illuminate\Queue\SqsQueue');

        $sqsQueueInstance = $sqsQueueclass->newInstanceWithoutConstructor();

        $method = $sqsQueueclass->getMethod('createPayload');

        $method->setAccessible(true);

        $payload =  $method->invokeArgs($sqsQueueInstance, [$mailableObj, '']);

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

        $bankingAccount = $this->createBankingAccount();

        foreach ($mailableClasses as $mailableClass)
        {
            $mailableObj = new SendQueuedMailable(new $mailableClass($bankingAccount['id']));

            $this->assertStatusChangeMailableSQSPayloadSize($mailableObj);
        }
    }

    public function testXProActivationMailableSQSPayloadSize()
    {
        $bankingAccount = $this->createBankingAccount();

        $mailableObj = new SendQueuedMailable(new BankingAccountMail\XProActivation($bankingAccount));

        $this->assertStatusChangeMailableSQSPayloadSize($mailableObj);
    }

    public function testStatementMailableSQSPayloadSize()
    {
        $this->createBankingAccount();

        $merchant = $this->fixtures->merchant->create();

        $toEmails = ['test@razorpay.com'];

        $fromDate = '946684800';

        $toDate = '947684800';

        $fileDownloadUrl = str_repeat("a",256); //maxlength for filename

        $mailableObj = new SendQueuedMailable(new BankingAccountMail\StatementMail($merchant, $toEmails, $fromDate, $toDate, $fileDownloadUrl));

        $this->assertStatusChangeMailableSQSPayloadSize($mailableObj);
    }
}
