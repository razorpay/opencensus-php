<?php

namespace RZP\Tests\Functional\Helpers;

use Mockery;

trait MocksDiagTrait
{

    public function createAndReturnDiagMock()
    {
        $diagMock = Mockery::mock('RZP\Diag\EventCode');

        // RepositoryManager mocking
        $this->repoMock = Mockery::mock('\RZP\Base\RepositoryManager', [$this->app]);

        $this->repoMock->shouldReceive('driver')->with('diag')->andReturn($diagMock);

        $this->app->instance('diag', $diagMock);

        return $diagMock;
    }
}
