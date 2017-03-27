<?php

namespace RZP\Mail\Payment;

class Metadata
{
    const CUSTOMER = [
        Event::AUTHORIZED => [
            'from' => 'care',
            'view' => [
                'html' => 'emails.payment.customer',
                'text' => 'emails.payment.customer_text'
            ]
        ],
        Event::REFUNDED => [
            'from'  => 'care',
            'view'  => [
                'html' => 'emails.refund.common',
            ],
        ],
        Event::FAILED_TO_AUTHORIZED => [
            'from'  => 'care',
            'view' => [
                'html'  => 'emails.payment.customer',
                'text'  => 'emails.payment.customer_text'
            ],
        ],
        Event::CARD_SAVED    => [
            'from' => 'care',
            'view' => [
                'emails.payment.cardsaving',
            ],
        ],
        Event::INVOICE_PAYMENT_AUTHORIZED => [
            'from' => 'care',
            'view' => [
                'html' => 'emails.invoice.customer.notification',
            ],
        ],
    ];

    const MERCHANT = [
        Event::CAPTURED => [
            'view' => [
                'html' => 'emails.payment.merchant',
                'text' => 'emails.payment.merchant_text'
            ],
        ],
        Event::REFUNDED => [
            'view' => [
                'html' => 'emails.refund.common',
            ],
        ],
        Event::FAILED_TO_AUTHORIZED => [
            'view' => [
                'html'  => 'emails.payment.failed_to_authorized',
                'text'  => 'emails.payment.failed_to_authorized_text',
            ],
        ],
        Event::INVOICE_PAYMENT_AUTHORIZED => [
            'view' => [
                'html' => 'emails.invoice.merchant.captured',
            ]
        ]
    ];
}
