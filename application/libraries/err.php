<?php

/**
 * Defines the constant error for the application to use.
 */
class ERR
{

    /**
     * We don't want the class to have objects.
     */
    private function __construct() {}
    
    const NO_ERROR = NULL;

    const SUCCESS = 0x0;

/**
 * User errors
 */
    const INVALID_PARAMETERS = 0x1;
    const NAME_1 = 'INVALID_PARAMETERS';
    const MSG_1 = "The parameters provided are invalid.";

    const INVALID_KEYS  = 0x2;
    const NAME_2 = 'INVALID_KEYS';
    const MSG_2 = 'Invalid key has been provided';

    const INVALID_CURRENCY = 0x3;
    const NAME_3 = 'INVALID_CURRENCY';

    const TOKEN_ALREADY_USED = 0x10;
    const NAME_10 = 'TOKEN_ALREADY_USED';
    const MSG_10 = "This token has alreay been used and hence expired.";

    const CVV_ALREADY_VERIFIED = 0x11;
    const NAME_11 = 'CVV_ALREADY_VERIFIED';
    const MSG_11 = "The CVV of the card has already been verified previously.";



/**
 * System errors
 */
    const DB_PROBLEM = 0x101;
    const NAME_4 = 'DB_PROBLEM';
    const MSG_4 = "There is a problem with database";

    const INTERNAL_SERVER_ERROR = 0x102;
    const NAME_5 = 'INTERNAL_SERVER_ERROR';
    const MSG_5 = "There is a problem with the server.";

    private static $err = array();
    private static $ix  = 0;

    public static function trigger($err, $info)
    {
        $msg_var = 'MSG_' . dechex($err);
        $msg = constant('self::'.$msg_var);

        array_push(self::$err, array($err, $msg, $info));
        self::$ix++;

        return $err;
    }

    public function __toString()
    {
        ;
    }

    public static function last_error()
    {
        if (count(self::$err) !== 0)
        {
            return self::$err[self::$ix-1][0];
        }
    }

    public static function print_last_error()
    {
        if (self::$ix == 0)
            return;

        $err = self::$err[self::$ix-1];
        $code = $err[0];
        $name = constant('self::'.'NAME_' . dechex($err));
        $generic_msg = constant('self::'.'MSG_' . dechex($err));
        $specific_msg = $err[1];

        echo 'Err Code: ' + dechex($code) + '\n';
        echo 'Err Name: ' + $name + '\n';
        echo 'Err Generic Msg: ' + $generic_msg + '\n';
        echo 'Err Specific Msg: ' + $specific_msg + '\n';
    }

    public static function last_error_str()
    {
        if (self::$ix == 0)
            return;

        $err = self::$err[self::$ix];
        $code = $err[0];
        $name = constant('self::'.'NAME_' . dechex($err));
        $generic_msg = constant('self::'.'MSG_' . dechex($err));
        $specific_msg = $err[1];

        $str = 'Err Code: ' + dechex($code) + '\n';
        $str += 'Err Name: ' + $name + '\n';
        $str += 'Err Generic Msg: ' + $generic_msg + '\n';
        $str += 'Err Specific Msg: ' + $specific_msg + '\n';

        return $str;
    }
    public static function invalid_keys($keys)
    {
        $invalid_keys = implode(', ', $keys);
        
        $msg = $invalid_keys . ' is/are not valid key(s).';
        return self::trigger(self::INVALID_KEYS, $msg);
    }

    public static function invalid_parameters($invalid_parameters)
    {
        return self::trigger(self::INVALID_PARAMETERS, $invalid_parameters);
    }

    public static function invalid_currency($msg)
    {
        return self::trigger(self::INVALID_CURRENCY, $msg);
    }

}

