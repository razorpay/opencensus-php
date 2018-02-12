<?php

namespace RZP\Gateway\Netbanking\Csb;

class Constant
{
    /**
     * This Constants class developed as per API contract.
     * @see https://drive.google.com/file/d/0B1kf6HOmx7JBQVg3dUgtN2tYN3dMN2ZGNjh4VERVbXh4MllB/view?usp=sharing
     */

    /**
     * These values are confirmed to be used in UAT.
     * TODO: Check if they are the same for live as well.
     */
    const CHNPGSYN  = 'RazorPay';
    const CHNPGCODE = '000000RazorPwy018126';

    const BANK_ID   = 'CSB';
}
