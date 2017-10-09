<?php

namespace RZP\Models\Admin\Admin\Token;

use Hash;
use RZP\Models\Admin\Base;
use RZP\Exception;
use Carbon\Carbon;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Error\ErrorCode;

class Repository extends Base\Repository
{
    protected $entity = 'admin_token';

    /**
     * Since token is encrypted we need to check with principal
     * and validate before fetching token instance.
     * @param $token
     * @return mixed
     */
    public function findOrFailToken($token)
    {
        // Token = bearertoken + TokenId(principal)(14chars).
        $principalLength = UniqueIdEntity::ID_LENGTH;

        $principal = substr($token, -$principalLength);

        $bearerToken = substr($token, 0, -$principalLength);

        // Current Timestamp
        $currentTimestamp = Carbon::now()->getTimestamp();

        $mode = $this->auth->getLiveConnection();

        $token =  $this->connection($mode)
                       ->newQuery()
                       ->with('admin')
                       ->where(Entity::ID, '=', $principal)
                       ->where(Entity::EXPIRES_AT, '>', $currentTimestamp)
                       ->firstOrFailPublic();

        if (Hash::check($bearerToken, $token->getToken()) === true)
        {
            return $token;
        }
        else
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ADMIN_TOKEN_MISMATCH, null, []);
        }
    }
}
