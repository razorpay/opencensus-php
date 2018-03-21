<?php
error_reporting(E_ALL);

    function base62($num)
    {
        $index = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

        $res = '';
        do {
            $res = $index[$num % 62] . $res;
            $num = intval($num / 62);
        } while ($num);

        return $res;
    }

        // Timestmap of 1st Jan 2014!!
        // 1388534400
        $ts1stJan2014 = 1388534400;

        // Get hex id
        $nanotime = $_GET['time'];
        $nanotime = substr($nanotime, 0, 16);

        $nanotime = (int) hexdec($nanotime);

        // Subtract nanotime of 1st Jan 2014
        $nanotime -= $ts1stJan2014*1000*1000*1000;

        if (isset($_GET['add']))
        {
            $addtime = (int) $_GET['add'];
            $nanotime += $addtime *1000*1000*1000;
        }

        // Convert to base 62
        $b62 = base62($nanotime);

        // Generate 3 random bytes, convert to hex and then to dec
        $dec = hexdec(bin2hex(random_bytes(3)));
        // Convert the random decimal generated to base 62
        $rand = base62($dec);

        // Only 4 base 62 digits are needed, so cutoff any more.
        if (strlen($rand) > 4)
        {
            $rand = substr($rand, 0, 4);
        }

        // Combine the base 62 nanotime with 4 base 62 digits
        // and create a unique identifier
        $id = $b62 . $rand;

        echo $id;
