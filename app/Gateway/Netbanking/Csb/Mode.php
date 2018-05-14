<?php

namespace RZP\Gateway\Netbanking\Csb;

class Mode
{
    /**
     * This Mode class developed as per API contract.
     * @see https://drive.google.com/file/d/0B1kf6HOmx7JBQVg3dUgtN2tYN3dMN2ZGNjh4VERVbXh4MllB/view?usp=sharing
     */

    /**
     * Mode used to make a payment
     */
    const PAY           = 'P';

    /**
     * Mode used to verify a payment
     */
    const VERIFY        = 'V';

    /**
     * Mode used to verify a payment without TID
     */
    const VERIFY_WO_TID = 'S';
}
