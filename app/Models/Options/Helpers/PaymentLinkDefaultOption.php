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

    // Refer https://jsonbin.io/5dc2bda6a5f7237736c23e21/11 for JSON structure
    // Few fields are commented below. Do not remove fields.
    // Change if a default value is needed in them in future.
    public function get()
    {
        return array (
//                    'org_id' => '100000000',
            'name'        => '',
            'description' => '',
            'checkout' =>
                array (
                    'prefill' =>
                        array (
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
                            'card' => true,
                            'netbanking' => true,
                            'wallet' => true,
                            'upi' => true,
                            'emi' => true,
                            'upi_intent' => false,
                            'qr' => false
                        ),
                    'features' =>
                        array (
                            'cardsaving' => true,
                        ),
                    'readonly' =>
                        array (
                            'contact' => false,
                            'email' => false,
                            'name' => false
                        ),
                    'hidden' =>
                        array (
                            'contact' => false,
                            'email' => false
                        ),
                    'theme' =>
                        array (
                            'hide_topbar' => false,
                            'image_padding' => true,
                            'image_frame' => true,
                            'close_button' => true,
                            'close_method_back' => false,
//                                    'color' => 'Use if sent : Merchant Profile',
//                                    'backdrop_color' => 'Use if sent : Merchant Profile',
                            'debit_card' => false
                        ),
                    'modal' =>
                        array (
                            'confirm_close' => false,
//                                    'ondismiss' => 'Use if sent : function()',
//                                    'onhidden' => 'Use if sent : function()',
                            'escape' => true,
                            'animation' => true,
                            'backdropclose' => false,
                            'handleback' => true,
                            'select_partial' => false
                        ),
                    'partial_payment' =>
                        array (
                            'min_amount_label' => 'Minimum first amount',
                            'total_amount_label' => 'Make payment in parts',
                            'total_amount_description' => 'Pay some now and remaining later'
                        ),
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
                            'razorpay_branding' => true,
                            'security_branding' => true
                        ),
                    'label' =>
                        array (
                            'receipt' 				 => 'Receipt',
                            'description' 		     => 'Payment For',
                            'amount_payable' 		 => 'Amount Payable',
                            'amount_paid'    		 => 'Amount Paid',
                            'partial_amount_due'     => 'Due',
                            'partial_amount_paid'    => 'Paid',
                            'expire_by'  		     => 'Expire By',
                            'expired_on'             => 'Expired On'
                        ),
                    'show_preferences' =>
                        array (
                            'issued_to'     => true
                        )
                )
        );
    }
}