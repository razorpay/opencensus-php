<?php

namespace RZP\Models\GatewayStatus\Absence;

use RZP\Models\GatewayStatus\Absence;
use RZP\Trace\TraceCode;
use App;

class Processor
{
    protected $app;

    protected $repo;

    protected $trace;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->repo = $this->app['repo'];

        $this->trace = $this->app['trace'];
    }
    public function createAction(array $input)
    {
        $downWindow = (new Absence\Core)->create($input);

        return $downWindow->toArrayPublic();
    }

    public function editAction(string $id, array $input)
    {
        $downWindow = $this->repo->gateway_absence->findOrFailPublic($id);

        $downWindow = (new Absence\Core)->edit($downWindow, $input);

        return $downWindow->toArrayPublic();
    }

    public function deleteAction(string $id)
    {
        $downWindow = $this->repo->gateway_absence->findOrFailPublic($id);

        $this->repo->gateway_absence->deleteOrFail($downWindow);

        $this->trace->info(TraceCode::GATEWAY_ABSENCE_DELETE, ['id' => $id]);

        return ['message' => 'Gateway Absence successfully deleted'];
    }
}