<?php


namespace RZP\Tests\Functional\Helpers;

use Mockery;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Terminal;
use RZP\Constants\Entity;

trait TerminalTrait
{
    protected function getTerminalsServiceMock()
    {
        $this->terminalRepository = new Terminal\Repository;

        $terminalsServiceMock = Mockery::mock('RZP\Services\TerminalsService', [$this->app])->makePartial();

        $terminalsServiceMock->shouldAllowMockingProtectedMethods();

        $this->app['terminals_service'] = $terminalsServiceMock;

        $this->app['config']->set('terminals_service.test.url', 'https://terminals-test.razorpay.com/');
        $this->app['config']->set('terminals_service.live.url', 'https://terminals-live.razorpay.com/');

        return $terminalsServiceMock;
    }

    protected function mockTerminalsServiceProxyRequest($response)
    {
        $this->terminalsServiceMock->shouldReceive('proxyTerminalService')
            ->andReturn($response);
    }


    protected function mockTerminalsServiceSendRequest($closure, $times = 2)
    {
        $this->terminalsServiceMock->shouldReceive('sendRequest')
            ->times($times)
            ->andReturnUsing($closure);
    }

    protected function mockTerminalsServiceSendFormRequest($closure, $times = 2)
    {
        $this->terminalsServiceMock->shouldReceive('sendFormRequest')
            ->times($times)
            ->andReturnUsing($closure);
    }

    protected function throwTerminalsServiceIntegrationException()
    {
        throw new \Requests_Exception_Transport_cURL('curl timed out', []);
    }

    protected function getDefaultTerminalServiceResponse($data = []) : \Requests_Response
    {
        if ($data === [])
        {
            $terminal = $this->getLastEntity(Entity::TERMINAL, true);

            $data = $this->getTerminalToArrayPassword($terminal[Terminal\Entity::ID]);

            Terminal\Entity::verifyIdAndSilentlyStripSign($data['id']);
        }

        $response =  new \Requests_Response;

        $data['entity'] = 'terminal';

        $responseData = ['data' => $data];

        $response->body = json_encode($responseData);

        return $response;
    }

    protected function getTokenisedTerminalServiceResponse($a, $b, $c) : \Requests_Response
    {
        $data = json_decode($b, true);

        if (isset($data["id"]) === false)
        {
            $data["id"] = "10000000000011";
        }

        $response =  new \Requests_Response;

        $responseData = ['data' => $data];

        $response->body = json_encode($responseData);

        return $response;
    }

    protected function getTerminalServiceCheckSecretResponse() : \Requests_Response
    {
        $data = [
            'gateway_terminal_password' => true,
            'gateway_terminal_password2' => true,
            'gateway_secure_secret' => true,
            'gateway_secure_secret2' => true
        ];

        $response = new \Requests_Response;

        $responseData = ['data' => $data];

        $response->body = json_encode($responseData);

        return $response;
    }

    protected function getServerErrorTerminalServiceResponse()
    {
        $this->throwTerminalsServiceIntegrationException();
    }

    protected function getHitachiOnboardResponse($id) : \Requests_Response
    {
        $data = [];
        $terminal = ["id" => $id, "gateway" => "hitachi"];
        $data["terminal"] = $terminal;

        $response = new \Requests_Response;

        $responseData = ['data' => $data];

        $response->body = json_encode($responseData);

        return $response;
    }

    protected function getProxyTerminalOnboardStatusResponse() : \Requests_Response
    {
        $data = [];
        $terminal = ["id" => "10000000000000", "gateway" => "hitachi"];
        $data["terminal"] = $terminal;
        $data["message"] = "test_message";

        $response = new \Requests_Response;

        $responseData = ['data' => [$data]];

        $response->body = json_encode($responseData);

        return $response;
    }

    protected function getHitachiOnboardErrorResponse()
    {
        $this->throwTerminalsServiceIntegrationException();
    }

    protected function getHitachiOnboardResponseAndCreate(string $category, $merchantId = "10000000000000")
    {
        // Though in production actual terminal will be created by terminal service, but since that part is mocked,
        // creating the terminal through code only and the mock response shd return that terminal id
        $mockTerminal = $this->fixtures->create('terminal:direct_hitachi_terminal', ["category"=>$category, "merchant_id"=> $merchantId]);

        $tid = $mockTerminal->getId();

        return $this->getHitachiOnboardResponse($tid);
    }

    protected function getHitachiOnboardIntegrationErrorResponse()
    {
        throw new Exception\IntegrationException('Terminals service request failed with status code : 500',
            ErrorCode::SERVER_ERROR_TERMINALS_SERVICE_INTEGRATION_ERROR);
    }
    protected function getSyncDeleteTerminalTerminalServiceResponse() : \Requests_Response
    {

        $data = ["count" => 7];

        $response =  new \Requests_Response;

        $responseData = ['data' => $data];

        $response->body = json_encode($responseData);

        return $response;
    }

    protected function getProxyDeleteTerminalSubmerchantTerminalServiceResponse() : \Requests_Response
    {

        $data = ["data" => null];

        $response =  new \Requests_Response;

        $responseData = ['data' => $data];

        $response->body = json_encode($responseData);

        return $response;
    }

    protected function getProxyEditTerminalServiceResponseBadRequest() : \Requests_Response
    {
        throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_TERMINALS_SERVICE_ERROR, null, [], "Terminal doesn't exist with this Id");
    }

    protected function getProxyRestoreTerminalServiceResponse() : \Requests_Response
    {

        $data = ["id" => "123456789asdfg", "gateway" => "payu", 'merchant_id' => '10000000000000'];

        $response =  new \Requests_Response;

        $responseData = ['data' => $data];

        $response->body = json_encode($responseData);

        return $response;
    }

    protected function getProxyReassignTerminalServiceResponse() : \Requests_Response
    {

        $data = ["id" => "123456789asdfg", "gateway" => "payu", 'merchant_id' => '100000Razorpay'];

        $response =  new \Requests_Response;

        $responseData = ['data' => $data];

        $response->body = json_encode($responseData);

        return $response;
    }

    protected function getProxyCreateGatewayCredentialTerminalServiceResponse() : \Requests_Response
    {

        $data = ["id" => "123456789asdfg", "gateway_credential_id" => "12345678901234"];

        $response =  new \Requests_Response;

        $responseData = ['data' => $data];

        $response->body = json_encode($responseData);

        return $response;
    }

    protected function getProxyFetchGatewayCredentialTerminalServiceResponse() : \Requests_Response
    {

        $data = ["merchant_ids" => ['10000000000000'], "gateway" => "paytm"];

        $response =  new \Requests_Response;

        $responseData = ['data' => $data];

        $response->body = json_encode($responseData);

        return $response;
    }


    protected function getProxyCreateTerminalSubmerchantTerminalServiceResponse() : \Requests_Response
    {

        $data = ["data" => ["id" => '1000000000000t', "submerchants" => ['1000000000000m']]];

        $response =  new \Requests_Response;

        $responseData = ['data' => $data];

        $response->body = json_encode($responseData);

        return $response;
    }

    protected function getProxyExecuteTerminalTestRunResponse() : \Requests_Response
    {
        $data = ['data' => [
                'id' => 'trmnlTestRunId',
                'terminal_id' => 'GzGbeCf6yWzenn',
                'created_by' => 'admin@razorpay.com',
                'payment_test_summary' =>
                [
                  'success' => 0,
                  'failed' => 0,
                  'pending' => 0,
                  'in_progress' => 0,
                  'timed_out' => 0,
                ],
                'refund_test_summary' =>
                [
                  'success' => 0,
                  'failed' => 0,
                  'pending' => 0,
                  'in_progress' => 0,
                  'timed_out' => 0,
                ],
                'verify_test_summary' =>
                [
                  'success' => 0,
                  'failed' => 0,
                  'pending' => 0,
                  'in_progress' => 0,
                  'timed_out' => 0,
                ],
                'status' => '',
                'activate_terminal_on_success' => true,
                'terminal_test_cases' => NULL,
                'created_at' => 0,
                'updated_at' => 0,
        ]];

        $response =  new \Requests_Response;

        $responseData = ['data' => $data];

        $response->body = json_encode($responseData);

        return $response;
    }

    protected function getTerminalToArrayPassword($terminalId)
    {
        Terminal\Entity::verifyIdAndSilentlyStripSign($terminalId);

        $terminalEntity = $this->terminalRepository->findOrFail($terminalId);

        return $terminalEntity->toArrayWithPassword();
    }

}
