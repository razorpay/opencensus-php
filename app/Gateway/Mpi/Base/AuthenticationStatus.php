<?php

namespace RZP\Gateway\Mpi\Base;

class AuthenticationStatus
{
    /**
     * Successfully authenticated
     */
    const Y = 'Y';

    /**
     * Cannot be authenticated due to technical or business reasons.
     */
    const U = 'U';

    /**
     * Means card is not enrolled for 3dsecure.
     */
    const N = 'N';

    /**
     * Authentication failed.
     */
    const F = 'F';
}
