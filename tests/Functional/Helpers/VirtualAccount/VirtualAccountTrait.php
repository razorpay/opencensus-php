<?php

namespace RZP\Tests\Functional\Helpers\VirtualAccount;

use RZP\Models\Order;
use RZP\Constants\Mode;
use RZP\Models\VirtualAccount\Provider;
use RZP\Tests\Functional\Partner\PartnerTrait;

trait VirtualAccountTrait
{
    use PartnerTrait;

    private function createVirtualAccount(
        array $input = [],
              $numeric = true,
              $descriptor = null,
              $qrCode = false,
              $vpa = false,
              $vpaDescriptor = null,
              $mode = 'test')
    {
        $defaultValues = $this->getDefaultVirtualAccountRequestArray();

        if ($numeric === false)
        {
            $defaultValues['receivers']['bank_account']['numeric'] = 0;
        }

        if ($descriptor !== null)
        {
            $defaultValues['receivers']['bank_account']['descriptor'] = $descriptor;
        }

        if ($qrCode === true)
        {
            $defaultValues['receivers']['types'] = ['qr_code'];
        }

        if($vpa === true)
        {
            $defaultValues['receivers']['types'] = ['vpa'];
        }

        if ($vpaDescriptor !== null)
        {
            $defaultValues['receivers']['vpa']['descriptor'] = $vpaDescriptor;
        }

        $attributes = array_merge($defaultValues, $input);

        ($mode === Mode::TEST) ? $this->ba->privateAuth() : $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $request = [
            'method'  => 'POST',
            'url'     => '/virtual_accounts',
            'content' => $attributes,
        ];

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    private function createVirtualAccountPartnerAuth(array $input = [])
    {
        list($subMerchantId, $client) = $this->createPartnerEnv();

        $attributes = $this->getDefaultVirtualAccountRequestArray();

        $attributes = array_merge($attributes, $input);

        $this->ba->partnerAuth($subMerchantId, 'rzp_test_partner_' . $client->getId(), $client->getSecret());

        $request = [
            'method'  => 'POST',
            'url'     => '/virtual_accounts',
            'content' => $attributes,
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->ba->deleteAccountAuth();

        return [$response, $subMerchantId, $client];
    }

    private function createPartnerEnv()
    {
        $partner = $this->fixtures->merchant->createWithBalance();

        $client = $this->createPartnerApplicationAndGetClientByEnv(
            'dev',
            [
                'type'        => 'partner',
                'id'          => 'AwtIC8XQqM0Wet',
                'merchant_id' => $partner->getId(),
                'partner_type'=> 'aggregator',
            ]);

        $this->fixtures->edit('merchant', $partner->getId(), ['partner_type' => 'aggregator']);

        $subMerchantId = '10000000000000';

        $this->fixtures->feature->create([
                                             'entity_type' => 'application',
                                             'entity_id'   => $client->getApplicationId(),
                                             'name'        => 'virtual_accounts']);

        $this->fixtures->create(
            'merchant_access_map',
            [
                'entity_id'   => $client->getApplicationId(),
                'merchant_id' => $subMerchantId,
            ]
        );

        return [$subMerchantId, $client];
    }

    private function createVirtualAccountForOrder(Order\Entity $order, array $input = [])
    {
        $this->ba->publicAuth();

        $request = [
            'method'  => 'POST',
            'url'     => '/orders/' . $order->getPublicId() . '/virtual_accounts',
            'content' => $input,
        ];

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

   private function createVirtualAccountForOfflineOrder(string $orderId, array $input = [])
    {
        $this->ba->publicAuth();

        $request = [
            'method'  => 'POST',
            'url'     => '/orders/' . $orderId . '/virtual_accounts',
            'content' => $input,
        ];

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    private function createVirtualAccountOldFormat(array $input = [])
    {
        $defaultValues = $this->getOldVirtualAccountRequestArray();

        $attributes = array_merge($defaultValues, $input);

        $this->ba->privateAuth();

        $request = [
            'method'  => 'POST',
            'url'     => '/virtual_accounts',
            'content' => $attributes,
        ];

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    private function closeVirtualAccountViaEdit(string $id)
    {
        $request = [
            'method'  => 'PATCH',
            'url'     => '/virtual_accounts/'.$id,
            'content' => [
                'status' => 'closed',
            ],
        ];

        $this->ba->privateAuth();

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    private function closeVirtualAccount(string $id)
    {
        $request = [
            'method'  => 'POST',
            'url'     => '/virtual_accounts/'.$id.'/close',
        ];

        $this->ba->privateAuth();

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    private function deleteVirtualAccount(string $id)
    {
        $request = [
            'method'  => 'DELETE',
            'url'     => '/virtual_accounts/'.$id,
        ];

        $this->ba->privateAuth();

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    private function fetchVirtualAccount(string $id)
    {
        $request = [
            'method'  => 'GET',
            'url'     => '/virtual_accounts/' . $id,
        ];

        $this->ba->privateAuth();

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    private function fetchVirtualAccounts(array $input = [])
    {
        $request = [
            'method'  => 'GET',
            'url'     => '/virtual_accounts',
            'content' => $input,
        ];

        $this->ba->privateAuth();

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    private function fetchVirtualAccountsForDashboard(array $input = [])
    {
        $request = [
            'method'  => 'GET',
            'url'     => '/virtual_accounts',
            'content' => $input,
        ];

        $this->ba->proxyAuth();

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    private function fetchVirtualAccountPayments(string $id = null, string $vaTransactionId = null)
    {
        $url = '';
        if ($id === null)
        {
            $url = '/payments?virtual_account=1';

            if ($vaTransactionId !== null)
            {
                $url .= ('&va_transaction_id=' . $vaTransactionId);
            }
        }
        else
        {
            $url = '/virtual_accounts/' . $id . '/payments';
        }
        $request = [
            'method' => 'GET',
            'url'    => $url,
        ];

        $this->ba->privateAuth();

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    private function refundVirtualAccountExcessPayments()
    {
        $request = [
            'method'  => 'POST',
            'url'     => '/virtual_accounts/refund/excess',
        ];

        $this->ba->appAuth();

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    private function payVirtualAccount(string $virtualAccountId, array $paymentArray = [])
    {
        $defaultPaymentArray = $this->getDefaultBankTransferArray();

        $paymentArray = array_merge($defaultPaymentArray, $paymentArray);

        $response = $this->fetchVirtualAccount($virtualAccountId);

        $bankAccount = $response['receivers'][0];

        $paymentArray['payee_account'] = $bankAccount['account_number'];
        $paymentArray['payee_ifsc']    = $bankAccount['ifsc'];

        $request = [
            'method'  => 'POST',
            'url'     => '/ecollect/validate',
            'content' => $paymentArray,
        ];

        if ($paymentArray['payee_ifsc'] === Provider::IFSC[Provider::YESBANK])
        {
            $this->ba->yesbankAuth();
        }
        else if ($paymentArray['payee_ifsc'] === Provider::IFSC[Provider::ICICI])
        {
            $this->ba->iciciAuth();
        }
        else
        {
            $request['url'] = '/ecollect/validate/test';

            $this->ba->proxyAuth();
        }

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    private function payViaBharatQr($qrCodeId, string $gateway)
    {
        $content = $this->getMockServer($gateway)->getBharatQrCallbackForRecon($qrCodeId);

        $request = [
            'method'  => 'POST',
            'url'     => '/payment/callback/bharatqr/'. $gateway,
            'content' => $content
        ];

        $this->ba->directAuth();

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    private function closeVirtualAccountsByCloseBy()
    {
        $request = [
            'method'  => 'POST',
            'url'     => '/virtual_accounts/close',
        ];

        $this->ba->cronAuth();

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    private function getDefaultBankTransferArray()
    {
        return [
            'payer_account'  => '7654321234567',
            'payer_ifsc'     => 'HDFC0000001',
            'mode'           => 'neft',
            'transaction_id' => strtoupper(random_alphanum_string(12)),
            'time'           => time(),
            'amount'         => 100,
            'description'    => 'Test bank transfer',
        ];
    }

    private function getOldVirtualAccountRequestArray()
    {
        return [
            'name'           => 'Test virtual account',
            'description'    => 'VA for tests',
            'receiver_types' => [
                'bank_account'
            ],
            'notes'          => [
                'a' => 'b',
            ],
        ];
    }

    private function getDefaultVirtualAccountRequestArray()
    {
        return [
            'name'        => 'Test virtual account',
            'description' => 'VA for tests',
            'receivers'   => [
                'types' => [
                    'bank_account',
                ],
            ],
            'notes'       => [
                'a' => 'b',
            ],
        ];
    }

    private function addReceiverToVirtualAccount(string $virtualAccountId, string $receiverType, $input = [])
    {
        $content = [
            'types'       => [
                $receiverType
            ],
            $receiverType => $input
        ];
        $request = [
            'method'  => 'POST',
            'url'     => '/virtual_accounts/' . $virtualAccountId . '/receivers',
            'content' => $content,
        ];

        $this->ba->privateAuth();

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    private function savePrefix(string $prefix)
    {
        $this->ba->proxyAuth();

        $content  = [
            'prefix' => $prefix,
        ];
        $request  = [
            'url'     => '/virtual_vpa_prefixes',
            'method'  => 'post',
            'content' => $content,
        ];
        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    public function ecollectValidateVirtualAccountVpa($gateway, $vpaPrefix, $input)
    {
        $url = '/test/ecollect/validate/' . $gateway . '/' . $vpaPrefix;

        $request = [
            'method'  => 'POST',
            'url'     => $url,
            'raw'     => $input
        ];

        $response = $this->makeRequestAndGetRawContent($request);

        return $response;
    }

    public function getVirtualAccountConfig()
    {
        $request = [
            'method'  => 'GET',
            'url'     => '/virtual_account/configs',
        ];

        $this->ba->proxyAuth();

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    public function addCustomAccountNumberSetting($input = [])
    {
        $request = [
            'method'  => 'POST',
            'url'     => '/virtual_accounts/setting/account_number',
            'content' => $input,
        ];

        $this->ba->adminAuth();

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }
}
