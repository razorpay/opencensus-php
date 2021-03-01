<?php

namespace RZP\Tests\P2p\Service\Base\Traits;

use phpseclib\Crypt\AES;
use RZP\Error\ErrorCode;
use RZP\Gateway\P2p\Upi\Npci;
use RZP\Tests\P2p\Service\Base\P2pHelper;
use RZP\Tests\P2p\Service\Base\Scenario;

trait NpciClTrait
{
    /**
     * @var case assertion message
     */

    protected $npciClAssertionMessage;
    /**
     * @param array $input
     * @param string $action
     * @param callable|null $closure
     *  Param 1 is list of params
     *  Param 2 is complete content
     */
    protected function handleNpciClRequest(
        array $input,
        string $action,
        string $callback = null,
        array $vector = [],
        callable $closure = null)
    {
        $expected = [
            'version'   => 'v1',
            'type'      => 'sdk',
            'request' => [
                'sdk'       => 'npci',
                'content'   => [
                    'vector' => $vector,
                ],
                'action'    => $action,
            ]
        ];

        if (is_null($callback) === false)
        {
            $expected['callback'] = $callback;
        }

        $this->assertArraySubset($expected, $input, true, $this->npciClAssertionMessage ?? '');
        $this->assertCount(3, $input['request']);
        $this->assertCount(2, $input['request']['content']);
        $this->assertSame($input['request']['content']['count'], count($input['request']['content']['vector']));

        if (is_callable($closure) === true)
        {
            $closure($input['request']['content']['vector']);
        }

        return function(array $override = []) use ($action, $input)
        {
            return $this->getMockedSdkData($action, $input['request']['content']['vector'], $override);
        };
    }

    protected function getMockedSdkData(string $action, array $vector, array $input = [])
    {
        switch ($action)
        {
            case Npci\ClAction::GET_CHALLENGE:
                $response = base64_encode(implode('|', [
                    $input['token'],
                    $vector[0],
                    $vector[1],
                ]));
                break;

            case Npci\ClAction::REGISTER_APP:
                $response = true;
                break;

            case Npci\ClAction::GET_CREDENTIAL:
                $response = $this->getCredentialResponse($vector, $input);
                break;
        }

        return [$action => $response];
    }

    protected function getCredentialResponse(array $vector, array $input)
    {
        $txnId = $vector[3]['txnId'];

        switch (3)
        {
            case strpos($txnId, 'M.CL301'):
                $response = '';
                break;

            case strpos($txnId, 'M.CL302000'):
                $response = 'USER_ABORTED';
                break;

            case strpos($txnId, 'M.CL302'):
                $response = 'UNKNOWN FAILURE';
                break;

            case strpos($txnId, 'M.CL303'):
                $errorCode = substr($txnId, 10, 3);
                $errorText = ($errorCode === 'L05') ? 'Technical Issue, please contact your administrator' : '';
                $response = [
                    'error' => [
                        'errorCode' => $errorCode,
                        'errorText' => $errorText,
                    ],
                ];
                break;

            default:
                $response = [
                    'type'      => $vector[2]['CredAllowed'][0]['type'],
                    'subType'   => $vector[2]['CredAllowed'][0]['subType'],
                    'data'      => [
                        'code'  => $vector[0], // NPCI
                        'ki'    => '20150822',
                        'encryptedBase64String' => '2.0|' . base64_encode(implode('|', $vector[4]))
                    ],
                ];
        }

        return $response;
    }

    protected function runClFailureFlow(callable $object, callable $initiate, callable $action)
    {
        $scenarios = [
            Scenario::CL301 => [
                '000' => ['BAD_REQUEST_ERROR', 'UPI device must be present']
            ],
            Scenario::CL302 => [
                '000' => ['BAD_REQUEST_ERROR', 'Payment processing cancelled'],
                '001' => ['BAD_REQUEST_ERROR', 'Something went wrong, please try again after sometime.'],
            ],
            Scenario::CL303 => [
                '000' => ['BAD_REQUEST_ERROR', 'The payment request has invalid device'],
                'L01' => ['BAD_REQUEST_ERROR', 'The payment request has invalid device'],
                'L05' => ['BAD_REQUEST_ERROR', 'The payment request has invalid device'],
            ],
        ];

        foreach ($scenarios as $scenario => $subs)
        {
            foreach ($subs as $sub => $expected)
            {
                $helper = $object();

                $helper->setScenarioInContext($scenario, $sub);

                $request = $initiate($helper);

                $function = $this->handleNpciClRequest($request, Npci\ClAction::GET_CREDENTIAL);

                $sdk = $function(['scenario' => $scenario]);

                $this->withFailureResponse($helper, function ($error) use ($expected)
                {
                    $this->assertSame($expected[0], $error['code']);
                    $this->assertSame($expected[1], $error['description']);
                });

                $action($helper, $request, ['sdk' => $sdk]);
            }
        }

    }

    protected function calculateHmac(string $action, string $token, array $input = [])
    {
        $crypto = (new AES(AES::MODE_CTR, base64_decode($token)));

        $string = $this->fixtures->device->getAppName() . '|' .
                  substr($this->fixtures->device->getContact(), -10) . '|' .
                  $this->fixtures->device->getUuid();

        $hash = hash('sha256', $string);

        return base64_encode($crypto->encrypt($hash));
    }
}
