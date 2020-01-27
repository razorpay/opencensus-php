<?php

namespace RZP\Jobs;

use RZP\Models;


class MdrFixJob extends Job
{
    protected $input;

    public function __construct(string $mode = null, $input)
    {
        parent::__construct($mode);

        $this->input = $input;
    }

    public function handle()
    {
        parent::handle();

        try
        {
            $service = new Models\Transaction\Service;

            $service->processMdrAdjustmentRow($this->input);
        }
        catch (\Throwable $e)
        {

        }
        finally
        {
            $this->delete();

        }

    }
}
