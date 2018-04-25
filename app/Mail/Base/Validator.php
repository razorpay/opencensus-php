<?php

namespace RZP\Mail\Base;

use EmailValidator\Validator as BaseValidator;

class Validator extends BaseValidator
{
    /**
     * Test email address for MX or A or AAAA records in this order
     * As per RFC https://tools.ietf.org/html/rfc5321#section-5, the SMTP
     * client should fallback to an A or AAAA record if present.
     * By only doing MX checks we are not sending mails to valid
     * recipient addresses.
     *
     * @param string $email Address
     * @return boolean|null
     */
    public function hasMx($email)
    {
        if ($this->isEmail($email) === false)
        {
            return null;
        }

        $hostname = $this->hostnameFromEmail($email);

        if ($hostname !== null)
        {
            return ((checkdnsrr($hostname, 'MX') === true) or
                (checkdnsrr($hostname, 'A') === true) or
                (checkdnsrr($hostname, 'AAAA') === true));
        }

        return null;
    }

    /**
     * Get the hostname form an email address
     *
     * @access private
     * @param string $email Address
     * @return string|null
     */
    private function hostnameFromEmail($email)
    {
        $parts = explode('@', $email);

        if (count($parts) == 2) {
            return strtolower($parts[1]);
        }

        return null;
    }
}
