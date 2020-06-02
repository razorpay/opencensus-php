<?php


namespace RZP\Tests\Functional\Helpers;

use Mockery;
use RZP\Models\Terminal;
use RZP\Constants\Entity;

trait TerminalTrait
{
    protected function getTerminalsServiceMock()
    {
        $this->terminalRepository = new Terminal\Repository;

        $terminalsServiceMock = Mockery::mock('RZP\Services\TerminalsService')->makePartial();

        $terminalsServiceMock->shouldAllowMockingProtectedMethods();

        $this->app['terminals_service'] = $terminalsServiceMock;

        $this->app['config']->set('terminals_service.test.url', 'https://terminals-test.razorpay.com/');
        $this->app['config']->set('terminals_service.live.url', 'https://terminals-live.razorpay.com/');

        return $terminalsServiceMock;
    }

    protected function mockTerminalsServiceSendRequest($closure, $times = 2)
    {
        $this->terminalsServiceMock->shouldReceive('sendRequest')
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

        $responseData = ['data' => $data];

        $response->body = json_encode($responseData);

        return $response;
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

    protected function getHitachiOnboardErrorResponse()
    {
        $this->throwTerminalsServiceIntegrationException();
    }

    protected function getSyncDeleteTerminalTerminalServiceResponse() : \Requests_Response
    {

        $data = ["count" => 7];

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
