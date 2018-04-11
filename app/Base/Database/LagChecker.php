<?php

namespace RZP\Base\Database;

use Closure;

interface LagChecker
{
    public function useReadPdoIfApplciable(Closure $readPdo);
}
