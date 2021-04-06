<?php

namespace RZP\Gateway\P2p\Upi\Sharp;

use RZP\Error\P2p\ErrorCode;
use RZP\Gateway\P2p\Base\Request;
use RZP\Gateway\P2p\Base\Response;
use RZP\Gateway\P2p\Upi\Contracts;
use RZP\Models\P2p\BankAccount\Bank;
use RZP\Gateway\P2p\Upi\Mock\Scenario;
use RZP\Models\P2p\BankAccount\Entity;
use RZP\Gateway\P2p\Upi\Npci\ClAction;

class BankAccountGateway extends Gateway implements Contracts\BankAccountGateway
{
    public function initiateRetrieve(Response $response)
    {
        if ($this->handleFailureScenarios($response, [Scenario::BA102]))
        {
            return;
        }

        $request = new Request();
        $request->setRedirect($this->getContextDevice()->get(Entity::CREATED_AT));

        $response->setRequest($request);
    }

    public function retrieve(Response $response)
    {
        if ($this->handleFailureScenarios($response, [Scenario::BA201, Scenario::BA202, Scenario::BA203]))
        {
            return;
        }

        if ($this->scenario->is(Scenario::BA205))
        {
            $response->setData([
                'bank_id'       => $this->input->get('bank')->get('id'),
                'bank_accounts' => [],
            ]);

            return;
        }

        $sub = (int) $this->scenario->getParsedSub(Scenario::BA204);
        $count = ($sub % 100);
        $mask = floor($sub / 100);

        $ifsc = $this->input->get('bank')->get('ifsc');

        $bankAccounts = [
            $this->createMockBankAccount($ifsc, 1, $mask)
        ];

        for ($i = 2; $i <= $count; $i++)
        {
            $bankAccounts[] = $this->createMockBankAccount($ifsc, $i, $mask);
        }

        $response->setData([
            'bank_id'       => $this->input->get('bank')->get('id'),
            'bank_accounts' => $bankAccounts,
        ]);
    }

    public function initiateSetUpiPin(Response $response)
    {
        $bankAccount = $this->input->get('bank_account');

        $this->cl()->setData([
            Entity::BANK_ACCOUNT   => $bankAccount,
        ]);

        $request = $this->cl()->getCredentialRequest($this->input->get(Entity::ACTION));

        $response->setRequest($request);
    }

    public function setUpiPin(Response $response)
    {
        $bankAccount = $this->input->get('bank_account');

        $gatewayData = [
            'upi_pin_state' => 'failed',
        ];

        if ($this->scenario->is(Scenario::BA301))
        {
            // For timeout, we can set upi_pin_state as unknown.
            // Also, we verify the fact that entity is getting updating for error too.
            $gatewayData = [
                'upi_pin_state' => 'unknown',
            ];
        }

        $failures = [Scenario::BA301, Scenario::BA302, Scenario::BA303, Scenario::BA304, Scenario::BA305];

        $response->setData([
            'id'            => $bankAccount->get('id'),
            'gateway_data'  => $gatewayData,
        ]);

        if ($this->handleFailureScenarios($response, $failures))
        {
            return;
        }

        $credentials = $this->handleSdkCredential($response);

        if ($credentials === null)
        {
            return;
        }

        $response->setData([
            'id'            => $bankAccount->get('id'),
            // TODO: We need to send gateway data here
        ]);
    }

    public function initiateFetchBalance(Response $response)
    {
        $bankAccount = $this->input->get('bank_account');

        $this->cl()->setData([
            Entity::BANK_ACCOUNT   => $bankAccount,
        ]);

        $request = $this->cl()->getCredentialRequest(ClAction::BALANCE);

        $response->setRequest($request);
    }

    public function fetchBalance(Response $response)
    {
        $bankAccount = $this->input->get('bank_account');

        $failures = [Scenario::BA401, Scenario::BA402, Scenario::BA403];

        if ($this->handleFailureScenarios($response, $failures))
        {
            return;
        }

        $credentials = $this->handleSdkCredential($response);

        if ($credentials === null)
        {
            return;
        }

        $sub = $this->scenario->getParsedSub(Scenario::BA404);
        $tenth = ($sub % 100);
        $power = floor($sub / 10);

        $balance = pow(2, $power) * 100 + $tenth;

        $response->setData([
            'id'            => $bankAccount->get('id'),
            'response'      => [
                'balance'   => $balance,
                'currency'  => 'INR',
            ]
        ]);
    }

    public function retrieveBanks(Response $response)
    {
        if ($this->handleFailureScenarios($response, [Scenario::BB101, Scenario::BB102]))
        {
            return;
        }

        if ($this->scenario->is(Scenario::BB104))
        {
            $response->setData([
                'banks' => [],
            ]);

            return;
        }

        $sub = (int) $this->scenario->getParsedSub(Scenario::BB103);
        $count = ($sub % 100);

        $banks = [];

        for ($i = 1; $i <= $count; $i++)
        {
            $banks[] = $this->createMockBank($i);
        }

        $response->setData([
            'banks' => $banks,
        ]);
    }

    private function createMockBankAccount($ifsc, $index, $mask)
    {
        $last4 = (1000 + ($index * 10));

        $pin        = boolval($mask & 1);           // Whether the pin is set
        $long       = boolval($mask & 2);           // Whether to use long masking and name
        $length     = boolval($mask & 4) ? 6 : 4;   // Whether PIN length is 4
        $account    = boolval($mask & 8);           // Whether to seed with account number

        $masked = str_repeat('x', ($long ? 12 : 4));

        $creds = [
            [
                'type'          => 'pin',
                'sub_type'      => 'upipin',
                'set'           => $pin,
                'length'        => $length,
                'format'        => 'NUM',
            ],
            [
                'type'          => 'pin',
                'sub_type'      => 'atmpin',
                'set'           => $pin,
                'length'        => $length,
                'format'        => 'ALPHANUM'
            ]
        ];

        $bankAccount = [
            'ifsc'                  => $ifsc . '000' . $last4,
            'beneficiary_name'      => 'Sharp Customer' . ($long ? ' Longname' : ''),
            'account_number'        => '9753108' . $last4,
            'masked_account_number' => $masked . ($pin ? 1 : 0) . $length . $last4,
            'gateway_data'          => [
                'id'                => 'SRP' . $last4,
            ],
            'creds'                 => $creds
        ];

        if ($account === false)
        {
            unset($bankAccount['account_number']);
        }

        return $bankAccount;
    }

    private function createMockBank($index)
    {
        $last4 = (1000 + ($index * 10));

        $bank = [
            'name'              => 'Sharp bank' . $last4,
            'handle'            => $this->context->handleCode(),
            'gateway_data'      => [
                'id'            => 'SRP' . $last4,
                ],
            'upi_iin'           => '12'.$last4,
            'active'            => true,
        ];

        return $bank;
    }
}
