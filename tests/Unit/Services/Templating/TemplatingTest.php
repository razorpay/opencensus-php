<?php

namespace Unit\Services;

use Mockery;

use RZP\Tests\Functional\TestCase;
use RZP\Services\Templating;

class TemplatingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testCreateNamespace()
    {
        $templating = $this->getTemplatingWithSendRequestMock();

        $templating->createNamespace(['name' => 'payments']);

        $expectedArgs = [[
            'path'       => '/namespaces',
            'method'   => 'POST',
            'data'      => ['name'  => 'payments'],
        ]];

        $templating->shouldHaveReceived('sendRequest', $expectedArgs);

    }

    public function testListNamespace()
    {
        $templating = $this->getTemplatingWithSendRequestMock();

        $templating->listNamespace();

        $expectedArgs = [[
            'path'      => '/namespaces',
            'method'    => 'GET',
            'data'      => [],
        ]];

        $templating->shouldHaveReceived('sendRequest', $expectedArgs);
    }

    public function testCreateTemplateConfigs()
    {
        $templating = $this->getTemplatingWithSendRequestMock();

        $samplePayload = [ 'samplePayloadKey'  => 'samplePayloadValue' ];

        $templating->createTemplateConfig($samplePayload);

        $expectedArgs = [[
            'path'      => '/template_configs',
            'method'    => 'POST',
            'data'      => $samplePayload,
        ]];

        $templating->shouldHaveReceived('sendRequest', $expectedArgs);
    }

    public function testUpdateTemplateConfigs()
    {
        $templating = $this->getTemplatingWithSendRequestMock();

        $samplePayload = [ 'samplePayloadKey'  => 'samplePayloadValue' ];

        $sampleConfigId = 'randomId';

        $templating->updateTemplateConfig($sampleConfigId, $samplePayload);

        $expectedArgs = [[
            'path'      => '/template_configs/'.$sampleConfigId,
            'data'      => $samplePayload,
            'method'    => 'PATCH',
        ]];

        $templating->shouldHaveReceived('sendRequest', $expectedArgs);
    }

    public function testListTemplateConfigsWithParams()
    {
        $templating = $this->getTemplatingWithSendRequestMock();

        $sampleParams = ['channel' => 'email'];

        $templating->listTemplateConfig($sampleParams);

        $expectedArgs = [[
            'path'      => '/template_configs',
            'method'    => 'GET',
            'data'      => $sampleParams,
        ]];

        $templating->shouldHaveReceived('sendRequest', $expectedArgs);
    }

    public function testListTemplateConfigsWithoutParams()
    {
        $templating = $this->getTemplatingWithSendRequestMock();

        $templating->listTemplateConfig();

        $expectedArgs = [[
            'path'      => '/template_configs',
            'method'    => 'GET',
            'data'      => [],
        ]];

        $templating->shouldHaveReceived('sendRequest', $expectedArgs);
    }

    public function testGetTemplateConfigs()
    {
        $templating = $this->getTemplatingWithSendRequestMock();

        $sampleConfigId = 'randomId';

        $templating->getTemplateConfig($sampleConfigId);

        $expectedArgs = [[
            'path'      => '/template_configs/'.$sampleConfigId,
            'method'    => 'GET',
        ]];

        $templating->shouldHaveReceived('sendRequest', $expectedArgs);
    }

    public function testViewTemplateConfig()
    {
        $templating = $this->getTemplatingWithSendRequestMock();

        $sampleConfigId = 'randomId';

        $templating->viewTemplateConfig($sampleConfigId);

        $expectedArgs = [[
            'path'      => '/template_configs/view/'.$sampleConfigId,
            'method'    => 'GET',
        ]];

        $templating->shouldHaveReceived('sendRequest', $expectedArgs);
    }

    public function testDeleteTemplateConfig()
    {
        $templating = $this->getTemplatingWithSendRequestMock();

        $sampleConfigId = 'randomId';

        $templating->deleteTemplateConfig($sampleConfigId);

        $expectedArgs = [[
            'path'      => '/template_configs/'.$sampleConfigId,
            'method'    => 'DELETE',
        ]];

        $templating->shouldHaveReceived('sendRequest', $expectedArgs);
    }

    public function testAssignRole()
    {
        $templating = $this->getTemplatingWithSendRequestMock();

        $samplePayload = [ 'samplePayloadKey'  => 'samplePayloadValue' ];

        $templating->assignRole($samplePayload);

        $expectedArgs = [[
            'path'      => '/user_roles/assign',
            'data'      => $samplePayload,
            'method'    => 'POST',
        ]];

        $templating->shouldHaveReceived('sendRequest', $expectedArgs);
    }

    public function testRevokeRole()
    {
        $templating = $this->getTemplatingWithSendRequestMock();

        $samplePayload = [ 'samplePayloadKey'  => 'samplePayloadValue' ];

        $templating->revokeRole($samplePayload);

        $expectedArgs = [[
            'path'      => '/user_roles/revoke',
            'data'      => $samplePayload,
            'method'    => 'POST',
        ]];

        $templating->shouldHaveReceived('sendRequest', $expectedArgs);
    }

    protected function getTemplatingWithSendRequestMock()
    {
        $templating = Mockery::mock('RZP\Services\Templating', [$this->app])->makePartial();

        $templating->shouldAllowMockingProtectedMethods();

        $templating->shouldReceive('sendRequest');

        return $templating;
    }
}
