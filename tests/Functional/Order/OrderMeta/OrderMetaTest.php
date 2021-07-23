<?php

namespace RZP\Tests\Functional\Order\OrderMeta;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Exception\BadRequestValidationFailureException;

/**
 * Class OrderMetaTest
 *
 * @package Functional\Order\OrderMeta
 */
class OrderMetaTest extends TestCase
{
    use RequestResponseFlowTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ba->privateAuth();
    }

    public function testOrderMetaWithTaxInvoice()
    {
        $request = $this->getOrderMetaArrayWithTaxInvoice();

        $response = $this->startOrderMetaFlow($request);

        $orderMeta = $this->getDbLastOrderMeta();

        $order = $this->getDbLastOrder();

        $this->assertCount(1, ($order->orderMetas), 'Number of order meta for same order should be 1');

        $this->assertSame($order->getId(), $orderMeta->getOrderId(), 'Ids should be same');

        $this->assertEquals('tax_invoice', $orderMeta->getType());

        $this->assertSame($response['tax_invoice'], $orderMeta->getValue());
    }

    public function testOrderMetaWithoutMandatoryGstFields()
    {
        $baseRequest = $this->getOrderMetaArrayWithTaxInvoice(
            [
                'method'      => 'upi',
                'tax_invoice' => [
                    'business_gstin' => '123456789012345',
                    'supply_type'    => 'interstate',
                    'cess_amount'    => 12500,
                ]
            ]
        );

        $response = $this->startOrderMetaFlow($baseRequest);

        //Since flow is not gst, then orderMeta entity should not be created and,
        //response should not have invoice block.
        $this->assertArrayNotHasKey('tax_invoice', $response);

        $this->assertNull($this->getDbLastEntity('order_meta'));
    }

    public function testOrderMetaWithTaxInvoiceOddValues()
    {
        $request = $this->getOrderMetaArrayWithTaxInvoice();

        $request['tax_invoice']['gst_amount'] = 15;

        $response = $this->startOrderMetaFlow($request);

        $orderMeta = $this->getDbLastOrderMeta();

        $order = $this->getDbLastOrder();

        $this->assertCount(1, ($order->orderMetas), 'Number of order meta for same order should be 1');

        $this->assertSame($response['tax_invoice'], $orderMeta->getValue());
    }

    public function testOrderMetaWithUnsupportedMethod()
    {
        $baseRequest = $this->getOrderMetaArrayWithTaxInvoice(
            [
                'method'      => 'netbanking',
                'tax_invoice' => [
                    'business_gstin' => '123456789012345',
                    'gst_amount'     => 10000,
                    'supply_type'    => 'intrastate',
                    'cess_amount'    => 12500,
                    'customer_name'  => 'Gaurav',
                    'number'         => '1234',
                    'date'           => 1626286666,
                ]
            ]
        );

        $response = $this->startOrderMetaFlow($baseRequest);

        $this->assertArrayNotHasKey('tax_invoice', $response);

        $orderMeta = $this->getDbLastEntity('order_meta');

        $this->assertNull($orderMeta);
    }

    /*********************** Tax Invoice Validation Test cases *************************/

    /**
     * @dataProvider functionValidateOrderMetaTaxInvoiceCreate
     *
     * @param array $request
     * @param null  $expectionClass
     * @param null  $exceptionMessage
     */
    public function testValidateOrderMetaTaxInvoiceCreate(array $request, $expectionClass = null, $exceptionMessage = null)
    {
        $this->makeRequestAndCatchException(
            function() use ($request)
            {
                $this->startOrderMetaFlow($request);
            },
            $expectionClass,
            $exceptionMessage
        );

        $orderMeta = $this->getDbLastEntity('order_meta');

        $this->assertNull($orderMeta);
    }

    public function functionValidateOrderMetaTaxInvoiceCreate()
    {
        // Missing GST Amount
        $baseRequest = $this->getOrderMetaArrayWithTaxInvoice();
        unset($baseRequest['tax_invoice']['gst_amount']);
        $cases['missing_gst_amount'] = [
            $baseRequest,
            BadRequestValidationFailureException::class,
            'The gst amount field is required when supply type is present.',
        ];

        // Missing Cess Amount
        $baseRequest = $this->getOrderMetaArrayWithTaxInvoice();
        unset($baseRequest['tax_invoice']['cess_amount']);
        $cases['missing_cess_amount'] = [
            $baseRequest,
            BadRequestValidationFailureException::class,
            'The cess amount field is required when supply type is present.',
        ];

        // Invalid GSTIN length
        $baseRequest = $this->getOrderMetaArrayWithTaxInvoice(
            [
                'tax_invoice' => [
                    'business_gstin' => '1234345',
                    'customer_name'  => 'Gaurav',
                    'number'         => '1234',
                ]
            ]
        );
        $cases['invalid_gstin_length'] =
            [
                $baseRequest,
                BadRequestValidationFailureException::class,
                'The business gstin must be 15 characters.',
            ];


        // Invalid Supply Type
        $baseRequest = $this->getOrderMetaArrayWithTaxInvoice(
            [
                'tax_invoice' => [
                    'business_gstin' => '123456789012345',
                    'customer_name'  => 'Gaurav',
                    'number'         => '1234',
                    'supply_type'    => 'random_supply_type',
                ],
            ]
        );
        $cases['invalid_supply_type'] = [
            $baseRequest,
            BadRequestValidationFailureException::class,
            'random_supply_type is not a valid supply type',
        ];

        // Negative Cess Amount
        $baseRequest = $this->getOrderMetaArrayWithTaxInvoice(
            [
                'tax_invoice' => [
                    'business_gstin' => '123456789012345',
                    'customer_name'  => 'Gaurav',
                    'number'         => '1234',
                    'supply_type'    => 'intrastate',
                    'cess_amount'    => -1000,
                ],
            ]
        );
        $cases['negative_cess_amount'] = [
            $baseRequest,
            BadRequestValidationFailureException::class,
            'The cess amount must be at least 0.',
        ];

        // Negative GST Amount
        $baseRequest = $this->getOrderMetaArrayWithTaxInvoice(
            [
                'tax_invoice' => [
                    'business_gstin' => '123456789012345',
                    'customer_name'  => 'Gaurav',
                    'number'         => '1234',
                    'supply_type'    => 'intrastate',
                    'gst_amount'     => -1000,
                ],
            ]
        );
        $cases['negative_cess_amount'] = [
            $baseRequest,
            BadRequestValidationFailureException::class,
            'The gst amount must be at least 0.',
        ];

        // Invalid date - float input
        $baseRequest = $this->getOrderMetaArrayWithTaxInvoice(
            [
                'tax_invoice' => [
                    'business_gstin' => '123456789012345',
                    'customer_name'  => 'Gaurav',
                    'number'         => '1234',
                    'supply_type'    => 'intrastate',
                    'gst_amount'     => 1000,
                    'date'           => 12.56,
                ],
            ]
        );
        $cases['invalid_invoice_date - float_input'] = [
            $baseRequest,
            BadRequestValidationFailureException::class,
            'date must be an integer.',
        ];

        // Invalid date - out of range
        $baseRequest = $this->getOrderMetaArrayWithTaxInvoice(
            [
                'tax_invoice' => [
                    'business_gstin' => '123456789012345',
                    'customer_name'  => 'Gaurav',
                    'number'         => '1234',
                    'supply_type'    => 'intrastate',
                    'gst_amount'     => 1000,
                    'date'           => 626286666,
                ],
            ]
        );
        $cases['invalid_invoice_date - outside_allowed_range'] = [
            $baseRequest,
            BadRequestValidationFailureException::class,
            'date must be between 946684800 and 4765046400',
        ];

        //CESS Amount & GST Amount passed, Supply Type missing
        $baseRequest = $this->getOrderMetaArrayWithTaxInvoice(
            [
                'tax_invoice' => [
                    'business_gstin' => '123456789012345',
                    'gst_amount'     => 10000,
                    'cess_amount'    => 12500,
                    'customer_name'  => 'Gaurav',
                    'number'         => '1234',
                    'date'           => 1626286666,
                ]
            ]
        );
        $cases['supply_type_absent'] = [
            $baseRequest,
            BadRequestValidationFailureException::class,
            'The supply type field is required.',
        ];

        //GST Amount & Supply Type passed, CESS Amount missing
        $baseRequest = $this->getOrderMetaArrayWithTaxInvoice(
            [
                'tax_invoice' => [
                    'business_gstin' => '123456789012345',
                    'gst_amount'     => 12500,
                    'supply_type'    => 'interstate',
                    'customer_name'  => 'Gaurav',
                    'number'         => '1234',
                    'date'           => 1626286666,
                ]
            ]
        );
        $cases['supply_type_present_cess_amount_missing'] = [
            $baseRequest,
            BadRequestValidationFailureException::class,
            'The cess amount field is required when supply type is present.',
        ];

        //CESS Amount & Supply Type passed, GST Amount missing
        $baseRequest = $this->getOrderMetaArrayWithTaxInvoice(
            [
                'tax_invoice' => [
                    'business_gstin' => '123456789012345',
                    'cess_amount'    => 12500,
                    'supply_type'    => 'interstate',
                    'customer_name'  => 'Gaurav',
                    'number'         => '1234',
                    'date'           => 1626286666,
                ]
            ]
        );
        $cases['supply_type_present_gst_amount_missing'] = [
            $baseRequest,
            BadRequestValidationFailureException::class,
            'The gst amount field is required when supply type is present.',
        ];

        return $cases;
    }

    protected function startOrderMetaFlow(array $payload)
    {
        $this->ba->privateAuth();

        $request = [
            'content' => $payload,
            'method'  => 'POST',
            'url'     => '/orders',
        ];

        return $this->makeRequestAndGetContent($request);
    }

    private function getOrderMetaArrayWithTaxInvoice($overrideWith = []) : array
    {
        $taxInvoice = [
            'amount'      => 50000,
            'currency'    => 'INR',
            'receipt'     => 'rcptid42',
            'method'      => 'upi',
            'tax_invoice' => [
                'business_gstin' => '123456789012345',
                'gst_amount'     => 3000,
                'supply_type'    => 'intrastate',
                'cess_amount'    => 1000,
                'customer_name'  => 'Gaurav',
                'number'         => '1234',
                'date'           => 1626286666,
            ]
        ];

        return array_merge($taxInvoice, $overrideWith);
    }
}
