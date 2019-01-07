<?php

namespace RZP\Models\P2p\Vpa;

class Permissions
{
    const CUSTOMER              = 'customer';
    const BENEFICIARY           = 'beneficiary';

    // Whether the vpa can send a pay request
    const INITIATE_PAY          = 'initiate_pay';
    // Whether the vpa can send a collect request
    const INITIATE_COLLECT      = 'initiate_collect';
    // Whether the vpa can accept incoming pay request
    const ACCEPT_PAY            = 'accept_pay';
    // Whether the vpa can handle incoming collect request
    const ACCEPT_COLLECT        = 'accept_collect';

    /**
     * List of default enabled permissions for the owner of vpa
     * Any permission not listed here will be considered disabled
     *
     * @var array
     */
    protected static $defaults = [
        self::CUSTOMER => [
            self::ACCEPT_PAY,
            self::ACCEPT_COLLECT,
            self::INITIATE_PAY,
            self::INITIATE_COLLECT,
        ],
        self::BENEFICIARY => [
            // No Permissions are given to beneficiary
        ],
    ];

    protected static $bitIndex = [
        self::ACCEPT_PAY          => 0,
        self::ACCEPT_COLLECT      => 1,
        self::INITIATE_PAY        => 2,
        self::INITIATE_COLLECT    => 3,
    ];

    /**
     * @param string $ownerType
     * @return array
     * @throws LogicException
     */
    public static function getDefaultPermission(string $ownerType): array
    {
        if (isset(self::$defaults[$ownerType]) === false)
        {
            throw new LogicException(
                        'Invalid owner type for permissions',
                        null,
                        [
                            'owner_type' => $ownerType,
                        ]);
        }

        $permissions = [];

        foreach (self::$bitIndex as $permission => $index)
        {
            $permissions[$permission] = in_array($permission, self::$defaults[$ownerType], true);
        }

        return $permissions;
    }

    /**
     * Return default bit mask for owner
     * @param string $ownerType
     * @throws LogicException
     */
    public static function getDefaultBitmask(string $ownerType): int
    {
        return self::generateBitmask(self::getDefaultPermission($ownerType));
    }

    /**
     * Validate for valid permission
     *
     * @param string $permission
     * @throws BadRequestValidationFailureException
     */
    public static function validatePermission(string $permission)
    {
        if (isset(self::$bitIndex[$permission]) === false)
        {
            throw new BadRequestValidationFailureException(
                'Invalid permission', null, ['permission' => $permission]);
        }
    }

    /**
     * Return list of enabled permissions for given bitmask
     *
     * @param int $bitmask
     * @return array
     */
    public static function getPermissions(int $bitmask): array
    {
        $permissions = [];

        foreach (self::$bitIndex as $permission => $index)
        {
            $permissions[$permission] = self::isPermissionEnabled($bitmask, $permission);
        }

        return $permissions;
    }

    /**
     * Generate valid bit mask for permissions array
     *
     * @param array $permissions
     *              [
     *                  Permission1 => true,
     *                  Permission2 => false
     *              ]
     * @param int $bitmask
     * @return int
     */
    public static function generateBitmask(array $permissions, int $bitmask = 0): int
    {
        foreach (self::$bitIndex as $permission => $bitIndex)
        {
            if (array_key_exists($permission, $permissions) === true)
            {
                $value = filter_var($permissions[$permission], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;

                $bitmask = (
                    ($bitmask & ~ (1 << $bitIndex)) |
                    ($value << $bitIndex)
                );
            }
        }

        return $bitmask;
    }

    /**
     * Check if bitmask has given permission enabled
     *
     * @param int $bitmask
     * @param string $permission
     * @return bool
     * @throws BadRequestValidationFailureException
     */
    public static function isPermissionEnabled(int $bitmask, string $permission)
    {
        self::validatePermission($permission);

        $value = ($bitmask & (1 << self::$bitIndex[$permission]));

        return ($value === 0) ? false : true;
    }

    /**
     * Checks if the permission array passed in the Api is valid or not
     * @param array $permissions
     * @throws BadRequestValidationFailureException
     */

    public static function validatePermissions(array $permissions)
    {
        foreach ($permissions as $permission => $value)
        {
            self::validatePermission($permission);
        }
    }
}
