<?php namespace App\Edge;

use Auth;
use Request;
use Session;

use App\Session\Entity as AppSession;

/**
 * handler class written for dashboard-backend decomp
 * which verifies whether edge has already authenticated the request
 * and syncs dashboard-backend sessions with edge
 */
class ValidateEdgeToken {
    const X_EDGE_TOKEN_REVOKED = 'X-Edge-Token-Revoked';

    /**
     * Verifies if the token is already revoked at edge.
     * @return bool true if token is already revoked at edge, false otherwise
     */
    public function verifyRevoked()
    {
        // will be false if header doesn't exist or set to false
        $is_token_revoked = filter_var(Request::header(self::X_EDGE_TOKEN_REVOKED), FILTER_VALIDATE_BOOLEAN);

        if ($is_token_revoked === true) {
            // remove user sessions since token is already revoked at edge
            $user = Auth::guard('user')->user();
            // user should not be empty, if empty consider dashboard session is revoked
            if (empty($user)) {
                return false; // returning false for now until edge becomes primary
            }

            (new AppSession())->deleteCurrentSessionForUser($user->id);
            app('request.ctx')->setShouldOverrideSession(false);

            return true;
        }

        return false;
    }
}
