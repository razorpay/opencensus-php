<?php

namespace RZP\Services\Mock;

use RZP\Services\Razorflow as BaseRazorflow;

class Razorflow extends BaseRazorflow
{
    public function invokeSlashCommand(array $input, bool $throwExceptionOnFailure = false): array
    {
        return json_decode("{\n  \"body\": {\n    \"body\": \"Hello. Request accepted\",\n \"code\": 200\n}}", true);
    }
}
