<?php

namespace RZP\Tests\P2p\Service\Base;

use RZP\Models\P2p;
use RZP\Error\Error;
use RZP\Gateway\P2p\Upi\Mock;
use PHPUnit\Framework\Assert;
use RZP\Error\PublicErrorCode as Code;
use Illuminate\Testing\TestResponse;
use RZP\Error\P2p\PublicErrorDescription as Description;
use DMS\PHPUnitExtensions\ArraySubset\Assert as ArraySubsetAssert;

class Scenario extends Mock\Scenario
{
    const ERROR     = 'error';

    public function getScenarioCallback(): callable
    {
        $expected = $this->getScenarionCallbackMap();

        if ($this->isSuccess() === false)
        {
            Assert::assertArrayHasKey(self::ERROR, $expected, 'Error key must be defined:' . $this->getId());
        }
        else
        {
            Assert::assertArrayNotHasKey(self::ERROR, $expected, 'Error key must not be there:' . $this->getId());
        }

        $wrapper = function(TestResponse $response) use ($expected)
        {
            ArraySubsetAssert::assertArraySubset($expected, $response->json(), true, $this->getId());
        };

        return $wrapper;
    }

    public function getScenarionCallbackMap()
    {
        $map = [
            self::N0000 => [
            ],
            self::CL301 => [
            ],
            self::CL302 => [
            ],
            self::CL303 => [
            ],
            self::DE101 => [
                self::ERROR => [
                    Error::PUBLIC_ERROR_CODE    => Code::BAD_REQUEST_ERROR,
                    Error::DESCRIPTION          => Description::BAD_REQUEST_SMS_FAILED,
                ],
            ],
            self::DE102 => [
            ],
            self::DE201 => [
                self::ERROR => [
                    Error::PUBLIC_ERROR_CODE    => Code::BAD_REQUEST_ERROR,
                    Error::DESCRIPTION          => Description::BAD_REQUEST_DEVICE_NOT_ATTACHED_TO_HANDLE,
                ],
            ],
            self::DE202 => [
            ],
            self::DE203 => [
            ],
            self::DE301 => [
                self::ERROR => [
                    Error::PUBLIC_ERROR_CODE    => Code::BAD_REQUEST_ERROR,
                    Error::DESCRIPTION          => Description::BAD_REQUEST_DEVICE_NOT_ATTACHED_TO_HANDLE,
                ],
            ],
            self::DE302 => [
            ],
            self::DE303 => [
            ],
            self::DE304 => [
                self::ERROR => [
                    Error::PUBLIC_ERROR_CODE    => Code::GATEWAY_ERROR,
                    Error::DESCRIPTION          => Description::GATEWAY_ERROR,
                ],
            ],
            self::DE305 => [
                self::ERROR => [
                    Error::PUBLIC_ERROR_CODE    => Code::GATEWAY_ERROR,
                    Error::DESCRIPTION          => Description::GATEWAY_ERROR,
                ],
            ],
            self::DE306 => [
                self::ERROR => [
                    Error::PUBLIC_ERROR_CODE    => Code::GATEWAY_ERROR,
                    Error::DESCRIPTION          => Description::GATEWAY_ERROR,
                ],
            ],
            self::DE401 => [
                self::ERROR => [
                    Error::PUBLIC_ERROR_CODE    => Code::GATEWAY_ERROR,
                    Error::DESCRIPTION          => Description::GATEWAY_ERROR_TECHNICAL_ERROR,
                ],
            ],
            self::BA101 => [
            ],
            self::BA102 => [
                self::ERROR => [
                    Error::PUBLIC_ERROR_CODE    => Code::GATEWAY_ERROR,
                    Error::DESCRIPTION          => Description::GATEWAY_ERROR_TECHNICAL_ERROR,
                ],
            ],
            self::BA201 => [
                self::ERROR => [
                    Error::PUBLIC_ERROR_CODE    => Code::GATEWAY_ERROR,
                    Error::DESCRIPTION          => Description::GATEWAY_ERROR_REQUEST_TIMEOUT,
                ],
            ],
            self::BA202 => [
                self::ERROR => [
                    Error::PUBLIC_ERROR_CODE    => Code::GATEWAY_ERROR,
                    Error::DESCRIPTION          => Description::GATEWAY_ERROR_TECHNICAL_ERROR,
                ],
            ],
            self::BA203 => [
                self::ERROR => [
                    Error::PUBLIC_ERROR_CODE    => Code::BAD_REQUEST_ERROR,
                    Error::DESCRIPTION          => Description::BAD_REQUEST_NO_BANK_ACCOUNT_FOUND,
                    Error::ACTION               => P2p\BankAccount\Action::INITIATE_RETRIEVE,
                ],
            ],
            self::BA204 => [
            ],
            self::BA205 => [
            ],
            self::BA301 => [
                self::ERROR => [
                    Error::PUBLIC_ERROR_CODE    => Code::GATEWAY_ERROR,
                    Error::DESCRIPTION          => Description::GATEWAY_ERROR_REQUEST_TIMEOUT,
                ],
            ],
            self::BA302 => [
                self::ERROR => [
                    Error::PUBLIC_ERROR_CODE    => Code::BAD_REQUEST_ERROR,
                    Error::DESCRIPTION          => Description::BAD_REQUEST_INVALID_CARD_DETAILS,
                ],
            ],
            self::BA303 => [
                self::ERROR => [
                    Error::PUBLIC_ERROR_CODE    => Code::BAD_REQUEST_ERROR,
                    Error::DESCRIPTION          => Description::BAD_REQUEST_SMS_OTP_FAILED,
                ],
            ],
            self::BA304 => [
                self::ERROR => [
                    Error::PUBLIC_ERROR_CODE    => Code::BAD_REQUEST_ERROR,
                    Error::DESCRIPTION          => Description::BAD_REQUEST_PAYMENT_PIN_INCORRECT,
                ],
            ],
            self::BA305 => [
                self::ERROR => [
                    Error::PUBLIC_ERROR_CODE    => Code::BAD_REQUEST_ERROR,
                    Error::DESCRIPTION          => Description::BAD_REQUEST_DEVICE_NOT_ATTACHED_TO_HANDLE,
                    Error::ACTION               => P2p\Device\Action::INITIATE_VERIFICATION,
                ],
            ],
            self::BA401 => [
                self::ERROR => [
                    Error::PUBLIC_ERROR_CODE    => Code::GATEWAY_ERROR,
                    Error::DESCRIPTION          => Description::GATEWAY_ERROR_REQUEST_TIMEOUT,
                ],
            ],
            self::BA402 => [
                self::ERROR => [
                    Error::PUBLIC_ERROR_CODE    => Code::BAD_REQUEST_ERROR,
                    Error::DESCRIPTION          => Description::BAD_REQUEST_PAYMENT_PIN_INCORRECT,
                ],
            ],
            self::BA403 => [
                self::ERROR => [
                    Error::PUBLIC_ERROR_CODE    => Code::BAD_REQUEST_ERROR,
                    Error::DESCRIPTION          => Description::BAD_REQUEST_DEVICE_NOT_ATTACHED_TO_HANDLE,
                    Error::ACTION               => P2p\Device\Action::INITIATE_VERIFICATION,
                ],
            ],
            self::BA404 => [
            ],
            self::BB101 => [
                self::ERROR => [
                    Error::PUBLIC_ERROR_CODE    => Code::GATEWAY_ERROR,
                    Error::DESCRIPTION          => Description::GATEWAY_ERROR_REQUEST_TIMEOUT,
                ],
            ],
            self::BB102 => [
                self::ERROR => [
                    Error::PUBLIC_ERROR_CODE    => Code::GATEWAY_ERROR,
                    Error::DESCRIPTION          => Description::GATEWAY_ERROR_TECHNICAL_ERROR,
                ],
            ],
            self::BB103 => [

            ],
            self::BB104 => [

            ],
            self::VA101 => [

            ],
            self::VA201 => [
                self::ERROR => [
                    Error::PUBLIC_ERROR_CODE    => Code::GATEWAY_ERROR,
                    Error::DESCRIPTION          => Description::GATEWAY_ERROR_REQUEST_TIMEOUT,
                ],
            ],
            self::VA202 => [
                self::ERROR => [
                    Error::PUBLIC_ERROR_CODE    => Code::GATEWAY_ERROR,
                    Error::DESCRIPTION          => Description::GATEWAY_ERROR_TECHNICAL_ERROR,
                ],
            ],
            self::VA203 => [
                self::ERROR => [
                    Error::PUBLIC_ERROR_CODE    => Code::BAD_REQUEST_ERROR,
                    Error::DESCRIPTION          => Description::BAD_REQUEST_VPA_NOT_AVAILABLE,
                    Error::ACTION               => P2p\Vpa\Action::INITIATE_CHECK_AVAILABILITY,
                ],
            ],
            self::VA204 => [
                self::ERROR => [
                    Error::PUBLIC_ERROR_CODE    => Code::BAD_REQUEST_ERROR,
                    Error::DESCRIPTION          => Description::BAD_REQUEST_VPA_NOT_AVAILABLE,
                    Error::ACTION               => P2p\Vpa\Action::INITIATE_CHECK_AVAILABILITY,
                ],
            ],
            self::VA205 => [
                self::ERROR => [
                    Error::PUBLIC_ERROR_CODE    => Code::BAD_REQUEST_ERROR,
                    Error::DESCRIPTION          => Description::BAD_REQUEST_MAX_VPA_LIMIT_REACHED,
                ],
            ],
            self::VA301 => [
                self::ERROR => [
                    Error::PUBLIC_ERROR_CODE    => Code::GATEWAY_ERROR,
                    Error::DESCRIPTION          => Description::GATEWAY_ERROR_REQUEST_TIMEOUT,
                ],
            ],
            self::VA302 => [
                self::ERROR => [
                    Error::PUBLIC_ERROR_CODE    => Code::GATEWAY_ERROR,
                    Error::DESCRIPTION          => Description::GATEWAY_ERROR_TECHNICAL_ERROR,
                ],
            ],
            self::VA303 => [
                self::ERROR => [
                    Error::PUBLIC_ERROR_CODE    => Code::BAD_REQUEST_ERROR,
                    Error::DESCRIPTION          => Description::BAD_REQUEST_VPA_NOT_AVAILABLE,
                    Error::ACTION               => P2p\Vpa\Action::INITIATE_CHECK_AVAILABILITY,
                ],
            ],
            self::VA304 => [
                self::ERROR => [
                    Error::PUBLIC_ERROR_CODE    => Code::BAD_REQUEST_ERROR,
                    Error::DESCRIPTION          => Description::BAD_REQUEST_VPA_NOT_AVAILABLE,
                    Error::ACTION               => P2p\Vpa\Action::INITIATE_CHECK_AVAILABILITY,
                ],
            ],
            self::VA305 => [
                self::ERROR => [
                    Error::PUBLIC_ERROR_CODE    => Code::BAD_REQUEST_ERROR,
                    Error::DESCRIPTION          => Description::BAD_REQUEST_MAX_VPA_LIMIT_REACHED,
                ],
            ],
            self::VA401 => [
                self::ERROR => [
                    Error::PUBLIC_ERROR_CODE    => Code::GATEWAY_ERROR,
                    Error::DESCRIPTION          => Description::GATEWAY_ERROR_REQUEST_TIMEOUT,
                ],
            ],
            self::VA501 => [
                self::ERROR => [
                    Error::PUBLIC_ERROR_CODE    => Code::GATEWAY_ERROR,
                    Error::DESCRIPTION          => Description::GATEWAY_ERROR_REQUEST_TIMEOUT,
                ],
            ],
            self::VA601 => [
                self::ERROR => [
                    Error::PUBLIC_ERROR_CODE    => Code::GATEWAY_ERROR,
                    Error::DESCRIPTION          => Description::GATEWAY_ERROR_REQUEST_TIMEOUT,
                ],
            ],
            self::VA701 => [
                self::ERROR => [
                    Error::PUBLIC_ERROR_CODE    => Code::GATEWAY_ERROR,
                    Error::DESCRIPTION          => Description::GATEWAY_ERROR_REQUEST_TIMEOUT,
                ],
            ],
            self::VA702 => [
                self::ERROR => [
                    Error::PUBLIC_ERROR_CODE    => Code::BAD_REQUEST_ERROR,
                    Error::DESCRIPTION          => Description::BAD_REQUEST_PAYMENT_UPI_INVALID_VPA,
                ],
            ],
            self::VA703 => [
            ],
            self::VA704 => [
            ],
            self::VA801 => [
                self::ERROR => [
                    Error::PUBLIC_ERROR_CODE    => Code::GATEWAY_ERROR,
                    Error::DESCRIPTION          => Description::GATEWAY_ERROR_REQUEST_TIMEOUT,
                ],
            ],
            self::VA802 => [
                self::ERROR => [
                    Error::PUBLIC_ERROR_CODE    => Code::GATEWAY_ERROR,
                    Error::DESCRIPTION          => Description::GATEWAY_ERROR_TECHNICAL_ERROR,
                ],
            ],
            self::VA901 => [
                self::ERROR => [
                    Error::PUBLIC_ERROR_CODE    => Code::GATEWAY_ERROR,
                    Error::DESCRIPTION          => Description::GATEWAY_ERROR_REQUEST_TIMEOUT,
                ],
            ],
            self::VA902 => [

            ],
            self::VA903 => [

            ],
            self::TR101 => [
                self::ERROR => [
                    Error::PUBLIC_ERROR_CODE    => Code::GATEWAY_ERROR,
                    Error::DESCRIPTION          => Description::GATEWAY_ERROR_REQUEST_TIMEOUT,
                ],
            ],
            self::TR201 => [
                self::ERROR => [
                    Error::PUBLIC_ERROR_CODE    => Code::GATEWAY_ERROR,
                    Error::DESCRIPTION          => Description::GATEWAY_ERROR_REQUEST_TIMEOUT,
                ],
            ],
            self::TR301 => [
                self::ERROR => [
                    Error::PUBLIC_ERROR_CODE    => Code::GATEWAY_ERROR,
                    Error::DESCRIPTION          => Description::GATEWAY_ERROR_REQUEST_TIMEOUT,
                ],
            ],
            self::TR302 => [
            ],
            self::TR401 => [
                self::ERROR => [
                    Error::PUBLIC_ERROR_CODE    => Code::GATEWAY_ERROR,
                    Error::DESCRIPTION          => Description::GATEWAY_ERROR_REQUEST_TIMEOUT,
                ],
            ],
            self::TR501 => [
                self::ERROR => [
                    Error::PUBLIC_ERROR_CODE    => Code::GATEWAY_ERROR,
                    Error::DESCRIPTION          => Description::GATEWAY_ERROR_REQUEST_TIMEOUT,
                ],
            ],
            self::TR502 => [
            ],
            self::TR601 => [
                self::ERROR => [
                    Error::PUBLIC_ERROR_CODE    => Code::GATEWAY_ERROR,
                    Error::DESCRIPTION          => Description::GATEWAY_ERROR_REQUEST_TIMEOUT,
                ],
            ],
            self::TR602 => [
            ],
        ];

        return $map[$this->id];
    }
}
