<?php

namespace RZP\Gateway\Ccavenue\Mock;

use RZP\Gateway\Base;
use RZP\Gateway\Base\Action;

class Server extends Base\Mock\Server
{
    public function getCallback(array $upiEntity, array $payment): array
    {
        $this->action = Action::CALLBACK;

        $content = $this->getCallbackData($upiEntity, $payment);

        $this->content($content, 'callback');

        return $this->getCallbackRequest($content);
    }

    public function getCallbackRequest($data)
    {
        $url = '/callback/ccavenue';
        $method = 'post';

        $server = [
            'CONTENT_TYPE'     => 'application/json',
        ];

        $raw = json_encode($data);

        return [
            'url'       => $url,
            'method'    => $method,
            'raw'       => $raw,
            'server'    => $server,
        ];
    }

    protected function getCallbackData(array $upiEntity, array $payment): array
    {
        return [
            "order_id"          =>  $payment['id'],
            "encResp"          => '6f5ef39eaff44c5690701352ede961a05576bc02889e75d4cf0aaf472483aea14ec7649acc9fbf92ea1bb5499b1e872e5bba8031d7a8de1a5702aff7cef437af8d27efaf5b53a646720818b5c8b7d2f34cc6c60eae761d44e8f4d2cae937b83d39e1122a802e0e72159ead677bc58340f4256b4115fa9f0d2187df6818e97abbdbf64b5d3f6518934984037e46cda6fd9a0acfa33c833185254966b2fb93856ade572bd31bf4fb6df8d2068522e847f4178fb36f2fda51e2e5e113b172e5f67ab6dbe0053f7fdae7f764a256ce711e64e50db380ab5185e188e5523d09d80df40b5baa8d5b76aadb4f68a921bd53bdc3d0dd713b541d12e98a8835356a4d8c39ec10f92e5e985a9c8b27374fdf113aea8cde44c8d5927e810f4d8905378e4ff7946173208c02ab6cb545f2788d260e1ddab243e7732e6fcfaa1bc2886a425357b36ab7c3a7f585d5b5a3405e67cfc2ec3efa4b530919d466da12e7142b1a05c9ae1c275ea75dda4855e460982a4e5dca2aaf765aa7f970d615628c645605382d3565439dfeab2d46a0e8c73b22a0517d6114697455632b18f0f26ce7958270d4b6bab27e65e7c4dedd61bdaf32c5d61fc8cb3929cc63fd3fd0963ee4422ef0ca36abf0d34ab5ff6d8977ded17634d0538f06692a9e36e328bf649fb74259ccf49b0bf41674aeaa322af4b834bbae9773074dc414c3c48a4f7570bdecce9468fc5db8e075ced9dd91cc6a91a9e6ee0453472d42740436e7f88c3462373504ba42036c59f4b0464372e71f9fd81fbecbda5227eca2f599b97c24dfa2a3e87004cc496361ef94ec6c971b9349f4998ff4b3514d2ee0a1777bca76aa47ea561416ac18a577b0b5cfec22e0af5036280147697966ed727784d5c96f2b98af0c0688f6beb73fc673b9151fe19d82a4d1058c5b3c1187e005bf6e598248c59d7cea9899d6f332f0d9cbc45425acb4ada04d2ddb877ad9adb03ae1dbd886063e7549d257bcd82a07c00a830e55d2993da6b7f617c9587a74edf32a7ca83e7360178400f4c7ac04abf407b42ad7c9bce0f09b0c1c8963018c1e00ef0c559431a74a524fb86e4cfc85a8f76a3f23cac1321954d6b6',
        ];
    }
}
