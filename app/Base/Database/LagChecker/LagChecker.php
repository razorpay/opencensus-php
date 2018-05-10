<?php

namespace RZP\Base\Database\LagChecker;

/**
 * Interface LagChecker
 *
 * @package RZP\Base\Database\LagChecker
 */
interface LagChecker
{
    /**
     * @param  \PDO|Closure $readPdo
     * @return \PDO|null
     */
    public function useReadPdoIfApplicable($readPdo);
}
