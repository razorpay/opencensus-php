<?php

namespace RZP\Models\BankingAccount;

class OpsMxPocEmails
{
    public static array $mxPocEmails = [
        'debika.nath@cnx.razorpay.com',
        'srinivasa.rao@cnx.razorpay.com',
        'nuhaid.pasha@cnx.razorpay.com',
        'mohammed.ibrahim@cnx.razorpay.com',
    ];

    public function checkIfEmailInMxPocEmailsList(string $email): bool
    {
        return in_array($email, array_flip(OpsMxPocEmails::$mxPocEmails));
    }
}
