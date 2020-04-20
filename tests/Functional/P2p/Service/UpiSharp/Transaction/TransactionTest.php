<?php

namespace RZP\Tests\P2p\Service\UpiSharp\Transaction;

use RZP\Error\P2p\ErrorCode;
use RZP\Models\P2p\Transaction;
use RZP\Http\Controllers\P2p\Requests;
use RZP\Tests\P2p\Service\UpiSharp\TestCase;
use RZP\Tests\P2p\Service\Base\Scenario as S;
use RZP\Models\P2p\Transaction\UpiTransaction;
use RZP\Tests\P2p\Service\Base\Traits\TransactionTrait;

class TransactionTest extends TestCase
{
    use TransactionTrait;

    public function initiatePay()
    {
        $add = $this->addScenario(0);

        $add(S::N0000);
        $add(S::TR101);

        return $add();
    }

    /**
     * @dataProvider initiatePay
     */
    public function testInitiatePay($scenario)
    {
        $helper = $this->getTransactionHelper();

        $helper->withSchemaValidated();

        $helper->setScenarioInContext($scenario);

        $request = $helper->initiatePay();

        if ($helper->getScenarioInContext()->isSuccess() === false)
        {
            return;
        }

        $transaction = $this->fixtures->getDbLastTransaction();

        $this->handleNpciClRequest(
            $request,
            'getCredential',
            $this->expectedCallback(Requests::P2P_CUSTOMER_TRANSACTIONS_AUTHORIZE, [$transaction->getPublicId()]),
            [],
            function($vector) use ($transaction)
            {
                $this->assertTransactionVector($transaction, $vector);
            });
    }

    public function initiateCollect()
    {
        $add = $this->addScenario(0);

        $add(S::N0000);
        $add(S::TR201);

        return $add();
    }

    /**
     * @dataProvider initiateCollect
     */
    public function testInitiateCollect($scenario)
    {
        $helper = $this->getTransactionHelper();

        $helper->withSchemaValidated();

        $helper->setScenarioInContext($scenario);

        $request = $helper->initiateCollect();

        if ($helper->getScenarioInContext()->isSuccess() === false)
        {
            return;
        }

        $transaction = $this->fixtures->getDbLastTransaction();

        $this->assertRequestResponse(
            'redirect',
            ['time' => $this->fixtures->device->getCreatedAt()],
            $this->expectedCallback(
                Requests::P2P_CUSTOMER_TRANSACTIONS_AUTHORIZE,
                [$transaction->getPublicId()],
                ['f' => 'initiateCollect']
            ),
            $request);
    }

    public function testInitiateAuthorize()
    {
        $helper = $this->getTransactionHelper();

        $helper->withSchemaValidated();

        $pay = $this->createPayTransaction();

        $request = $helper->initiateAuthorize($pay->getPublicId());

        $this->handleNpciClRequest(
            $request,
            'getCredential',
            $this->expectedCallback(Requests::P2P_CUSTOMER_TRANSACTIONS_AUTHORIZE, [$pay->getPublicId()]),
            [],
            function($vector) use ($pay)
            {
                $this->assertTransactionVector($pay, $vector);
            });

        $collect = $this->createCollectTransaction();

        $request = $helper->initiateAuthorize($collect->getPublicId());

        $this->assertRequestResponse(
            'redirect',
            ['time' => $this->fixtures->device->getCreatedAt()],
            $this->expectedCallback(
                Requests::P2P_CUSTOMER_TRANSACTIONS_AUTHORIZE,
                [$collect->getPublicId()],
                ['f' => 'initiateCollect']),
            $request);
    }

    public function initiateRejectTransaction()
    {
        $add = $this->addScenario(0);

        $add(S::N0000);
        $add(S::TR401);

        return $add();
    }

    /**
     * @dataProvider initiateRejectTransaction
     */
    public function testInitiateRejectTransaction($scenario)
    {
        $helper = $this->getTransactionHelper();

        $transaction = $this->createCollectIncomingTransaction();

        $helper->withSchemaValidated();

        $helper->setScenarioInContext($scenario);

        $request = $helper->initiateReject($transaction->getPublicId());

        if ($helper->getScenarioInContext()->isSuccess() === false)
        {
            return;
        }

        $this->assertRequestResponse(
            'redirect',
            ['time' => $this->fixtures->device->getCreatedAt()],
            $this->expectedCallback(
                Requests::P2P_CUSTOMER_TRANSACTIONS_AUTHORIZE,
                [$transaction->getPublicId()],
                ['f' => 'initiateReject']),
            $request);
    }


    public function authorizeTransaction()
    {
        $add = $this->addScenario(0,1,2);

        $add('pay', S::N0000, '000', ['completed', '00', 'Transaction is completed']);

        $add('pay', S::TR301, '000', ['created', null, null]);

        $add('pay', S::TR302, '000', ['failed', '102', ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE]);

        $add('pay', S::TR302, 'U18', ['failed', 'U18', ErrorCode::GATEWAY_ERROR_UPI_REQAUTH_TIMEOUT]);

        $add('pay', S::TR302, '0BT', ['pending', 'BT', ErrorCode::GATEWAY_ERROR_TRANSACTION_PENDING]);

        $add('pay', S::TR302, '001', ['pending', '01', ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE]);

        // Collect
        $add('collect', S::N0000, '000', ['created', '00', 'Transaction request sent']);

        $add('collect', S::TR301, '000', ['created', null, null]);

        $errorCode = ErrorCode::BAD_REQUEST_PAYMENT_UPI_COLLECT_REQUEST_EXPIRED;
        $add('collect', S::TR302, 'U69', ['expired', 'U69', $errorCode]);

        $errorCode = ErrorCode::BAD_REQUEST_PAYMENT_UPI_COLLECT_REQUEST_REJECTED;
        $add('collect', S::TR302, '0ZA', ['rejected', 'ZA', $errorCode]);

        $add('collect', S::TR302, 'U18', ['failed', 'U18', ErrorCode::GATEWAY_ERROR_UPI_REQAUTH_TIMEOUT]);

        // Reject
        $errorCode = ErrorCode::BAD_REQUEST_PAYMENT_UPI_COLLECT_REQUEST_REJECTED;
        $add('reject', S::N0000, '000', ['rejected', 'ZA', $errorCode]);

        $add('reject', S::TR301, '000', ['requested', null, null]);

        // Approve
        $add('approve', S::N0000, '000', ['completed', '00', 'Transaction is completed']);

        $add('approve', S::TR302, '100', ['failed', '100', ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE]);

        return $add(null);
    }

    /**
     * @dataProvider authorizeTransaction
     */
    public function testAuthorizeTransaction($type, $scenario, $sub, $expected)
    {
        $helper = $this->getTransactionHelper();

        if ($type === 'pay')
        {
            $request = $helper->initiatePay();
            $content = ['sdk' => $this->handleNpciClRequest($request, 'getCredential')()];
        }
        else if ($type === 'collect')
        {
            $request = $helper->initiateCollect();
            $content = [];
        }
        else if ($type === 'reject')
        {
            $transaction = $this->createCollectIncomingTransaction();
            $request = $helper->initiateReject($transaction->getPublicId());
            $content = [];
        }
        else
        {
            $transaction = $this->createCollectIncomingTransaction();
            $request = $helper->initiateAuthorize($transaction->getPublicId());
            $content = [];
        }

        $helper->withSchemaValidated();
        $helper->setScenarioInContext($scenario, $sub);

        $response = $helper->authorizeTransaction($request['callback'], $content);

        $transaction = $this->getDbLastTransaction();

        if ($helper->getScenarioInContext()->isSuccess() === false)
        {
            // Actual response if error block which has been test in Base/Scenario
            $response = $transaction->toArrayPublic();
        }

        // TODO: We need to set internal_error_code in transaction on failure
        $this->assertArraySubset([
            'status'    => $expected[0],
            'upi'       => [
                'gateway_error_code'            => $expected[1],
                'gateway_error_description'     => $expected[2],
            ],
        ], $response);
    }

    public function testFetchAll()
    {
        $helper = $this->getTransactionHelper();

        $helper->initiatePay();

        $helper->withSchemaValidated();

        $helper->fetchAll();
    }

    public function testFetch()
    {
        $helper = $this->getTransactionHelper();

        $helper->initiatePay();

        $transaction = $this->fixtures->getDbLastTransaction();

        $helper->withSchemaValidated();

        $helper->fetch($transaction->getPublicId());
    }

    public function raiseConcern()
    {
        $add = $this->addScenario(0, 1, 3, 4);

        $add(S::N0000, '000', ['initiated', 'pending', 'GATEWAY_ERROR_TRANSACTION_PENDING'],
             S::N0000, '000', ['closed', 'success', 'Transaction is completed']);

        $add(S::N0000, '000', ['initiated', 'pending', 'GATEWAY_ERROR_TRANSACTION_PENDING'],
             S::TR601, '000', ['closed', 'success', 'Transaction is completed']);

        $add(S::N0000, '000', ['initiated', 'pending', 'GATEWAY_ERROR_TRANSACTION_PENDING'],
             S::TR602, '000', ['closed', 'failed', 'GATEWAY_ERROR_INVALID_RESPONSE']);

        $add(S::N0000, '000', ['initiated', 'pending', 'GATEWAY_ERROR_TRANSACTION_PENDING'],
             S::TR602, 'U69', ['closed', 'expired', 'BAD_REQUEST_PAYMENT_UPI_COLLECT_REQUEST_EXPIRED']);

        $add(S::TR501, '000', ['pending', 'pending', 'GATEWAY_ERROR_TRANSACTION_PENDING']);

        $add(S::TR502, '001', ['initiated', 'pending', 'GATEWAY_ERROR_INVALID_RESPONSE']);

        $add(S::TR502, '0ZA', ['closed', 'rejected', 'BAD_REQUEST_PAYMENT_UPI_COLLECT_REQUEST_REJECTED']);

        return $add();
    }

    /**
     * @dataProvider raiseConcern
     */
    public function testRaiseConcern($scenario, $sub, $expected, $scenario2 = null, $sub2 = null, $expected2 = [])
    {
        $helper = $this->getTransactionHelper();

        $transaction = $this->createFailedPayTransaction();

        $helper->withSchemaValidated();

        $helper->setScenarioInContext($scenario, $sub);

        $response = $helper->raiseConcern($transaction->getPublicId());

        if ($helper->getScenarioInContext()->isSuccess() === false)
        {
            return;
        }

        $concern = $transaction->concerns->first();

        $this->assertArraySubset([
            'gateway_reference_id'  => 'SharpQuery' . $concern->getId(),
            'status'                => $expected[0],
            'response_code'         => $expected[1],
            'response_description'  => $expected[2],
        ], $response, true, 'Raise concern failed');

        if (empty($scenario2))
        {
            return;
        }

        $helper->setScenarioInContext($scenario2, $sub2);

        $response = $helper->concernStatus($transaction->getPublicId());

        if ($helper->getScenarioInContext()->isSuccess() === false)
        {
            return;
        }

        $concern->refresh();

        $this->assertArraySubset([
            'gateway_reference_id'  => 'SharpQuery' . $concern->getId(),
            'status'                => $expected2[0],
            'response_code'         => $expected2[1],
            'response_description'  => $expected2[2],
        ], $response, true, 'Concern status failed');
    }

    private function assertTransactionVector(Transaction\Entity $transaction, array $vector)
    {
        $payer          = $transaction->payer;
        $payee          = $transaction->payee;
        $bankAccount    = $transaction->bankAccount;
        $upi            = $transaction->upi;

        $this->assertCount(8, $vector);
        $this->assertSame('NPCI', $vector[0]);
        $this->assertSame('<xml></xml>', $vector[1]);
        $this->assertArrayHasKey('CredAllowed', $vector[2]);
        $this->assertCount(2, $vector[3]);
        $this->assertTrue(is_string($vector[5]));
        $this->assertCount(5, $vector[6]);
        $this->assertSame('en_US', $vector[7]);

        $this->assertArraySubset([
            'txnId'         => $upi->getNetworkTransactionId(),
            'txnAmount'     => amount_format_IN($transaction->getAmount()),
            'deviceId'      => $this->fixtures->device->getUuid(),
            'appId'         => $this->fixtures->device->getAppName(),
            'mobileNumber'  => substr($this->fixtures->device->getContact(), -10),
            'payerAddr'     => $payer->getAddress(),
            'payeeAddr'     => $payee->getAddress(),
        ], $vector[4], true);

        $values = [
            [
                'name'  => 'payeeName',
                'value' => $payee->getBeneficiaryName(),
            ],
            [
                'name'  => 'note',
                'value' => $transaction->getDescription(),
            ],
            [
                'name'  => 'refId',
                'value' => $upi->getRefId(),
            ],
            [
                'name'  => 'refUrl',
                'value' => $upi->getRefUrl(),
            ],
            [
                'name'  => 'account',
                'value' => $bankAccount->getMaskedAccountNumber(),
            ],
        ];

        $this->assertArraySubset($values, $vector[6], true);
    }

    private function addScenario()
    {
        $this->scenarioKeysToMessage = func_get_args();
        $this->scenarioAllCasesList   = [];

        return function(...$input)
        {
            if (empty($input[0]))
            {
                return $this->scenarioAllCasesList;
            }

            $message = 'Scenario#' . implode('#', array_only($input, $this->scenarioKeysToMessage));
            $this->scenarioAllCasesList[$message] = $input;
        };
    }
}
