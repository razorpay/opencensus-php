<?php

namespace RZP\Models\BankingAccount;

class OpsMxPocEmails
{
    public static array $mxPocEmails = [
        'debika.nath@cnx.razorpay.com',
        'srinivasa.rao@cnx.razorpay.com',
        'nuhaid.pasha@cnx.razorpay.com',
        'mohammed.ibrahim@cnx.razorpay.com',
        'tasmiya.mohammadi@cnx.razorpay.com',
        'abhishek.sriram@cnx.razorpay.com',
        'shreyash.patil@cnx.razorpay.com',
        'prateek.prateek@cnx.razorpay.com',
        'saurav.gupta@cnx.razorpay.com',
        'vinay.rajj@ie.razorpay.com',
        'vaishnavi.kjoi@ie.razorpay.com',
        'harshita.nandini@ie.razorpay.com',
        'deepa.m@ie.razorpay.com',
        'samanvi.suvarna@ie.razorpay.com',
        'daniel.gilbert@ie.razorpay.com',
        'rasheed.abrar@ie.razorpay.com',
        'kavitha.s@ie.razorpay.com'
    ];

    public function checkIfEmailInMxPocEmailsList(string $email): bool
    {
        return in_array($email, array_flip(OpsMxPocEmails::$mxPocEmails));
    }
}
