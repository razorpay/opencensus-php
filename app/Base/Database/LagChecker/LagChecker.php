<?php

namespace RZP\Base\Database\LagChecker;

use Closure;

interface LagChecker
{
    public function useReadPdoIfApplciable(Closure $readPdo);
}
