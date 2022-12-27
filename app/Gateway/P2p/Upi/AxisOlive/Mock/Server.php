<?php

namespace RZP\Gateway\P2p\Upi\AxisOlive\Mock;

use RZP\Gateway\P2p\Base\Mock;
use RZP\Gateway\P2p\Upi\AxisOlive\Fields;
use RZP\Gateway\P2p\Upi\AxisOlive\Actions\ClientAction;

/**
 * Class Server
 * Mock server to mock all device flows
 * @package RZP\Gateway\P2p\Upi\AxisOlive\Mock
 */
class Server extends Mock\Server
{
    public function setMockRequest($request)
    {
        parent::setMockRequest($request);

        $content = json_decode($request['content'], true);

        return $content;
    }

    public function clientGetGatewayConfig($request)
    {
        $response = [
            Fields::DATA    => [
                Fields::RAW => "{\"code\":\"00\",\"result\":\"Success\",\"data\":{\"merchantauthtoken\":\"tjjd5r2c1ejdl68p13xeva4755mz9n5phpu9gvtrp4wmzt786iqdew0quubejvhpef6n24y7a0uowuuq3ngepuioxr02kf5dvz422cywnf3nrjp0wx03ir6rhoacnac6h6w8uyiormcwq1t1elzdbhygyttgx7ruulhdkfk9vzrpizwrz5atsulfydny3szyrgvfr0ec7udwkkadgs3ejmk8sqz3p5z58wv3gd2ladtqslefcoi3d5f17mpbtg2f\"},\"riskScoreValue\":null,\"checkSum\":null}",
                Fields::CODE                     => '00',
                Fields::RESULT                   => 'Success',
                Fields::CHECK_SUM                =>  null,
                Fields::DATA                     => [
                    Fields::MERCHANT_AUTH_TOKEN      => 'k1jbukiqggdbv2hpy2vo5urrz8wz9p3oz13beidbd1gog1dpb42xod33x53bn4wmvz2t4nmfukozqj7costj7alg8y9sbkwxavfr648rrn20p8sax36',
                    Fields::RESULT                   => 'Success',
                    Fields::RISK_SCORE_VALUE         => null,
                ],
                Fields::SUCCESS  => true,
                Fields::NEXT  => [],
            ],
        ];

        $this->content($response, ClientAction::GET_GATEWAY_CONFIG);

        $response = $this->makeResponse($response);

        return $response;
    }

    protected function makeResponse($input)
    {
        $response = new \WpOrg\Requests\Response();

        $response->headers = ['content-type' => 'application/json'];

        $response->body = json_encode($input);

        return $response;
    }

}
