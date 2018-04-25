<?php

namespace RZP\Base\Database\LagChecker;

use Closure;

/**
 * Interface LagChecker
 *
 * @package RZP\Base\Database\LagChecker
 */
interface LagChecker
{
    /**
     * @param Closure $readPdo
     *
     * @return mixed
     */
    public function useReadPdoIfApplicable(Closure $readPdo);
}
