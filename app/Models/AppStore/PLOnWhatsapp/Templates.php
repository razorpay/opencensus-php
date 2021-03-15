<?php

namespace RZP\Models\AppStore\PLOnWhatsapp;

class Templates
{
    const PL_WELCOME_MESSAGE_TEMPLATE = "PL_WELCOME_MESSAGE_TEMPLATE";

    const PL_WRONG_MESSAGE_TEMPLATE = "PL_WRONG_MESSAGE_TEMPLATE";

    const PL_CREATION_FAILED_MESSAGE_TEMPLATE = "PL_CREATION_FAILED_MESSAGE_TEMPLATE";

    const PL_CREATION_SUCCESS_MESSAGE_TEMPLATE = "PL_CREATION_SUCCESS_MESSAGE_TEMPLATE";

    const WHATSAPP_TEMPLATES = [
        self::PL_WELCOME_MESSAGE_TEMPLATE => 'Congratulations! You have successfully installed Razorpay Whatsapp Bot for Payment Links. Now, you can create payments links from Whatsapp directly.

To create a Payment Link:
Send _*Create <Amount>*_ to create a Payment Link
eg: _*Create 100*_ will create a Payment Link for INR 100',

        self::PL_WRONG_MESSAGE_TEMPLATE => 'Sorry! We could not understand the message.

To create a Payment Link:
Send _*Create <Amount>*_ to create a Payment Link
eg: _*Create 100*_ will create a Payment Link for INR 100',

        self:: PL_CREATION_FAILED_MESSAGE_TEMPLATE => '_Sorry! Payment Link creation failed. Please try again._

To create a Payment Link:
Send _*Create <Amount>*_ to create a Payment Link
eg: _*Create 100*_ will create a Payment Link for INR 100',

        self::PL_CREATION_SUCCESS_MESSAGE_TEMPLATE => '*Hurray! Payment Link has been successfully created*
_Share this link with your customers to start collecting payments_
- {{paymentLinkUrl}}',

    ];

}
