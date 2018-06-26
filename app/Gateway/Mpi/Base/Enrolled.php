<?php

namespace RZP\Gateway\Mpi\Base;

class Enrolled
{
    /**
     * Y = Authentication Available
     * N = Cardholder Not Participating
     * U = Unable To Authenticate
     * Note: “U” is used whether the Issuer’s
     * inability to authenticate the account is
     * due to technical difficulties or business
     * reasons.
     * If the value is not “Y”, MPI must not
     * create a PAReq.
     */

    /**
     * Means card is enrolled for 3dsecure.
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
}
