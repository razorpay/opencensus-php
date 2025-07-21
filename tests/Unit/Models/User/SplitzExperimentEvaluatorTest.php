<?php

namespace Tests\Unit\Models\User;

use Tests\Unit\TestCase;
use RZP\Models\User\SplitzExperimentEvaluator;
use Mockery;

class SplitzExperimentEvaluatorTest extends TestCase
{
    protected $splitzEvaluator;
    protected $mockSplitzService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockSplitzService = Mockery::mock('RZP\Services\SplitzService');
        $this->app->instance('splitzService', $this->mockSplitzService);

        $this->splitzEvaluator = new SplitzExperimentEvaluator();
    }

    public function testIsLoginViaSsoEnabledReturnsTrue()
    {
        // Arrange
        $email = 'test@example.com';
        $expectedResponse = [
            'response' => [
                'variant' => [
                    'name' => 'enable'
                ]
            ]
        ];

        $this->mockSplitzService
            ->shouldReceive('evaluateRequest')
            ->once()
            ->with(Mockery::type('array'))
            ->andReturn($expectedResponse);

        // Act
        $result = $this->splitzEvaluator->isLoginViaSsoEnabled($email);

        // Assert
        $this->assertTrue($result);
    }

    public function testIsLoginViaSsoEnabledReturnsFalse()
    {
        // Arrange
        $email = 'test@example.com';
        $expectedResponse = [
            'response' => [
                'variant' => [
                    'name' => 'disable'
                ]
            ]
        ];

        $this->mockSplitzService
            ->shouldReceive('evaluateRequest')
            ->once()
            ->andReturn($expectedResponse);

        // Act
        $result = $this->splitzEvaluator->isLoginViaSsoEnabled($email);

        // Assert
        $this->assertFalse($result);
    }

    public function testIsLoginViaSsoEnabledWithMissingVariant()
    {
        // Arrange
        $email = 'test@example.com';
        $expectedResponse = [
            'response' => []
        ];

        $this->mockSplitzService
            ->shouldReceive('evaluateRequest')
            ->once()
            ->andReturn($expectedResponse);

        // Act
        $result = $this->splitzEvaluator->isLoginViaSsoEnabled($email);

        // Assert
        $this->assertFalse($result);
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testSplitzRequestParameters()
    {
        // Arrange
        $email = 'test@example.com';
        $expectedResponse = ['response' => ['variant' => ['name' => 'enable']]];

        $this->mockSplitzService
            ->shouldReceive('evaluateRequest')
            ->once()
            ->with(Mockery::on(function ($params) use ($email) {
                return isset($params['id']) &&
                    isset($params['experiment_id']) &&
                    isset($params['request_data']) &&
                    str_contains($params['request_data'], $email);
            }))
            ->andReturn($expectedResponse);

        // Act
        $this->splitzEvaluator->isLoginViaSsoEnabled($email);

        // Assert - Mock expectation is verified automatically
    }
}
