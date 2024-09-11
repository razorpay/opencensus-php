<?php

namespace RZP\Services\UpiPayment;

use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Store\Entity;
use Razorpay\Trace\Logger as Trace;

class Core extends Service
{
    public function __construct()
    {
        parent::__construct();
    }

    /** processes the actions sent from UPS
     * @param $input
     * @param $action
     * @return array
     * @throws Exception\BadRequestException
     */
    public function processActions($input, $action)
    {
        switch ($action)
        {
            case Constants::UnexpectedPreProcess:
                return $this->unexpectedPreProcess($input);

            default:
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_ACTION_NOT_ALLOWED,
                    null,
                    [
                        'action' => $action,
                    ]
                );
        }
    }

    /** unexpectedPreprocess returns the payment type
     * @param $input
     * @return array
     */
    private function unexpectedPreprocess($input)
    {
        (new Validator())->validateInput(Validator::UNEXPECTED_PREPROCESS, $input);

        $paymentId = $input['upi']['merchant_reference'] ?? '';

        $gateway = $input['gateway'];

        $this->trace->info(
            TraceCode::UPI_UNEXPECTED_PREPROCESS_REQUEST,
            [
                'input'             => $input,
                'gateway'           => $gateway,
                'npci_reference_id' => $input['upi']['npci_reference_id'],
            ]);

        [$payment, $mode] = $this->app['repo']->payment
            ->fetchPaymentLiveOrTestModeWithGateway($paymentId, $gateway);

        if ($mode !== null)
        {
            return [
                Constants::DATA => [
                    Constants::TYPE => Constants::API,
                ]
            ];
        }

        [$qrCode, $mode] = (new \RZP\Models\QrPayment\Service())->findQrCodeForQrPaymentRearch($input, $gateway);

        if (empty($mode) === false)
        {
            return [
                Constants::DATA => [
                    Constants::TYPE => Constants::QR,
                ]
            ];
        }

       $response = $this->upiEntityFetch($input['upi'], $gateway);

        if ($response['success'] === true)
        {
            return [
                Constants::DATA => [
                    Constants::TYPE     => Constants::API_UNEXPECTED,
                    Constants::RESPONSE      => $response['entities'],
                ]
            ];
        }

        // return unexpected if not found API, QR
        return [
            Constants::DATA => [
                Constants::TYPE => Constants::UNEXPECTED,
            ]
        ];
    }

    /** upiEntityFetch returns the upi entity
     * @param $input
     * @return array
     */
    private function upiEntityFetch($input, $gateway)
    {
        $merchant_reference = $input['merchant_reference'] ?? '';

        $npciReferenceId = $input['npci_reference_id'] ?? '';

        $upiEntity = null;

        try
        {
            // This checks if merchant_reference can be used for fetching unexpected payments
            if (($this->shouldUseMerchantReferenceForUnexpectedPayment($gateway) === true) and
                (empty($merchant_reference) === false))
            {
                $upiEntity = $this->app['repo']->upi->fetchAllByMerchantReferenceAndNpciReferenceIdAndGateway($merchant_reference, $npciReferenceId, $gateway);
            }
            else
            {
                $upiEntity = $this->app['repo']->upi->findAllByNpciReferenceIdAndGateway($npciReferenceId, $gateway);
            }
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::UPI_UNEXPECTED_ENTITY_FETCH_FAILED,
                [
                    'npci_reference_id'         => $npciReferenceId,
                    'gateway'                   => $gateway,
                ]
            );
            throw $ex;
        }

        if ((empty($upiEntity) === false) and
            $upiEntity->count() > 0)
        {
            return [
                "entities"  => $upiEntity->toArray(),
                "count"     => $upiEntity->count(),
                "success"   => true,
            ];
        }

        return [
            "success"   => false,
        ];
    }

    /**
     * Checks if gateway is enabled for using merchant reference
     * for identifying unexpected payments
     * @param string $gateway
     * @return bool
     */
    private function shouldUseMerchantReferenceForUnexpectedPayment(string $gateway)
    {
        $variant = $this->app->razorx->getTreatment($gateway,
        Merchant\RazorxTreatment::USE_MERCHANT_REFERENCE_FOR_UNEXPECTED_PAYMENT, Mode::LIVE);

        $this->trace->info(
            TraceCode::UPI_UNEXPECTED_PAYMENT_IDENTIFIER_RAZORX_VARIANT,
            [
                'gateway'           => $gateway,
                'variant'           => $variant
            ]
        );

        if (strtolower($variant) === 'on')
        {
            return true;
        }

        return false;
    }
}
