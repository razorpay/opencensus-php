<?php

namespace App\Services\Uuid;

class Generator
{
    /**
     * Generate the Uuid
     *
     * @return string
     */
    public function generate()
    {
        // Timestmap of 1st Jan 2014!!
        // 1388534400
        $ts1stJan2014 = 1388534400;

        // Get current nanotime from 1st Jan 1970
        $nanotime = $this->getNanotimeInteger();

        // Subtract nanotime of 1st Jan 2014
        $nanotime -= $ts1stJan2014*1000*1000*1000;

        // Convert to base 62
        $b62 = $this->base62($nanotime);

        // Generate 3 random bytes, convert to hex and then to dec
        $dec = hexdec(bin2hex(openssl_random_pseudo_bytes(5)));

        // Convert the random decimal generated to base 62
        $rand = $this->base62($dec);

        // Only 4 base 62 digits are needed, so cutoff any more.
        if (strlen($rand) > 4)
            $rand = substr($rand, 0, 4);

        // Combine the base 62 nanotime with 4 base 62 digits
        // and create a unique identifier
        $id = $b62 . $rand;

        assert(strlen($id) === 14);

        return $id;
    }

    protected function getNanotimeInteger()
    {
        $cmd = '';

        if (PHP_OS === 'Darwin')
        {
            $cmd = '/usr/local/opt/coreutils/libexec/gnubin/';
        }

        $cmd .= 'date +%s%N';
        exec($cmd, $nanotime, $status);

        return $nanotime[0];
    }

    protected function base62($num)
    {
        $index = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

        $res = '';
        do {
            $res = $index[$num % 62] . $res;
            $num = intval($num / 62);
        } while ($num);

        return $res;
    }
}
