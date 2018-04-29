<?php

namespace RZP\Gateway\Netbanking\Csb;

class Status
{
    /**
     * Status class developed as per API contract.
     * @see https://drive.google.com/file/d/0B1kf6HOmx7JBQVg3dUgtN2tYN3dMN2ZGNjh4VERVbXh4MllB/view?usp=sharing
     */

    /**
     * Transaction successful
     */
    const SUCCESS   = 'Y';

    /**
     * Transaction failure
     */
    const FAILURE   = 'N';

    /**
     * Duplicate Transaction
     */
    const DUPLICATE = 'F';
}
