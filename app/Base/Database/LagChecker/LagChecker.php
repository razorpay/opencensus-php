<?php

namespace RZP\Base\Database\LagChecker;

use Closure;

interface LagChecker
{
    public function useReadPdoIfApplicable(Closure $readPdo);
}
