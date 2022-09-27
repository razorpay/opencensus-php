<?php

namespace RZP\Tests\Functional\CreditNote;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Invoice\InvoiceTestTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class CreditNoteTest extends TestCase
{
    use PaymentTrait;

    use InvoiceTestTrait;

    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/Helpers/CreditNoteTestData.php';

        parent::setUp();

        $this->ba->proxyAuth();
    }

    public function testCreateCreditNote()
    {
        $this->startTest();
    }

    public function testCreateCreditNoteWithoutCustomer()
    {
        $this->startTest();
    }

    public function testApplyCreditNoteWithSingleInvoice()
    {
        $this->testCreateCreditNote();

        $creditNote = $this->getLastEntity('creditnote', true);

        $order = $this->createOrder();

        $invoice = $this->createIssuedInvoice();

        $this->makePaymentForInvoiceAndAssert($invoice->toArrayPublic());

        $this->ba->proxyAuth();

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/creditnote/'.$creditNote['id'].'/apply';

        $testData['request']['content']['invoices'][] = ['invoice_id' => $invoice->getPublicId(), 'amount' => 1000];

        // Use this flag to test with the new refund flow, which now entirely happens on Scrooge.
        // This will only assert what is necessary.
        // This flow is not live yet on prod for this flow, so disabling flag for the time being.
        $flag = false;

        if ($flag === true)
        {
            $this->enableRazorXTreatmentForRefundV2();
        }

        $this->startTest();

        if ($flag === true)
        {
            $this->assertPassportKeyExists('consumer.id');
            $this->assertNull($this->getDbLastEntity('refund')); // making sure refunds are not created in api db in new refund flow
        }
    }

    public function testApplyCreditNoteWithSingleInvoiceWithoutCustomer()
    {
        $this->testCreateCreditNoteWithoutCustomer();

        $creditNote = $this->getLastEntity('creditnote', true);

        $order = $this->createOrder();

        $invoice = $this->createIssuedInvoice(['customer_id' => null]);

        $this->makePaymentForInvoiceAndAssert($invoice->toArrayPublic());

        $this->ba->proxyAuth();

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/creditnote/'.$creditNote['id'].'/apply';

        $testData['request']['content']['invoices'][] = ['invoice_id' => $invoice->getPublicId(), 'amount' => 1000];

        // Use this flag to test with the new refund flow, which now entirely happens on Scrooge.
        // This will only assert what is necessary.
        // This flow is not live yet on prod for this flow, so disabling flag for the time being.
        $flag = false;

        if ($flag === true)
        {
            $this->enableRazorXTreatmentForRefundV2();
        }

        $this->startTest();
    }

    public function testApplyCreditNoteWithSingleInvoiceAndFullAmount()
    {
        $testDataCreate = &$this->testData['testCreateCreditNote'];

        $testDataCreate['request']['content']['amount'] = 1000;

        $testDataCreate['response']['content']['amount'] = '1000';

        $testDataCreate['response']['content']['amount_available'] = '1000';

        $this->testCreateCreditNote();

        $creditNote = $this->getLastEntity('creditnote', true);

        $order = $this->createOrder();

        $invoice = $this->createIssuedInvoice();

        $this->makePaymentForInvoiceAndAssert($invoice->toArrayPublic());

        $this->ba->proxyAuth();

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/creditnote/'.$creditNote['id'].'/apply';

        $testData['request']['content']['invoices'][] = ['invoice_id' => $invoice->getPublicId(), 'amount' => 1000];

        // Use this flag to test with the new refund flow, which now entirely happens on Scrooge.
        // This will only assert what is necessary.
        // This flow is not live yet on prod for this flow, so disabling flag for the time being.
        $flag = false;

        if ($flag === true)
        {
            $this->enableRazorXTreatmentForRefundV2();
        }

        $this->startTest();
    }

    public function testApplyCreditNoteWithSingleInvoiceAndFullAmountWithoutCustomer()
    {
        $testDataCreate = &$this->testData['testCreateCreditNoteWithoutCustomer'];

        $testDataCreate['request']['content']['amount'] = 1000;

        $testDataCreate['response']['content']['amount'] = '1000';

        $testDataCreate['response']['content']['amount_available'] = '1000';

        $this->testCreateCreditNoteWithoutCustomer();

        $creditNote = $this->getLastEntity('creditnote', true);

        $order = $this->createOrder();

        $invoice = $this->createIssuedInvoice(['customer_id' => null]);

        $this->makePaymentForInvoiceAndAssert($invoice->toArrayPublic());

        $this->ba->proxyAuth();

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/creditnote/'.$creditNote['id'].'/apply';

        $testData['request']['content']['invoices'][] = ['invoice_id' => $invoice->getPublicId(), 'amount' => 1000];

        // Use this flag to test with the new refund flow, which now entirely happens on Scrooge.
        // This will only assert what is necessary.
        // This flow is not live yet on prod for this flow, so disabling flag for the time being.
        $flag = false;

        if ($flag === true)
        {
            $this->enableRazorXTreatmentForRefundV2();
        }

        $this->startTest();
    }

    protected function createOrder(array $overrideWith = [])
    {
        $order = $this->fixtures
            ->create(
                'order',
                array_merge(
                    [
                        'id'              => '100000000order',
                        'amount'          => 100000,
                        'payment_capture' => true,
                    ],
                    $overrideWith
                )
            );

        return $order;
    }

}
