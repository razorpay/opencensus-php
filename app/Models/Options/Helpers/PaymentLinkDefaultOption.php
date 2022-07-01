<?php


namespace RZP\Models\Options\Helpers;

/**
 * This class defines the default options json with values needed for Payment Links.
 *
 * Class PaymentLinkDefaultOption
 * @package RZP\Models\Options\Helpers
 */
class PaymentLinkDefaultOption implements DefaultOption
{

    // Refer https://jsonbin.io/5dc2bda6a5f7237736c23e2/1 for JSON structure
    // Few fields are commented below. Do not remove fields.
    // Change if a default value is needed in them in future.
    public function get()
    {
        return array (
//                    'org_id' => '100000000',
            'checkout' =>
                array (
                    'name'        => '',
          //          'remember_customer'        => '1',
                    'description' => '',
                    'first_payment_min_amount' => 'Minimum Amount Due',
                    'prefill' =>
                        array (
                                      'select_partial' => "0",
                                      'select_full' => "0",
//                                    'method' => 'Use if sent',
//                                    'amount' => 'Use if sent',
//                                    'wallet' => 'Use if sent',
//                                    'provider' => 'Use if sent',
//                                    'name' => 'Use if sent',
//                                    'contact' => 'Use if sent',
//                                    'email' => 'Use if sent',
//                                    'vpa' => 'Use if sent',
                            'card' =>
                                array (
//                                            'number' => 'Use if sent',
//                                            'cvv' => 'Use if sent',
//                                            'expiry' => 'Use if sent',
//                                            'prefill_bank' => 'Use if sent',
                                ),
                        ),
                    'method' =>
                        array (
                            'card' => "1",
                            'netbanking' => "1",
                            'wallet' => "1",
                            'upi' => "1",
                            'emi' => "1",
                            'upi_intent' => "0",
                            'qr' => "1",
                            'bank_transfer' => "1"
                        ),
                    'features' =>
                        array (
                            'cardsaving' => "1",
                        ),
                    'readonly' =>
                        array (
                            'contact' => "0",
                            'email' => "0",
                            'name' => "0"
                        ),
                    'hidden' =>
                        array (
                            'contact' => "0",
                            'email' => "0"
                        ),
                    'theme' =>
                        array (
                            'hide_topbar' => "0",
                            'image_padding' => "1",
                            'image_frame' => "1",
                            'close_button' => "0",
                            'close_method_back' => "0",
//                                    'color' => 'Use if sent : Merchant Profile',
//                                    'backdrop_color' => 'Use if sent : Merchant Profile',
                            'debit_card' => "0"
                        ),
                    'modal' =>
                        array (
                            'confirm_close' => "0",
//                                    'ondismiss' => 'Use if sent : function()',
//                                    'onhidden' => 'Use if sent : function()',
                            'escape' => "1",
                            'animation' => "1",
                            'backdropclose' => "0",
                            'handleback' => "1",
                            'select_partial' => "0"
                        ),
                    'partial_payment' =>
                        array (
                            'min_amount_label' => 'Minimum first amount',
                            'partial_amount_label' => 'Make payment in parts',
                            'partial_amount_description' => 'Pay some now and the remaining later',
                            'full_amount_label' => 'Pay in full'
                        ),
                    'config' =>
                        array ()
                ),
            'order' =>
                array (
                    'bank_account' =>
                        array (
//                                    'bankcode' => 'Use if sent',
//                                    'account_number' => 'Use if sent',
//                                    'name' => 'Use if sent',
//                                    'ifsc' => 'Use if sent',
                        ),
                ),
            'hosted_page' =>
                array (
                    'footer' =>
                        array (
                            'razorpay_branding' => "1",
                            'security_branding' => "1"
                        ),
                    'label' =>
                        array (
                            'receipt' 				 => 'RECEIPT',
                            'description' 		     => 'PAYMENT FOR',
                            'amount_payable' 		 => 'AMOUNT PAYABLE',
                            'amount_paid'    		 => 'AMOUNT PAID',
                            'partial_amount_due'     => 'DUE',
                            'partial_amount_paid'    => 'PAID',
                            'expire_by'  		     => 'EXPIRE BY',
                            'expired_on'             => 'EXPIRED ON'
                        ),
                    'show_preferences' =>
                        array (
                            'issued_to'     => "1"
                        ),
                    'enable_embedded_checkout' => "0"
                )
        );
    }
}
