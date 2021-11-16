<?php

namespace App\Merchant\Notifications;

use App\MerchantDetails;

class Constants
{
    const NOTIFICATIONS = [
        [
            'title'       => 'Settlements on Hold',
            'description' => 'Settlements on Hold: Your settlements are under review due to regulatory requirements. Please provide clarification on the email received on your registered email ID to resolve the issue',
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/rtb_announcement.svg',
            'track_event' => true,
            'id'          => 'merchant-risk-action-FOH',
            'filters'     => [
                'tags' => ["merchant_risk_action_foh","mra_foh"]
            ],
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Check Now!',
                    'url'   => '/profile',
                ],
            ],
        ],
        [
            'title'       => 'Account Disabled',
            'description' => 'Account Disabled: Your account has been disabled as per regulatory requirements. Please find more details on the email received on your registered email',
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/rtb_announcement.svg',
            'track_event' => true,
            'id'          => 'merchant-risk-action-disabled',
            'filters'     => [
                'tags' => ["merchant_risk_action_disabled","mra_disabled"]
            ],
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Know More',
                    'url'   => '/profile',
                ],
            ],
        ],
        [
            'title'       => 'Pay your vendors in seconds',
            'description' => 'Pay upto 300 vendor invoices free every month.',
            'start_ts'    => 1608633480,
            'end_ts'      => 1624358771,
            'icon'        => 'https://cdn.razorpay.com/pg_announcement_icon.svg',
            'track_event' => true,
            'id'          => 'DEC20-VP-C1',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Learn More',
                    'url'   => 'https://x.razorpay.com/vendor-payments',
                ]
            ],
            'filters'     => [
                'experiments'         => ['show_rx_vp_announcement_2'],
            ],
        ],
        [
            'title'       => 'Pay your vendors in seconds',
            'description' => 'Upload invoices and pay vendors and TDS automatically.',
            'start_ts'    => 1604904751,
            'end_ts'      => 1612084863,
            'icon'        => 'https://cdn.razorpay.com/pg_announcement_icon.svg',
            'track_event' => true,
            'id'          => 'NOV20-VP-C1',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Learn More',
                    'url'   => 'https://x.razorpay.com/vendor-payments',
                ]
            ],
            'filters'     => [
                'experiments'         => ['show_rx_vp_announcement'],
            ],
        ],
        [
            'title'       => '2 Step Verification',
            'description' => 'Enable 2 step verification with SMS based OTP along with user credentials to add additional security to your account.',
            'start_ts'    => 1588876200,
            'end_ts'      => 1617167373,
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/2fa.svg',
            'track_event' => true,
            'id'          => 'TwoStepVerification2020',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Enable Now',
                    'url'   => '/profile',
                ],
                [
                    'type'  => 'primary-inverted',
                    'label' => 'Know More',
                    'url'   => 'https://razorpay.com/docs/payment-gateway/dashboard-guide/my-account/#set-up-two-factor-authentication',
                ]
            ],
        ],
        [
            'title'       => 'Introducing Payment Buttons',
            'description' => 'Start accepting payments on your website or blog in less than 5 minutes. No coding needed.',
            'start_ts'    => 1598941800,
            'end_ts'      => 1617167373,
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/payment-button.svg',
            'track_event' => true,
            'id'          => 'paymentButton_GTM',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Try Now',
                    'url'   => '/paymentbuttons/new',
                ],
                [
                    'type'  => 'primary-inverted',
                    'label' => 'Know More',
                    'url'   => 'https://betasite.razorpay.com/docs/pb-index-true/payment-button',
                ]
            ],
            'filters'     => [
                'activated' => 1,
            ]
        ],
        // [
        //     'id'          => 'SHOW_CREDIT_SCORE',
        //     'title'       => 'Free Credit Score!',
        //     'description' => 'Click Here to get your credit score along with the credit report for FREE!',
        //     'start_ts'    => 1604320769,
        //     'end_ts'      => 1617167373,
        //     'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/badge.svg',
        //     'buttons'     => [
        //         [
        //             'type'  => 'button',
        //             'label' => 'Get Free Credit Report',
        //             'url'   => '/dashboard#creditscore',
        //         ],
        //     ],
        //     'filters'     => [
        //         'features'  => ['show_credit_score'],
        //         'role'  => ['owner'],
        //     ],
        // ],
        [
            'title'       => 'You\'re all set to accept payments',
            'description' => 'You\'ve successfully unlocked free payments for upto ₹2,00,000! To avail this offer, complete your first transaction before 16th of November',
            'start_ts'    => 1604320769,
            'end_ts'      => 1617167373,
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/unlock.svg',
            'track_event' => true,
            'id'          => 'NOV20-RZP-FESTIVEOFFER',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Accept Payments',
                    'url'   => '',
                    'id'    => 'NOV20-RZP-FESTIVEOFFER-BUTTON'
                ],
                [
                    'type'  => 'primary-inverted',
                    'label' => 'Know More',
                    'url'   => 'https://razorpay.com/links/festive-campaign-terms-conditions',
                ]
            ],
            'filters'     => [
                'campaigns' => ['UNLOCKFEST'],
            ]
        ],
        // [
        //     'title'       => 'Boost International Sales With PayPal',
        //     'description' => 'Get upto 20% higher international success rates as well at T+1 settlement with PayPal',
        //     'start_ts'    => 1605160337,
        //     'end_ts'      => 1607752337,
        //     'icon'        => 'https://cdn.razorpay.com/static/assets/International_globe.png',
        //     'track_event' => true,
        //     'id'          => 'DEC20-PayPal-GTM',
        //     'buttons'     => [
        //         [
        //             'type'  => 'button',
        //             'label' => 'Enable Now',
        //             'url'   => '/config',
        //         ],
        //         [
        //             'type'  => 'primary-inverted',
        //             'label' => 'Know More',
        //             'url'   => 'https://razorpay.com/docs/payment-gateway/payment-methods/paypal/',
        //         ],
        //     ],
        //     'filters'     => [
        //         'features'  => ['paypal_gtm_notification'],
        //     ],
        // ],
        [
            'title'       => 'Get ₹10,000 PayPal FREE Credits',
            'description' => 'Get ₹10K International Free Credits on PayPal. Enjoy 20% higher conversion, T+1 settlement and activation within 24hrs. TnCs apply.',
            'start_ts'    => 1607409081,
            'end_ts'      => 1609417824,
            'icon'        => 'https://cdn.razorpay.com/static/assets/International_globe.png',
            'track_event' => true,
            'id'          => 'DEC20-PayPal-GTM',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Enable Now',
                    'url'   => '/config',
                ],
                [
                    'type'  => 'primary-inverted',
                    'label' => 'Know More',
                    'url'   => 'https://lp.razorpay.com/links/international-free-credits-paypal',
                ],
            ],
            'filters'     => [
                'features'  => ['paypal_gtm_notification'],
            ],
            'inverseFilters'     => [
                'experiments'         => ['whats-new-dec-2020'],
            ],
        ],
        [
            'title'       => 'Easily Update Your Bank Account',
            'description' => 'In case you want to update your bank account, you can do so directly from your Razorpay account settings',
            'icon'        => '/dist/css/assets/ic_building.svg',
            'track_event' => true,
            'id'          => 'NOV20-PG-BANKUPDATE',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Try Now',
                    'url'   => '/profile#request-bank-account-change',
                ],
                [
                    'type'  => 'primary-inverted',
                    'label' => 'Learn More',
                    'url'   => 'https://razorpay.com/docs/payment-gateway/dashboard-guide/profile/#change-bank-account-details',
                ],
            ],
            'start_ts'    => 1604925051,
            'end_ts'      => 1617193851,
            'filters'     => [
                'activation_status' => ['activated'],
                'role' => ['owner', 'admin'],
            ],
        ],
        [
            'title'       => 'Festive Special: Exclusive Offer For You',
            'description' => "Get ₹5,00,000 of free credits & 3 months of Opfin’s Payroll software for free.",
            'icon'        => '/dist/css/assets/products/opfin.svg',
            'id'          => 'Nov20-Opfin-NitroV3',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Learn More',
                    'url'   => '',
                    'id'    => 'announcement-Nov20-Opfin-NitroV3-cta1',
                ],
            ],
            'start_ts'    => 1609308877,
            'end_ts'      => 1623994200,
            'filters'     => [
                'experiments_with_variant'  => ['rx_opfin_announcement_v2' => 'cohort-4'],
            ],
        ],
        [
            'id'          => 'whats-new-upi-pl-jan2021',
            'title'       => 'You can now create & send UPI Payment Links',
            'description' => 'UPI Payment Link is an activated link that enables the user to complete successful payments by only entering their UPI PIN.',
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/payment-links.svg',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Try Now',
                    'url'   => '/paymentlinks/new?link_type=upi',
                ],
                [
                    'type'  => 'primary-inverted',
                    'label' => 'Read More',
                    'id'    => 'announcement-details-l2',
                    'url'   => '/announcements/whats-new-upi-pl-jan2021/'
                ],
            ],
            'start_ts'    => 1606707034,
            'end_ts'      => 1617193851,
            'filters'     => [
                'experiments'         => ['whats-new-dec-2020'],
            ],
            'l2_content'  => [
                'content'     => "<div class='title'><img src='https://cdn.razorpay.com/static/assets/notifs/payment-links.svg' width='32px' /><p >Save up to 1% on transaction cost with UPI Payment Links</p></div><div> <b>How does UPI Payment Link work?</b><div> Well, all that you read brings you to another question – how does the entire thing work? The GIF attached below explains the steps involved.</div> <div class='image'><img src='/dist/css/assets/whats-new/payment-links-flow.gif' /></div><p>The flow is as simple as it can be. Here’s an overview:</p><ol><li>The business sends a link of their desired amount. Let’s say INR 100.</li><li>The customer receives the link in their phone via SMS or email</li><li>They open the link</li><li>The customer is asked to choose their UPI app of choice</li><li> Lastly, the customer enters the PINs to make a payment and voila – money in the bank!</li></ol><div class='paragraph'> Just to make it obvious, the customer did not have to enter their VPA at any point of time. Yes, that’s right – you can make UPI payments without a VPA.</div><div> <b>Please note:</b> This is what we have with Razorpay Payment Links otherwise – an expiry date, a paid confirmation page and a shareable short URL.</div><div class='image'> <img src='https://cdn.razorpay.com/static/assets/whats-new/pl-payment-complete.png' /></div><div> This is essential to the complete experience – a payment reference that the merchant and the customer can refer for any form of reconciliation or disputes.</div><div class='paragraph'> <b>Use cases of UPI Payment Links</b><p> After getting a detailed understanding, let’s talk about the use cases, shall we?</p><p>This is a super handy tool for you if you relate one of the scenarios:</p></div> <b>Lending business</b><div> You might be having a tough time chasing after your customers to collect repayments for existing loans. With UPI Payment Links, it is easy to remember and collect your payments with a single click.</div><div class='paragraph'> <b>Alternatives to GPay / PayTM</b><div> You might have customers asking you if they can send the money on GPay or PayTM or some other UPI App. Whatever be their reason – lack of immediate access to other modes, comfort in PSP Apps or even getting cash backs – UPI Payment Links makes this process convenient for you – to collect and reconcile on the same dashboard.</div></div><div> <b>Better success rates</b><p> UPI Payment Links reduces the step of entering the VPA and sending a collect request. This is not the only benefit, the link opens the app directly and payment is processed via the PSP app itself.</p></div><div class='paragraph'> Here is a quick <a href='https://cdn.razorpay.com/static/assets/notifs/payment-links.svg' target='_blank'> UPI Payment Link </a> on which you can try to attempt a payment.</div><div>To learn more about UPI payment links click on the read more button below</div></div>",
                'buttons'     => [
                    [
                        'type'  => 'button',
                        'label' => 'Try Now',
                        'url'   => '/paymentlinks/new?link_type=upi',
                    ],
                    [
                        'type'  => 'primary-inverted',
                        'label' => 'Read More',
                        'url'   => 'https://razorpay.com/blog/create-upi-payment-links/',
                    ],
                ],
            ],
        ],
        [
            'id'          => 'whats-new-subs-btn-jan2021',
            'title'       => 'Introducing Subscriptions on Payment Button',
            'description' => 'Now collect one time and subscription payments with a single button on your website. Creating and adding this button on your website takes less than 5 minutes and involves no coding.',
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/payment-button.svg',
            'video_url'   => 'https://www.youtube.com/embed/RlUVGyMs8B8',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Try Now',
                    'url'   => '/subscriptions',
                ],
                [
                    'type'  => 'primary-inverted',
                    'label' => 'Read More',
                    'id'    => 'announcement-details-l2',
                    'url'   => '/announcements/whats-new-subs-btn-jan2021/'
                ],
            ],
            'start_ts'    => 1606707034,
            'end_ts'      => 1617193851,
            'filters'     => [
                'experiments'         => ['whats-new-dec-2020'],
            ],
            'l2_content'  => [
                'content'     => "<div class='title'> <img src='https://cdn.razorpay.com/static/assets/notifs/payment-button.svg' width='32px' /><p>Introducing Subscriptions on Payment Button</p></div><div><div>Razorpay was the first Indian player to launch Subscriptions for the domestic market. We want to do it again. We want to punch a bit higher this time. We are now the first Indian player to launch a no-code subscription button for the Indian market.</div><div class='paragraph'> <b>What is Subscriptions on Payment button?</b><p>Subscriptions on payment button lets one collect subscriptions or single payments by providing a convenient snippet of code that can be pasted on any webpage without any specific sort of integration requirement. Razorpay subscription button is a simple no-code tool that lets you collect subscription payments from your customers without having to integrate APIs on subscriptions. You can even collect payments as low as INR 10!</p></div><div> <b>How does it help your business?</b><p>Subscriptions help you in increasing the CTLV i.e your customer’s lifetime value. Your customers are more likely to pay you more over a period of time and it is not just the power of time but also related to the fact that a growing business has upsell and cross-sell  more offerings. You also maintain a longer relationship with your customers. You are here to stay and offer the same quality of services over and over - this helps building trust. A better relationship leads to better stickiness. In short a perfect recipe for loyalty.</p></div><div class='paragraph'> <b>What are the popular use-cases on subscriptions?</b><p>If you’re still wondering how this helps you, let's take a look at what some players in the market are using subscriptions for:</p></div><div><ol><li> <b>Gated content:</b> If you’re offering your own courses on a weekly or a monthly basis behind a paywall, copy and paste a few lines of code on your website to make it subscription ready.</li><li class='paragraph'> <b>Crowdfunding a passion project:</b> Your fans can now become customers. You fund your project yourself and plan a roadmap with ease. </li><li> <b>Recurring product packs:</b> If you’re looking to start out on a D2C brand with novel content every week, we solve payments for you. </li><li class='paragraph'> <b>Supporting a cause:</b> This is the best way to have a long term relationship with your patrons to collect smaller amounts over a longer period of time</li></ol></div></div>",
                'buttons'     => [
                    [
                        'type'  => 'button',
                        'label' => 'Try Now',
                        'url'   => '/subscriptions',
                    ],
                    [
                        'type'  => 'primary-inverted',
                        'label' => 'Read More',
                        'url'   => 'https://razorpay.com/docs/payment-button/subscription-buttons/',
                    ],
                ],
            ],
        ],
        [
            'id'          => 'whats-new-pp-80gReciepts-jan2021',
            'title'       => 'Enable Automated receipts and 80G receipts',
            'description' => 'Save time in post payment processing by providing an instant payment confirmation receipt. Automated 80G Receipts ensure NGOs can instantly share 80G-compliant receipts',
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/payment-pages-2.svg',
            'video_url'   => 'https://www.youtube.com/embed/fLF3dOdi1CM',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Try Now',
                    'url'   => '/paymentpages',
                ],
                [
                    'type'  => 'primary-inverted',
                    'label' => 'Read More',
                    'id'    => 'announcement-details-l2',
                    'url'   => '/announcements/whats-new-pp-80gReciepts-jan2021/'
                ],
            ],
            'start_ts'    => 1606707034,
            'end_ts'      => 1617193851,
            'filters'     => [
                'experiments'         => ['whats-new-dec-2020'],
            ],
            'l2_content'  => [
                'content'     => "<div class='title'> <img src='https://cdn.razorpay.com/static/assets/notifs/payment-pages-2.svg' width='32px' /><p>Enable automated receipts and 80G receipts on your payment pages</p></div><div><div>In a world where online payments are the norm, giving your customers a seamless payment experience has become absolutely essential. Hiccups in the online payment process bring customer drop-offs, which are bad for business in this super-competitive environment. We, at Razorpay, constantly strive to make payments simpler, smoother, and faster for the benefit of our customers.  We’re taking a step further in our quest for innovating and creating the finest payment solutions with Automated Payment Pages Receipts</div><div class='paragraph'> <b>Why automated Payment Pages Receipts and automated 80G receipts?</b><p>Reassuring customers that their payment has gone through successfully is paramount in business. Businesses, therefore, take several steps to ensure timely confirmation of orders received. However, there is no shortage of problems in this regard. The Automated Payment Pages Receipts feature allows instant receipts to be sent to all your customers instantly when they make payments. As soon as the transaction is successful, the customer instantly gets their receipt via email.  Automated 80G Receipts will ensure that NGOs no longer have to look for donor details to generate 80G-compliant receipts. Donors get an instant receipt with the information they provide, which makes claiming exceptions a breeze.</p></div><div><p>Here’s how Automated Payment Pages Receipts can make your life easier and operations smoother.</p></div><ol><li> <b>Reduced operating costs:</b> You no longer need to hire someone just to send out and keep track of receipts. We’ll do it for you</li><li class='paragraph'> <b>Immediate post-purchase confirmation:</b> The customer receives a receipt instantly after making the payment. No more keeping track of unsent receipts and delayed manual confirmations! </li><li> <b>Fewer support queries:</b> With automated receipts, support queries can be avoided as the customer knows exactly what they have purchased, and the amount paid</li><li class='paragraph'> <b>Better for your brand:</b> Instant confirmation to a digital purchase also cements the brand image and trust for all the transacting parties paid</li><li> <b>Storage simplified: </b> The Razorpay Dashboard serves as a handy tool when it comes to storing your customers’ details and receipt particulars</li><li class='paragraph'> <b>Tax filing made easy: </b> NGOs no longer need to go fishing into their records for their donors in the tax filing season, when the donors wish to claim tax exemptions. The receipts generated for NGOs with all the claim-related information will be sent to them the moment they donate successfully</li></ol></div>",
                'buttons'     => [
                    [
                        'type'  => 'button',
                        'label' => 'Try Now',
                        'url'   => '/paymentpages',
                    ],
                    [
                        'type'  => 'primary-inverted',
                        'label' => 'Read More',
                        'url'   => 'https://razorpay.com/blog/accept-online-donations-with-80g-receipts/',
                    ],
                ],
            ],
        ],
        [
            'id'          => 'whats-new-subs-pause-jan2021',
            'title'       => 'Now you can pause active subscriptions',
            'description' => 'Merchants can now pause and subscriptions on cards and customers can pause and resume subscriptions on UPI. This feature goes a long way in improving customer experience and engaging with customers over a longer period of time.',
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/subscriptions.svg',
            'buttons'     => [
                [
                    'type'  => 'primary-inverted',
                    'label' => 'Read More',
                    'id'    => 'announcement-details-l2',
                    'url'   => '/announcements/whats-new-subs-pause-jan2021/'
                ],
            ],
            'start_ts'    => 1606707034,
            'end_ts'      => 1617193851,
            'filters'     => [
                'experiments'         => ['whats-new-dec-2020'],
            ],
            'l2_content'  => [
                'content'     => "<div class='title'> <img src='https://cdn.razorpay.com/static/assets/notifs/subscriptions.svg' width='32px' /><p>Now you can pause active subscriptions</p></div><div><div>Merchants can now pause and subscriptions on cards and customers can pause and resume subscriptions on UPI. This feature goes a long way in improving customer experience and engaging with customers over a longer period of time.</div><div class='paragraph'> <b>Pause a Subscription</b><p>Only subscriptions in the active state can be paused.</p><p>You can pause a subscription either:</p><ol><li>From the <a href='https://razorpay.com/docs/subscriptions/dashboard/pause/' target='_blank'> Dashboard </a> .</li><li>Via <a href='https://razorpay.com/docs/api/subscriptions/#pause-a-subscription/' target='_blank'> API </a> .</li></ol></div><div> <b>Note: </b> If you pause a subscription in the authenticated state, the subscription goes to the cancelled state..</div><div class='paragraph'> <b>Resume a Subscription</b><p>You can resume a subscription either:</p><ol><li>Via Using <a href='https://razorpay.com/docs/api/subscriptions/#resume-a-subscription' target='_blank'> APIs </a> .</li><li>Using the<a href='https://razorpay.com/docs/subscriptions/dashboard/pause/#resume-subscription-via-the-dashboard/' target='_blank'> Dashboard </a> .</li></ol></div></div>",
                'buttons'     => [
                    [
                        'type'  => 'primary-inverted',
                        'label' => 'Read More',
                        'url'   => 'https://razorpay.com/docs/subscriptions/workflow/#pause-and-resume-a-subscription',
                    ],
                ],
            ],
        ],
        [
            'id'          => 'whats-new-paypal-nocode-jan2021',
            'title'       => 'International Support is now available on these products',
            'description' => 'PayPal as a payment method can now be integrated with any of Payment Links, Payment Pages and Payment Buttons to accept payments in international currencies.',
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/paypal.svg',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Try Now',
                    'url'   => '/config#paypalonboard',
                ],
                [
                    'type'  => 'primary-inverted',
                    'label' => 'Read More',
                    'id'    => 'announcement-details-l2',
                    'url'   => '/announcements/whats-new-paypal-nocode-jan2021/'
                ],
            ],
            'start_ts'    => 1606707034,
            'end_ts'      => 1617193851,
            'filters'     => [
                'features'  => ['paypal_gtm_notification'],
                'experiments'         => ['whats-new-dec-2020'],
            ],
            'l2_content'  => [
                'content'     => "<div class='title'> <img src='https://cdn.razorpay.com/static/assets/notifs/paypal.svg' width='32px' /><p>International Support is now available on these products</p></div><div><div> <b>What are the advantages?</b><p>Integrating Paypal as a payment method on Checkout offers you the following advantages:</p><ol><li>Better Success Rates: Enjoy up to 20% higher success rates.</li><li>Faster Settlement time: Get paid on a T+1 settlement schedule.</li><li>Wide user base: Reach Over 30 Crore PayPal users around the world.</li><li>No additional charges: Transactions will be charged as per the rates defined by PayPal.</li></ol></div><div class='paragraph'> <b>International Payments Only:</b> Currently, you can only accept payments in international currencies using PayPal. You cannot accept payments in INR using PayPal.</div><div> <b>Onboarding process to enable PayPal </b><p>Below is the onboarding process to enable PayPal on your checkout form.</p><p> <b>Note:</b> The PayPal section is visible only on the Live mode on the Razorpay Dashboard.</p><ol><li>Go to Settings on your Razorpay Dashboard.</li><li>Scroll to the PayPal section and click Link Account.</li><div class='image'> <a target='_blank' href='https://cdn.razorpay.com/static/assets/whats-new/paypal-link-account.png'> <img class='image' src='https://cdn.razorpay.com/static/assets/whats-new/paypal-link-account.png' /> </a></div><li>Upon redirection to PayPal: If you do not have a PayPal account, you need to complete the verification process and KYC. This will include confirming your email address by clicking on the link sent to you by PayPal.</li><li>If you already have a PayPal account, you just need to authorize Razorpay to accept payments.</li></ol></div><div class='paragraph'>You should now be able to see your PayPal enablement status set to Pending on your Dashboard. If all the previous steps were completed successfully, PayPal will activate your account within 48 hours. You can now proceed with the integration. This depends on how you have integrated Razorpay on your website or application.</div><div>By default, your PayPal account will only be configured to receive USD payments. You can enable more currencies on your account from your PayPal Dashboard.</div><div class='paragraph'> <b>Standard Checkout Integration</b><p>If you are using Razorpay Standard Checkout, you just need to enable Paypal from your dashboard and complete the onboarding procedure.</p></div><div><p>Once the onboarding is completed and PayPal is enabled for you, it appears on your checkout form for all supported currencies.</p><p class='paragraph'>For other types of integration please click on the learn more button below.</p></div></div>",
                'buttons'     => [
                    [
                        'type'  => 'button',
                        'label' => 'Try Now',
                        'url'   => '/config#paypalonboard',
                    ],
                    [
                        'type'  => 'primary-inverted',
                        'label' => 'Read More',
                        'url'   => 'https://razorpay.com/docs/payment-gateway/payment-methods/paypal/',
                    ],
                ],
            ]
        ],
        [
            'title'       => 'New Year Offer',
            'description' => "Get ₹10,00,000 worth of free credits & 3 months of Opfin's Payroll software for free.",
            'icon'        => '/dist/css/assets/products/opfin.svg',
            'id'          => 'Nov20-Opfin-NitroV4',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Learn More',
                    'url'   => '',
                    'id'    => 'announcement-Nov20-Opfin-NitroV4-cta1',
                ],
            ],
            'start_ts'    => 1609922446,
            'end_ts'      => 1623994200,
            'filters'     => [
                'experiments_with_variant'  => ['rx_opfin_announcement_v2' => 'cohort-5'],
            ],
        ],
        [
            'title'       => 'Introducing Payroll by RazorpayX',
            'description' => "Simplify and process payroll for employees, automate PF, TDS, PT payments, and pay your contractors",
            'icon'        => '/dist/css/assets/products/opfin.svg',
            'id'          => 'opfin-sso-check',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Explore Now',
                    'url'   => 'https://payroll.razorpay.com/sso',
                ],
            ],
            'start_ts'    => 1609922446,
            'end_ts'      => 1623994200,
            'filters'     => [
                'experiments_with_variant'  => ['rx_opfin_sso_announcement' => 'on'],
            ]
        ],
        [
            'id'          => 'JAN21-PG-GTM1',
            'title'       => 'Create an online store using Shopify',
            'description' => 'Build your ecommerce website in no time with Shopify and integrate Razorpay in one-click. Trusted by 1M+ businesses worldwide',
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/shopify.png',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Sign up for free trial',
                    'url'   => 'https://www.shopify.in/shopifyxrazorpay/?ref=thirdwatch-data',
                ],
            ],
            'start_ts'    => 1610908200,
            'end_ts'      => 1619807399,
            'filters'     => [
                'experiments_with_variant'  => ['shopify_gtm_notification_cohorts' => 'cohort-1'],
            ]
        ],
        [
            'id'          => 'JAN21-PG-GTM1-V2',
            'title'       => 'Create an online store using Shopify',
            'description' => 'Build your ecommerce website in no time with Shopify and integrate Razorpay in one-click. Trusted by 1M+ businesses worldwide',
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/shopify.png',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Sign up for free trial',
                    'url'   => 'https://www.shopify.in/shopifyxrazorpay/?ref=thirdwatch-data',
                ],
            ],
            'start_ts'    => 1610908200,
            'end_ts'      => 1619807399,
            'filters'     => [
                'experiments_with_variant'  => ['shopify_gtm_notification_cohorts' => 'cohort-2'],
            ]
        ],
        [
            'title'       => 'Instant Settlements from Day 1',
            'description' => 'Settle your customer payments instantly 24x7',
            'start_ts'    => 1613932200,
            'end_ts'      => 1617258600,//do check for end ts, currently set till 1st march 2021
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/early-settlement.svg',
            'track_event' => true,
            'id'          => 'Feb20-ES1-PILOT',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Settle now',
                    'url'   => '/instantsettlements',
                ],
                [
                    'type'  => 'primary-inverted',
                    'label' => 'Know More',
                    'url'   => 'https://razorpay.com/capital/instant-settlements/',
                ]
            ],
            'filters'     => [
                'experiments_with_variant' => ['es_ondeman_restricted_cohorts' => 'cohort-1'],
            ]
        ],
        [
            'title'       => 'Instant Settlements from Day 1',
            'description' => 'Settle your customer payments instantly 24x7',
            'start_ts'    => 1613932200,
            'end_ts'      => 1617258600,//do check for end ts, currently set till 1st march 2021
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/early-settlement.svg',
            'track_event' => true,
            'id'          => 'Feb20-ES1-PILOT_V2',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Settle now',
                    'url'   => '/instantsettlements',
                ],
                [
                    'type'  => 'primary-inverted',
                    'label' => 'Know More',
                    'url'   => 'https://razorpay.com/capital/instant-settlements/',
                ]
            ],
            'filters'     => [
                'experiments_with_variant' => ['es_ondeman_restricted_cohorts' => 'cohort-2'],
            ]
        ],
        [
            'id'          => 'whats-new-mar21-credpay-gtm',
            'title'       => 'Introducing CRED Pay',
            'description' => 'Boost repeat sales, loyalty, and average order value by 40% with CRED Pay.',
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/cred_pay.png',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Apply for Access',
                    'url'   => 'https://share.hsforms.com/1vq5O2PbXTlmh60MOedSeRA3b5b6',
                ],
                [
                    'type'  => 'primary-inverted',
                    'label' => 'Read More',
                    'id'    => 'announcement-details-l2',
                    'url'   => '/announcements/whats-new-mar21-credpay-gtm/'
                ],
            ],
            'start_ts'    => 1616112000,
            'end_ts'      => 1623974400,
            'filters'     => [
                'experiments'         => ['cred_pay_amex_notification'],
            ],
            'l2_content'  => [
                'content'     => "<div class='title'> <img src='https://cdn.razorpay.com/static/assets/notifs/cred_pay.png' width='32px' /><p>Increase revenue, repeat purchases and loyalty with CRED Pay</p></div><div> <b> Tap into 5.9 million premium customers whose average order value is 40% higher than an average customer </b><ul><li>Up to 15% higher conversions</li><li>Target Premium Customers</li><li>Provide Exclusive Rewards</li><li>Boost Recurring Revenue</li><li>Up to 15% higher conversions</li><li>Exclusive Bank Offers</li></ul><div class='image'> <img src='https://cdn.razorpay.com/static/assets/whats-new/cred_pay.png' /></div><div> <b>CRED Pay Pricing</b><div>Given below are the pricing details for CRED Pay:</div><ul><li>Revenue Share: 5%</li><li> Discount: 10% (This sponsors the discount that your customers get on burning CRED coins.) <br /> <a href='https://razorpay.com/docs/payment-gateway/payment-methods/apps/cred/pricing/#roi-calculator' target='_blank' > Pricing details </a></li></ul></div><div class='paragraph'> <b>How Mosaic Wellness acquired premium customers with CRED pay</b><p> “We are consistently noticing higher basket sizes from CRED members and are thus incentivising these users with CRED coins. As a payment option, it gives users access to their credit cards saved on CRED along with a flow that bypasses OTP & CVV on select Visa cards leading to further reduction in payment drop offs.”</p> <b>Revant Bhate,</b> <br /> <b>CEO, Mosaic Wellness</b></div></div>",
                'buttons'     => [
                    [
                        'type'  => 'button',
                        'label' => 'Apply For Access',
                        'url'   => 'https://share.hsforms.com/1vq5O2PbXTlmh60MOedSeRA3b5b6',
                    ],
                    [
                        'type'  => 'primary-inverted',
                        'label' => 'Pricing',
                        'url'   => 'https://razorpay.com/docs/payment-gateway/payment-methods/apps/cred/pricing/#roi-calculator',
                    ],
                ],
            ],
        ],
        [
            'id'          => 'whats-new-mar21-upiintentios-gtm',
            'title'       => 'Activate UPI Intent on your iOS App',
            'description' => 'Offer a superior payment experience to your iOS App users and boost success rates by ~10%',
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/upi_intent.svg',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Get iOS SDK',
                    'url'   => 'https://razorpay.com/docs/payment-gateway/payment-methods/upi-intent/ios/',
                ],
                [
                    'type'  => 'primary-inverted',
                    'label' => 'Read More',
                    'id'    => 'announcement-details-l2',
                    'url'   => '/announcements/whats-new-mar21-upiintentios-gtm/'
                ],
            ],
            'start_ts'    => 1616544000,
            'end_ts'      => 1624492800,
            'filters'     => [
                'experiments'         => ['upi_intent_notification'],
            ],
            'l2_content'  => [
                'content'     => "<div><div class='title'> <img src='https://cdn.razorpay.com/static/assets/notifs/upi_intent.svg' width='32px' /><p>Unlock 200% growth for your UPI Payments</p></div><div> <p> We’re thrilled to announce that Razorpay is the first payment gateway to launch <b>UPI Intent for iOS.</b></p><div class='paragraph'><p> ‘UPI Intent’ enables your customers to seamlessly complete the payment from their favourite UPI app like GPay or PhonePe instead of typing in their UPI ID. This results in a far superior payment experience and <b>5-10% increase in success rates.</b></p></div><div class='image'> <img src='https://cdn.razorpay.com/static/assets/whats-new/upi-app-intent.gif' /></div><p> Until recently, UPI Intent was available <b>only</b> on Android Apps.</p><div class='paragraph'> Now, with a few lines of code, you can enable UPI Intent payments on your iOS App.</div><p> Click <a href='https://razorpay.com/docs/payment-gateway/payment-methods/upi-intent/ios/' target='_blank' >here</a>, or on the <b>‘Get iOS SDK’</b> below for details on how to integrate UPI Intent on your iOS App.</p><div class='paragraph'> Go ahead and provide the best payment experience to your customers.</div></div></div>",
                'buttons'     => [
                    [
                        'type'  => 'button',
                        'label' => 'Get iOS SDK',
                        'url'   => 'https://razorpay.com/docs/payment-gateway/payment-methods/upi-intent/ios/',
                    ],
                    [
                        'type'  => 'primary-inverted',
                        'label' => 'Learn More',
                        'url'   => 'https://razorpay.com/blog/upi-intent-ios/',
                    ],
                ],
            ],
        ],
        [
            'id'          => 'whats-new-may21-reten2-dashboard',
            'title'       => 'Win Rs. 40000 worth free credits',
            'description' => 'Accept payments from your customers between 24th - 30th May and win Rs.40,000* worth Razorpay Credits. *T&Cs Apply',
            'start_ts'    => 1621794599,
            'end_ts'      => 1622399399,
            'icon'        => 'https://dashboard.razorpay.com/dist/css/assets/product_onboarding/rewards_business_growth.svg',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Accept Payments',
                    'url'   => '/paymentlinks/new',
                ],
                [
                    'type'  => 'primary-inverted',
                    'label' => 'Read more',
                    'id'    => 'announcement-details-l2',
                    'url'   => '/announcements/whats-new-may21-reten2-dashboard',
                ]
            ],
            'filters'     => [
                'experiments'  => [
                    'enable_may_dashboard_notification_retention_1','enable_may_dashboard_notification_retention_2','enable_may_dashboard_notification_retention_3','enable_may_dashboard_notification_retention_4'
                ],
            ],
            'l2_content'  => [
                'content' => "<div><p>We are glad that you chose Razorpay as your payment partner. We wanted to inform you of a benefit that is enabled for your account that can help your business during these tough times.</p><br/><p>Your Razorpay account is eligible to win any one of the following:</p><br/><ul><li style='text-decoration: line-through;'>Rs. 50,000 worth of free credits, if you complete a transaction between 17th May - 23rd May OR</li><li>Rs. 40,000 worth of free credits, if you complete a transaction between 24th May - 30th May OR</li><li> Rs. 10,000 worth of free credits, if you complete a transaction on 31st May</li></ul><br/><p> Use your free credits to accept payments at 0% platform fee.*</p><br/><p>Not just that, 5 lucky winners also stand a chance to win credits worth Rs. 1 lakh by transacting with us between today and 31st May.</p><br/><p>Please note: You are eligible to win free credits only once in a month. The free credits will be added to your Razorpay account on or before 7th June’21.</p><br/><p>We're excited to partner with you on your payments journey and look forward to seeing your business grow!</p><a href='https://lp.razorpay.com/links/razorpay-free-credits-tnc'>*T&Cs apply</a></div>",
                'buttons'     => [
                    [
                        'type'  => 'button',
                        'label' => 'Accept Payments',
                        'url'   => '/paymentlinks/new',
                    ],
                ],
            ]
        ],
        [
            'id'          => 'whats-new-may21-remar2a-dashboard',
            'title'       => 'Win Rs. 40000 worth free credits',
            'description' => 'Accept payments from your customers between 24th - 30th May and win Rs.40,000* worth Razorpay Credits. *T&Cs Apply',
            'start_ts'    => 1621794599,
            'end_ts'      => 1622399399,
            'icon'        => 'https://dashboard.razorpay.com/dist/css/assets/product_onboarding/rewards_business_growth.svg',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Accept Payments',
                    'url'   => '/paymentlinks/new',
                ],
                [
                    'type'  => 'primary-inverted',
                    'label' => 'Read more',
                    'id'    => 'announcement-details-l2',
                    'url'   => '/announcements/whats-new-may21-remar2a-dashboard',
                ]
            ],
            'filters'     => [
                'experiments'   => ['enable_my_dashboard_notification_remarketing'],
            ],
            'l2_content'  => [
                'content' => "<div><p>We are glad that you chose Razorpay as your payment partner. We wanted to inform you of a benefit that is enabled for your account that can help your business during these tough times.</p><br/><p>Your Razorpay account is eligible to win any one of the following:</p><br/><ul><li style='text-decoration: line-through;'>Rs. 50,000 worth of free credits, if you complete a transaction between 17th May - 23rd May OR</li><li>Rs. 40,000 worth of free credits, if you complete a transaction between 24th May - 30th May OR</li><li> Rs. 10,000 worth of free credits, if you complete a transaction on 31st May</li></ul><br/><p> Use your free credits to accept payments at 0% platform fee.*</p><br/><p>Not just that, 5 lucky winners also stand a chance to win credits worth Rs. 1 lakh by transacting with us between today and 31st May.</p><br/><p>Please note: You are eligible to win free credits only once in a month. The free credits will be added to your Razorpay account on or before 7th June’21.</p><br/><p>We're excited to partner with you on your payments journey and look forward to seeing your business grow!</p><a href='https://lp.razorpay.com/links/razorpay-free-credits-tnc'>*T&Cs apply</a></div>",
                'buttons'     => [
                    [
                        'type'  => 'button',
                        'label' => 'Accept Payments',
                        'url'   => '/paymentlinks/new',
                    ],
                ],
            ]
        ],
        [
            'id'          => 'whats-new-may21-remar2-dashboard',
            'title'       => 'Accept payments for free!',
            'description' => 'You can accept payments from your customers at zero platform fee by utilising your available free credits. *T&Cs Apply',
            'start_ts'    => 1621403379,
            'end_ts'      => 1622485799,
            'icon'        => 'https://dashboard.razorpay.com/dist/css/assets/product_onboarding/rewards_business_growth.svg',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Accept Payments',
                    'url'   => '/paymentlinks/new',
                ],
                [
                    'type'  => 'primary-inverted',
                    'label' => 'Check Credits Balance',
                    'url'   => '/credits',
                ]
            ],
            'filters'     => [
                'experiments'   => ['enable_my_dashboard_notification_remarketing'],
            ]
        ],
        [
            'id'          => 'whats-new-JUN21-RXCC-GROWTH',
            'title'       => 'Qualified for Corporate Cards!',
            'description' => 'Make recurring and international spends with exclusive access to a credit card for your business.',
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/corporate-cards.svg',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Apply Now',
                    'id'    => 'whats-new-JUN21-RXCC-GROWTH-cta1',
                    'url'   => '/capital/corporate-cards',
                ],
                [
                    'type'  => 'primary-inverted',
                    'label' => 'Know more',
                    'id'    => 'announcement-details-l2',
                    'url'   => '/announcements/whats-new-JUN21-RXCC-GROWTH/'
                ],
            ],
            'start_ts'    => 1623888000,
            'end_ts'      => 1631836800,
            'filters'     => [
                'features' => ['capital_cards_eligible'],
                'not_features' => ['capital_cards'],
            ],
            'l2_content'  => [
                'content'     => "<div><div> RazorpayX Corporate Card is designed for growing businesses with NO requirement of fixed deposit and up to 50 days of the free credit period. It is also exclusively available at a zero joining fee for top Razorpay users</div><div class='image'> <img src='https://cdn.razorpay.com/static/assets/whats-new/corporate-cards.png' /></div><div> Make effortless digital payments: Stop using your personal cards to avoid compliance and audit hassles. Use a universally accepted Visa Card for your business expenses with no personal liability.</div><div class='paragraph'> Get control with a smart dashboard: Get better visibility into your spending with a user-friendly dashboard. Quickly limit the usage of cards and types of expenses to reduce overcharges. Also, easily apply for add-on cards tailored to your team.</div><div> Dynamic limit, Rewards and Cashback: Get cashback on timely repayments and access to 100+ Visa Business Platinum rewards in addition to the exclusive rewards from our partners. You also get a credit limit that grows with an increase in your revenue.</div></div>",
                'buttons'     => [
                    [
                        'type'  => 'button',
                        'label' => 'Apply Now',
                        'id'    => 'whats-new-JUN21-RXCC-GROWTH-cta1',
                        'url'   => '/capital/corporate-cards',
                    ],
                ],
            ],
        ],
        [
            'id'          => 'JUL21-CC-FEATURELAUNCH',
            'title'       => 'New on Corporate Cards!',
            'description' => 'Physical Cards, Rewards & Add-on cards are here now!',
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/corporate-cards.svg',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Know more',
                    'id'    => 'announcement-details-l2',
                    'url'   => '/announcements/JUL21-CC-FEATURELAUNCH/'
                ],
            ],
            'start_ts'    => 1625184000,
            'end_ts'      => 1633132800,
            'filters'     => [
                'features' => ['capital_cards'],
            ],
            'l2_content'  => [
                'content'     => "<div><div>Your Corporate Card just got better. Your repayments are now faster with no delay in processing and we launched physical and add-on cards for you!</div> <br /><div> <b>Rewards</b><p>You can now access 100+ rewards with RazorpayX Corporate Card. The rewards are attuned to your business growth with names like AWS, Cleartax, Freshworks, Microsoft, Google Ads, Twitter, and many more!</p> <a href='https://lp.razorpay.com/rxcc-rewards' target='_blank'>Check them out</a></div><div class='paragraph'> <b>Add-on cards</b><p>Get add-on cards tailored to different roles of your team members right from your dashboard. Apply easily and quickly from the Card dashboard, empower your team and avoid hassles of reimbursement or approvals for different teams.</p> <a href='https://x.razorpay.com/cards' target='_blank'>Do it now</a></div><div> <b>Physical Cards</b><p> Due to pandemic and lockdown conditions at our manufacturer, your physical cards were delayed. Confirm your physical card delivery address and other details by just filling up the information below. We will prioritize the delivery for the merchants who fill this form</p> <a href='https://razorpay.typeform.com/to/hKPzCtEl' target='_blank'>Fill your details</a></div></div>",
            ],
        ],
    ];

    const SPLITZ_NOTIFICATION = [
        [
            'title'       => 'Get 1.65% pricing with RazorpayX',
            'description' => 'Open a current account with RazorpayX & reduce your platform fee to 1.65%.',
            'start_ts'    => 1626159840,
            'end_ts'      => 1636787040,
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/nitro.svg',
            'id'          => 'projectNitro',
            'campaign'    => 'nitro',
            "target_product_feature" => 'XCA',
            "target_metric" => 'MTU',
            'video_url'   => 'https://cdn.razorpay.com/videos/RazorpayX_Explainer_video_2.mp4',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Learn More',
                    'url'   => '',
                    'id'    => 'announcement-projectNitro-cta1',
                ],
            ],
            'filters'     => [
                'splitz_experiments'         => self::nitroSplitzExperimentsList,
            ],
        ],
        [
            'id'          => 'sept21-pb-wpgeneric-gtm',
            'title'       => 'Razorpay Payment Button for WordPress & Elementor!',
            'description' => 'Start accepting payments on WordPress & Elementor site or blog with Payment Button Plugins.',
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/cms-logo.png',
            'image_url'   => 'http://cdn.razorpay.com/static/assets/notifs/cms-generic.png',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Read More',
                    'id'    => 'announcement-details-l2',
                    'url'   => '/announcements/sept21-pb-wpgeneric-gtm/',
                ],
                [
                    'type'  => 'primary-inverted',
                    'label' => 'View documentation',
                    'url'   => 'https://razorpay.com/docs/payment-button/supported-platforms/wordpress/'
                ],
            ],
            'l2_content'  => [
                'content'     => " <div class='title' style='align-items: center;font-size: 16px;'> <img src='https://cdn.razorpay.com/static/assets/notifs/cms-logo.png' width='32px' style='margin-right:10px;'/> <b>Introducing Payment Button Plugins for WordPress & Elementor</b> </div><div class='video'> <p style='margin-bottom:20px;'> Razorpay Payment Button plugin is an easy-to-use tool that instantly adds a payment checkout function on WordPress & Elementor site or blog.</p> <iframe width='420' height='214' style='border: 0; margin: 10px 0;' src='https://www.youtube.com/embed/4MCy8Blo4Pw'></iframe> </div><div class='paragraph'> <p style='margin-bottom:20px;'><b>Razorpay Payment Button plugin: One button, many uses</b> </p> <ul> <li>100+ Payment modes including UPI, cards, and more</li> <li>One time and recurring payment options. <a href='https://razorpay.com/docs/payment-button/subscription-buttons/' target='_blank'>Read more</a></li> <li>Keep a fixed amount or let your customers fill the amount</li> <li>Accept International Payments in 100+ foreign currencies</li> </ul> <a href='https://razorpay.com/docs/payment-button/supported-platforms/wordpress/' target='_blank'>View documentation</a></div><div> <p style='margin-bottom:20px;'> <b >How to add Razorpay Payment Button on WordPress & Elementor</b> </p> <p style='margin-bottom:10px;'><b>Download and upload Payment Button Plugin:</b></p> <ol style='margin-bottom:20px;'> <li>Download the plugin from WordPress plugin store</li> <li>Upload the Zip file to the WordPress plugin directory</li> <li>Activate from the ‘Plugins’ menu in WordPress</li> </ol> <p>Or simply install the plugin within WordPress</p> <p style='margin-bottom:10px;'><b>Install and activate Razorpay Payment Button Plugin:</b></p> <ol style='margin-bottom:20px;'> <li>Visit the plugins page within WordPress dashboard and select ‘Add New’</li> <li>Search for ‘Razorpay Payment Button’</li> <li>Activate the plugin from ‘Plugins Page’</li> </ol></div><div> <p style='margin-bottom:20px;'><b>Sync WordPress and Razorpay account: </b></p> <ol> <li> <b>On your Razorpay Dashboard: </b> <p>Settings → API Keys → Generate/Copy your saved API Keys and Key ID</p> </li> <li> <b>On your WordPress Admin Dashboard</b> <p>Razorpay Payment Button → Settings → Add API Secret Keys and details</p> </li> </ol></div>",
                'buttons'     => [
                    [
                        'type'  => 'button',
                        'label' => 'Plugin for WordPress',
                        'url'   => 'https://wordpress.org/plugins/razorpay-payment-button/',
                    ],
                    [
                        'type'  => 'primary-inverted',
                        'label' => 'Plugin for Elementor',
                        'url'   => 'https://wordpress.org/plugins/razorpay-payment-button-elementor/',
                    ],
                ],
            ],
            'start_ts'    => 1634187296,
            'end_ts'      => 1642136096,
            'filters'     => [
                'splitz_experiments'         => ['PLATFORM_PB_PLUGIN_GENERIC_MERCHANTS_ANNOUNCEMENT_SPLITZ'],
            ],
        ],
        [
            'id'          => 'sept21-pb-wpcharity-gtm',
            'title'       => 'Donate Now button on WordPress & Elementor!',
            'description' => 'Accept one time and recurring donations, international payments on WordPress & Elementor with Payment Button Plugins.',
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/cms-logo.png',
            'image_url'   => 'http://cdn.razorpay.com/static/assets/notifs/cms-generic.png',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Read More',
                    'id'    => 'announcement-details-l2',
                    'url'   => '/announcements/sept21-pb-wpcharity-gtm/',
                ],
                [
                    'type'  => 'primary-inverted',
                    'label' => 'View documentation',
                    'url'   => 'https://razorpay.com/docs/payment-button/supported-platforms/wordpress/',
                ],
            ],
            'l2_content'  => [
                'content'     => " <div class='title' style='align-items: center;font-size: 16px;'><img src='https://cdn.razorpay.com/static/assets/notifs/cms-logo.png' width='32px' style='margin-right:10px;'/> <b>Collect one-time and recurring donations on WordPress with our latest plugins!</b> </div><div class='video'> <p style='margin-bottom:20px;'> Razorpay Payment Button plugin is an easy-to-use tool that instantly adds a payment checkout function on WordPress & Elementor site or blog. </p> <iframe width='420' height='214' style='border: 0; margin: 10px 0;' src='https://www.youtube.com/embed/4MCy8Blo4Pw'></iframe> </div><div class='paragraph'> <p style='margin-bottom:20px;'><b>Razorpay Payment Button plugin: One button, many uses</b> </p> <ul> <li>Send automated and 80G receipts to your customers</li> <li>Accept one time and recurring donations. <a href='https://razorpay.com/docs/payment-button/subscription-buttons/' target='_blank'>Read more</a></li> <li>Offer 100+ payment modes including UPI, cards, and more</li> <li>Keep a fixed amount or let your customers decide the amount</li> <li>Accept International Payments in 100+ foreign currencies</li> </ul> <a href='https://razorpay.com/docs/payment-button/supported-platforms/wordpress/' target='_blank'>View documentation</a></div><div> <p style='margin-bottom:20px;'> <b >How to add Razorpay Payment Button on WordPress & Elementor</b> </p> <p style='margin-bottom:10px;'><b>Download and upload Payment Button Plugin:</b></p> <ol style='margin-bottom:20px;'> <li>Download the plugin from WordPress plugin store</li> <li>Upload the Zip file to the WordPress plugin directory</li> <li>Activate from the ‘Plugins’ menu in WordPress</li> </ol> <p>Or simply install the plugin within WordPress</p> <p style='margin-bottom:10px;'><b>Install and activate Razorpay Payment Button Plugin:</b></p> <ol style='margin-bottom:20px;'> <li>Visit the plugins page within WordPress dashboard and select ‘Add New’</li> <li>Search for ‘Razorpay Payment Button’</li> <li>Activate the plugin from ‘Plugins Page’</li> </ol></div><div> <p style='margin-bottom:20px;'><b>Sync WordPress and Razorpay account: </b></p> <ol> <li> <b>On your Razorpay Dashboard: </b> <p>Settings → API Keys → Generate/Copy your saved API Keys and Key ID</p> </li> <li> <b>On your WordPress Admin Dashboard</b> <p>Razorpay Payment Button → Settings → Add API Secret Keys and details</p> </li> </ol></div>",
                'buttons'     => [
                    [
                        'type'  => 'button',
                        'label' => 'Plugin for WordPress',
                        'url'   => 'https://wordpress.org/plugins/razorpay-payment-button/',
                    ],
                    [
                        'type'  => 'primary-inverted',
                        'label' => 'Plugin for Elementor',
                        'url'   => 'https://wordpress.org/plugins/razorpay-payment-button-elementor/',
                    ],
                ],
            ],
            'start_ts'    => 1634187296,
            'end_ts'      => 1642136096,
            'filters'     => [
                'splitz_experiments'         => ['PLATFORM_PB_PLUGIN_CHARITY_MERCHANTS_ANNOUNCEMENT_SPLITZ'],
            ],
        ],
        [
            'id'          => 'sept21-pb-wpelearning-gtm',
            'title'       => 'Collect online course fees on WordPress & Elementor!',
            'description' => 'Grow your e-learning business by offering multiple payment plans on WordPress and Elementor sites with Payment Button Plugins.',
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/cms-logo.png',
            'image_url'   => 'http://cdn.razorpay.com/static/assets/notifs/cms-generic.png',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Read More',
                    'id'    => 'announcement-details-l2',
                    'url'   => '/announcements/sept21-pb-wpelearning-gtm/',
                ],
                [
                    'type'  => 'primary-inverted',
                    'label' => 'View documentation',
                    'url'   => 'https://razorpay.com/docs/payment-button/supported-platforms/wordpress/',
                ],
            ],
            'l2_content'  => [
                'content'     => " <div class='title' style='align-items: center;font-size: 16px;'> <img src='https://cdn.razorpay.com/static/assets/notifs/cms-logo.png' width='32px' style='margin-right:10px;'/> <b>Start collecting fees on your WordPress & Elemetor site instantly with Payment Button Plugins</b> </div><div class='video'> <p style='margin-bottom:20px;'> Razorpay Payment Button plugin is an easy-to-use tool that instantly adds a payment checkout function on WordPress & Elementor site or blog. </p> <p style='margin-bottom:20px;'> Start accepting payments for your online course, webinars and programs on WordPress in less than five minutes. </p> <iframe width='420' height='214' style='border: 0; margin: 10px 0;' src='https://www.youtube.com/embed/4MCy8Blo4Pw'></iframe> </div><div class='paragraph'> <p style='margin-bottom:20px;'><b>Plug and play with Razorpay Payment Buttons</b> </p> <ul> <li>Offer 100+ Payment modes including UPI, cards, and more</li> <li>Accept one time and recurring payments. <a href='https://razorpay.com/docs/payment-button/subscription-buttons/' target='_blank'>Read more</a></li> <li>Collect student details and track them on one dashboard</li> <li>Keep a fixed amount or let your customers decide the amount</li> <li>Accept International Payments in 100+ foreign currencies</li> </ul> <a href='https://razorpay.com/docs/payment-button/supported-platforms/wordpress/' target='_blank'>View documentation</a></div><div> <p style='margin-bottom:20px;'> <b >How to add Razorpay Payment Button on WordPress & Elementor</b> </p> <p style='margin-bottom:10px;'><b>Download and upload Payment Button Plugin:</b></p> <ol style='margin-bottom:20px;'> <li>Download the plugin from WordPress plugin store</li> <li>Upload the Zip file to the WordPress plugin directory</li> <li>Activate from the ‘Plugins’ menu in WordPress</li> </ol> <p>Or simply install the plugin within WordPress</p> <p style='margin-bottom:10px;'><b>Install and activate Razorpay Payment Button Plugin:</b></p> <ol style='margin-bottom:20px;'> <li>Visit the plugins page within WordPress dashboard and select ‘Add New’</li> <li>Search for ‘Razorpay Payment Button’</li> <li>Activate the plugin from ‘Plugins Page’</li> </ol></div><div> <p style='margin-bottom:20px;'><b>Sync WordPress and Razorpay account: </b></p> <ol> <li> <b>On your Razorpay Dashboard: </b> <p>Settings → API Keys → Generate/Copy your saved API Keys and Key ID</p> </li> <li> <b>On your WordPress Admin Dashboard</b> <p>Razorpay Payment Button → Settings → Add API Secret Keys and details</p> </li> </ol></div>",
                'buttons'     => [
                    [
                        'type'  => 'button',
                        'label' => 'Plugin for WordPress',
                        'url'   => 'https://wordpress.org/plugins/razorpay-payment-button/',
                    ],
                    [
                        'type'  => 'primary-inverted',
                        'label' => 'Plugin for Elementor',
                        'url'   => 'https://wordpress.org/plugins/razorpay-payment-button-elementor/',
                    ],
                ],
            ],
            'start_ts'    => 1634187296,
            'end_ts'      => 1642136096,
            'filters'     => [
                'splitz_experiments'         => ['PLATFORM_PB_PLUGIN_LEARNING_MERCHANTS_ANNOUNCEMENT_SPLITZ'],
            ],
        ],
        [
            'id'          => 'sept21-pb-wpwebsite-gtm',
            'title'       => 'Payment Button Plugins for WordPress & Elementor!',
            'description' => 'Accept one time, recurring and international payments on WordPress & Elementor site with Razorpay Payment Button Plugins!',
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/cms-logo.png',
            'image_url'   => 'http://cdn.razorpay.com/static/assets/notifs/cms-generic.png',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Read More',
                    'id'    => 'announcement-details-l2',
                    'url'   => '/announcements/sept21-pb-wpwebsite-gtm/',
                ],
                [
                    'type'  => 'primary-inverted',
                    'label' => 'Install the plugin',
                    'url'   => 'https://wordpress.org/plugins/razorpay-payment-button/',

                ],
            ],
            'l2_content'  => [
                'content'     => " <div class='title' style='align-items: center;font-size: 16px;'> <img src='https://cdn.razorpay.com/static/assets/notifs/cms-logo.png' width='32px' style='margin-right:10px;'/> <b>Make your work more efficient with Razorpay Payment Button Plugins!</b> </div><div class='video'> <p style='margin-bottom:20px;'> Razorpay Payment Button Plugin is an easy-to-setup tool that helps you accept payments on WordPress & Elementor. The plugin allows you to instantly add a payment checkout function at the touch of a button. </p> <iframe width='420' height='214' style='border: 0; margin: 10px 0;' src='https://www.youtube.com/embed/4MCy8Blo4Pw'></iframe> </div> <p>So now, no more deep-diving into the source code; plugins help developers save time, especially if you are adding multiple payment buttons on your site.</p><div class='paragraph'> <p style='margin-bottom:20px;'><b>Plug and play with Razorpay Payment Buttons</b> </p> <ul> <li>Accept one time and recurring payments. <a href='https://razorpay.com/docs/payment-button/subscription-buttons/' target='_blank'>Read more</a></li> <li>Offer 100+ Payment modes including UPI, cards, and more</li> <li>Keep a fixed amount or let your customers decide the amount</li> <li>Accept International Payments in 100+ foreign currencies</li> </ul> <a href='https://razorpay.com/docs/payment-button/supported-platforms/wordpress/' target='_blank'>View documentation</a></div><p>Drag, drop and collect with Razorpay Payment Button plugins!</p>",
                'buttons'     => [
                    [
                        'type'  => 'button',
                        'label' => 'Plugin for WordPress',
                        'url'   => 'https://wordpress.org/plugins/razorpay-payment-button/',
                    ],
                    [
                        'type'  => 'primary-inverted',
                        'label' => 'Plugin for Elementor',
                        'url'   => 'https://wordpress.org/plugins/razorpay-payment-button-elementor/',
                    ],
                ],
            ],
            'start_ts'    => 1634187296,
            'end_ts'      => 1642136096,
            'filters'     => [
                'splitz_experiments'         => ['PLATFORM_PB_PLUGIN_WEBDEV_MERCHANTS_ANNOUNCEMENT_SPLITZ'],
            ],
        ],
        [
            'title'       => 'Boost your revenue growth',
            'description' => 'Grow your revenue streams by accepting international payments in 100+ foreign currencies',
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/cross-border-payments-icon.svg',
            'id'          => 'Sep21-CrossBorder-Activation',
            'campaign'    => 'growth',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Accept International Payments',
                    'url'   => '/payment-methods?utm_source=Dashboard+announcement&utm_campaign=Existing+domestic+merchants&utm_id=International+Payments',
                ],
            ],
            'start_ts'    => 1634187296,
            'end_ts'      => 1642136096,
            'filters'     => [
                'splitz_experiments'         => ['CROSS_BORDER_PAYMENTS_ANNOUNCEMENT'],
            ],
        ],
        [
            'title'       => 'Razorpay Trusted Business badge is now Live!',
            'description' => 'Congratulations, you are now a Razorpay Trusted Business! 🚀',
            'start_ts'    => 1632421145,
            'end_ts'      => 1640283542,
            'icon'        => "https://cdn.razorpay.com/static/assets/notifs/rtb-icon.svg",
            'id'          => 'RTB_Trusted_Badge',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'See Details',
                    'url'   => '/trustedbadge',
                ],
                [
                    'type'  => 'primary-inverted',
                    'label' => 'Read More',
                    'id'    => 'announcement-details-l2',
                    'url'   => '/announcements/RTB_Trusted_Badge/'
                ],
            ],
            'l2_content'  => [
            'content'     => "<div class='paragraph'> <b>About the Razorpay Trusted Business badge</b><p>It can be challenging to build trust with customers in the digital world. A lack of trust often has businesses experience upto 20% cart abandonments with new v/s repeat users.</p><p class='paragraph'> To help bridge this gap in trust, we recently launched the 'Razorpay Trusted Business' badge. This badge acts as a seal of approval from a trusted payments service provider (Razorpay) and assures customers that they can safely transact with your brand. </p><div class='image'> <img src='https://cdn.razorpay.com/static/assets/whats-new/rtb-announcement.svg' /></div><p>Razorpay Trusted Business badge is an exclusive offering available only to our most trusted partners and promises to increase conversion rates and reduce dependency on 'cash on delivery'.</p></div><div class='paragraph'><b>Earning the Razorpay Trusted Business badge</b><p>Given your excellent track record with Razorpay, your Razorpay payment checkout page has already been upgraded to reflect that you are a Razorpay Trusted Business.</p></div><div class='paragraph'><b>What will your customers see?</b><ol><li>User arrives on your website, app, link or page to make a purchase </li><li>User sees the Razorpay Trusted Business badge on your Razorpay checkout page, reassuring the user that they can safely transact from your brand.</li><li>User selects their preferred payment method and completes the payment as usual</li></ol><p>Razorpay Trusted Business badge works across all our standard checkout products - core Payment Gateway, Payment Pages, Payment Buttons, Payment Links etc.</p></div><div class='paragraph'><b>Benefits of Razorpay Trusted Business badge</b><ol><li>Win your customers’ Trust 🚀</li><li>Boost your conversion rates 📈</li><li>Flaunt the badge and stand out from competition 😎</li></ol></div><div class='paragraph'><b>Will I be charged anything?</b><p>No, showcasing the Razorpay Trusted Badge on the payment checkout page is completely FREE with No hidden charges.</p></div>",
                'buttons'     => [
                    [
                        'type'  => 'button',
                        'label' => 'See Details',
                        'url'   => '/trustedbadge',
                    ],
                    [
                        'type'  => 'primary-inverted',
                        'label' => 'Read More',
                        'url'   => 'https://razorpay.com/docs/payment-gateway/dashboard-guide/trusted-badge/',
                    ],
                ],
            ],
            'filters'     => ['splitz_experiments' => ['RTB_ANNOUNCEMENT']],
        ],
        [
            'id'          => 'OCT21-QR-GTM',
            'title'       => 'Get Razorpay MultiQR today!',
            'description' => 'Create unlimited QR codes with your business branding and start collecting payments via UPI and cards.',
            'start_ts'    => 1632960000,
            'end_ts'      => 1640822400,
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/qr-code.svg',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Get QR',
                    'url'   => '/qr_codes',
                ],
                [
                    'type'  => 'primary-inverted',
                    'label' => 'Learn More',
                    'url'   => 'https://razorpay.com/docs/qr-codes/'
                ],
            ],
            'filters'     => [
                'splitz_experiments' => ['QR_CODE_ANNOUNCEMENT'],
            ]
        ],
        [
            'id'          => 'OCT-DA-NITRO-CARDOFFER',
            'title'       => 'Free Corporate Card! Festive Bonanza!',
            'description' => 'Yes, you heard it right. Get a Razorpay corporate card in less than 3 days  along with 25,000 free credits 🎉 .This festive season enjoy the benefits of a pre-approved RazorpayX corporate card with upto 5 lakhs credit limit 🎉',
            'start_ts'    => 1632960000,
            'end_ts'      => 1640822400,
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/nitro_festive_bonanza_icon.svg',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Know More',
                    'url'   => '',
                    'id'    => 'announcement-projectNitro-cta1',
                ],
            ],
            'filters'     => [
                'splitz_experiments' => ['NITRO_CC_ANNOUNCEMENT'],
            ]
        ],
        [
            'title'       => 'Avail Reduced Transaction Fee!',
            'description' => 'Reduce transaction fee to 1.65% & get a corporate card by switching to Razorpay Current Accounts!',
            'start_ts'    => 1626159840,
            'end_ts'      => 1636787040,
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/nitro.svg',
            'id'          => 'projectNitro',
            'campaign'    => 'nitro',
            "target_product_feature" => 'XCA+CCC',
            "target_metric" => 'MTU',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Learn More',
                    'url'   => '',
                    'id'    => 'announcement-projectNitro-cta1',
                ],
            ],
            'filters'     => [
                'splitz_experiments'         => self::nitroCorporateCardsSplitzExperimentsList,
            ],
        ],
        [
            'id'          => 'APR23-DX-CSAT',
            'title'       => 'Developers, we want to hear you!',
            'description' => 'Help us deliver the best developer experience for you. All we need is your valuable feedback. This won’t take more than a minute.',
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/csat-survey.svg',
            'track_event' => true,
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Let’s begin',
                    'id'    => '',
                    'url'   => 'https://razorpay.typeform.com/to/Kzw8bOUb',
                    'url_query_params' => ["mid", "email"]
                ],
            ],
            'start_ts'    => 1619481600,
            'end_ts'      => 1620432000,
            'filters'     => ['splitz_experiments' => ['ANNOUNCEMENT_DX_CSAT_APRIL2021_SPLITZ']],
        ],
        [
            'id'          => 'whats-new-april21-m2mrewards-gtm',
            'title'       => 'Introducing Checkout Rewards! 🎁',
            'description' => 'Give your customers exciting rewards with every purchase! Watch your sales grow with higher conversions and higher repeat purchase.',
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/checkout_rewards.svg',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Try Now',
                    'url'   => '/checkout-rewards',
                ],
                [
                    'type'  => 'primary-inverted',
                    'label' => 'Read More',
                    'id'    => 'announcement-details-l2',
                    'url'   => '/announcements/whats-new-april21-m2mrewards-gtm/'
                ],
            ],
            'l2_content'  => [
                'content'     => "<div class='paragraph'> <b>What are Checkout Rewards?</b><p> Checkout Rewards are essentially FREE rewards that your customers will receive upon completing each successful payment from your website/app.</p><div class='image'> <img src='https://cdn.razorpay.com/static/assets/whats-new/checkout_rewards.svg' /></div></div><div> <b>How does Checkout Rewards work?</b><p>The GIF below shows what the flow looks like from an end users’ perspective.</p><div class='image'> <img src='https://cdn.razorpay.com/static/assets/whats-new/checkout-rewards-flow.gif' /></div><p>The flow is as simple as it can be. Here’s an overview:</p><ol><li>User arrives on the checkout page of a merchant (Acme Corp in this example)</li><li>User selects their preferred payment type and completes the payment</li><li> User receives an email & SMS with payment confirmation and details about the reward</li><li>User can directly redeem the reward on the website/app of reward provider</li></ol><p> Just to make it obvious, the customer did NOT have to select any reward manually. A customer will automatically receive 1 exciting reward on completing a payment.</p> <br /><p> Please Note - Checkout Rewards is completely <b>Free</b> for you and your end users!</p></div><div class='paragraph'> <b>What to expect from Checkout Rewards</b><ol><li>Increase in sales volume 🚀</li><li>Better user experience 😃</li><li>Higher conversion rates 📈</li></ol></div><div> <b>How to activate Checkout Rewards?</b><p> Well, the beauty of Checkout Rewards lies in its simplicity. You can activate Checkout Rewards in less than 30 seconds. All you have to do is:</p><ol><li>Navigate to “Checkout Rewards” on the left panel</li><li>Click “Activate” to activate a Reward</li><li>That’s it. Your customers will start receiving rewards within 24 hours</li></ol><div class='paragraph'> So, what are you waiting for? Click “Try Now” to start using Checkout Rewards!</div></div>",
                'buttons'     => [
                    [
                        'type'  => 'button',
                        'label' => 'Try Now',
                        'url'   => '/checkout-rewards',
                    ],
                    [
                        'type'  => 'primary-inverted',
                        'label' => 'Read More',
                        'url'   => 'https://razorpay.com/docs/payment-gateway/checkout-rewards/',
                    ],
                ],
            ],
            'campaign' => 'M2M Rewards',
            'version_description' => 'Cross Selling M2M rewards feature',
            'target_product_feature'=> 'Checkout Rewards',
            'target_metric' => 'Adoption',
            'start_ts'    => 1620604800,
            'end_ts'      => 1628553600,
            'filters'     => [
                'splitz_experiments' => ['ANNOUNCEMENT_CHECKOUT_REWARDS_ENABLED_SPLITZ','ANNOUNCEMENT_CHECKOUT_REWARDS_INTERESTED_SPLITZ','ANNOUNCEMENT_CHECKOUT_REWARDS_LIVE_SPLITZ', 'ANNOUNCEMENT_CHECKOUT_REWARDS_INTERESTED_SEGMENT2_SPLITZ', 'ANNOUNCEMENT_CHECKOUT_REWARDS_GO_LIVE_READY_SPLITZ']
            ],
        ],
        [
            'id'          => 'June21-QR-GTM',
            'title'       => 'Collect payments using QR Codes!',
            'description' => 'Create your own QR code in a min - Download and Collect payments easily. Choose from 7+ features-Get unlimited QR codes for free.',
            'start_ts'    => 1623824237,
            'end_ts'      => 1633112999,
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/qr-code.svg',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Create QR code now',
                    'url'   => '/qr_codes/new',
                ],
                [
                    'type'  => 'primary-inverted',
                    'label' => 'Know more',
                    'url'   => 'https://razorpay.com/docs/qr-codes-beta/',
                ]
            ],
            'filters'     => [
                'splitz_experiments' => ['ANNOUNCEMENT_QR_CODE_V1_SPLITZ'],
            ]
        ],
        [
            'id'          => 'whats-new-JUL21-RXCC-ULTRA',
            'title'       => 'Qualified for Corporate Cards!',
            'description' => 'Simplify and save when doing recurring spends and international payments for marketing, subscriptions, cloud infra and much more.',
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/corporate-cards.svg',
            'start_ts'    => 1627453800,
            'end_ts'      => 1638081000,
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Get your card',
                    'url'   => '/capital/corporate-cards/',
                    'id'    => 'ultra-campaign-announcement-cta-1',
                ],
                [
                    'type'  => 'primary-inverted',
                    'label' => 'Know more',
                    'id'    => 'announcement-details-l2',
                    'url'   => '/announcements/whats-new-JUL21-RXCC-ULTRA/'
                ],
            ],
            'filters' => [
                'splitz_experiments' => ['ULTRA_CAMPAIGN_ANNOUNCEMENT_SPLITZ'],
            ],
            'l2_content'  => [
                'content'     => "<div><div class='paragraph'>Grow your business with a universal credit card with no requirement of fixed deposit and up to 50 days of the free credit period. It is also exclusively available at a ZERO joining fee for qualified Razorpay users.</div> <div class='image' style='padding: 10px 0;'><img src='https://cdn.razorpay.com/static/assets/whats-new/corporate-cards.png'></div><div class='paragraph'><b>Made for growing businesses: </b>Stop using your personal cards to avoid compliance and audit hassles. Use a universally accepted Visa Card for your business expenses with no personal liability. </div><div><b>Value for money on your spending: </b>0.4% Cashback on timely repayments and access to 100+ Visa Business Platinum rewards in addition to a low 1.99% foreign currency transaction fee. </div><div class='paragraph'><b>Smart dashboard and budgeting: </b>Get complete visibility and effortless reports. Set custom limits and permissions for add-on cards and dispute transactions or cancel subscriptions with a click.</div> </div>",
                'buttons'     => [
                    [
                        'type'  => 'button',
                        'label' => 'Apply now',
                        'url'   => '/capital/corporate-cards/',
                        'id'    => 'ultra-campaign-announcement-cta-1',
                    ],
                ],
            ],
        ],
        [
            'id'          => 'july-ssl-certificate-update',
            'title'       => 'SSL Certificate Update for Razorpay API',
            'description' => 'We’re updating the SSL certificate for api.razorpay.com on 15th July, 2021. To understand if this update affects you, click on the link below.',
            'start_ts'    => 1625097600,
            'end_ts'      => 1633046400,
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/alert.svg',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Learn more',
                    'url'   => 'https://lp.razorpay.com/links/update-tls-ssl-certificate-2021',
                ]
            ],
            'filters'     => [
                'splitz_experiments' => ['SSL_UPDATE_ANNOUNCEMENT_SPLITZ'],
            ]
        ],
        [
            'id'          => 'JUN21-SELFSERVE-CR&BL',
            'title'       => 'Goodbye Support Requests',
            'description' => 'Add credits & balances to your account on your own without raising a support ticket.',
            'start_ts'    => 1625547545,
            'end_ts'      => 1633046400,
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/save-rupee.svg',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Learn How',
                    'url'   => 'https://razorpay.com/docs/payment-gateway/dashboard-guide/credits/',
                ],
                [
                    'type'  => 'primary-inverted',
                    'label' => 'Try Now',
                    'url'   => '/credits',
                ],
            ],
            'filters'     => [
                'splitz_experiments' => ['SELF_SERVE_ANNOUNCEMENT_SPLITZ'],
            ]
        ],
        [
            'id'          => 'OCT-DA-NITRO-FESTIVEBONANZA',
            'title'       => 'Festive Bonanza!',
            'description' => "This festive season enjoy the benefits of RazorpayX current account along with a reduced pricing of 1.65% on payments 🎉",
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/nitro_festive_bonanza_icon.svg',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Try now',
                    'url'   => '',
                    'id'    => 'announcement-projectNitro-cta1',
                ],
            ],
            'start_ts'    => 1632960000,
            'end_ts'      => 1638517904,
            'filters'     => [
                'splitz_experiments'         => self::nitroSplitzExperimentsList,
            ],
        ],
        [
            'id'          => 'OCT-DA-NITRO-ICICIBranded',
            'title'       => 'Festive Bonanza!',
            'description' => "Enjoy the benefits of ICICI Powered RazorpayX current account with a reduced pricing of 1.65% on your payments 🎉",
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/nitro_festive_bonanza_icon.svg',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Try now',
                    'url'   => '',
                    'id'    => 'announcement-projectNitro-cta1',
                ],
            ],
            'start_ts'    => 1632960000,
            'end_ts'      => 1641800920,
            'filters'     => [
                'splitz_experiments' => ['NITRO_ICICI_BRANDED_CAMPAIGN'],
            ]
        ],
        [
            'id'          => 'OCT-DA-NITRO-ICICIRemarketing',
            'title'       => 'Powered By ICICI',
            'description' => "Enjoy the benefits of ICICI Powered RazorpayX current account with a reduced pricing of 1.65% on your payments 🎉",
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/nitro_festive_bonanza_icon.svg',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Try now',
                    'url'   => '',
                    'id'    => 'announcement-projectNitro-cta1',
                ],
            ],
            'start_ts'    => 1632960000,
            'end_ts'      => 1641800920,
            'filters'     => [
                'splitz_experiments' => ['NITRO_ICICI_REMARKETING_CAMPAIGN'],
            ]
        ],
        [
            'id'          => 'May21-PLMApp-GTM',
            'title'       => 'Accept and track your payments on the go!',
            'description' => 'With the Payments Mobile App, create and share payment links instantly, track payments on the go and issue refunds with a single click from anywhere.',
            'start_ts'    => 1626134400,
            'end_ts'      => 1634083200,
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/pg_mobile_app_icon.svg',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Download Now',
                    'url'   => '',
                    'id'    => 'announcement-May21-PLMApp-GTM',
                ],
            ],
            'filters'     => [
                'splitz_experiments' => ['PAYMENT_LINKS_PRIMARY_PRODUCT_SPLITZ']
            ]
        ],
        [
            'id'          => 'Sep22-AppStore-Zapier-GTM-Announcement',
            'title'       => 'Announcing Zapier Integration',
            'description' => "Now integrate Razorpay with GSheets, Zoho, Slack and 3000+ apps through Zapier",
            'icon'        => '/dist/css/assets/zapier.png',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Try now',
                    'url'   => 'https://zapier.com/apps/razorpay-1/integrations/?utm_source=GrowthAsset',
                    'id'    => '',
                ],
                [
                    'type'  => 'primary-inverted',
                    'label' => 'Learn more',
                    'id'    => '',
                    'url'   => 'https://dashboard.razorpay.com/app/app-store/zapier'
                ],
            ],
            'start_ts'    => 1632960000,
            'end_ts'      => 1640822400,
            'filters'     => [
                'splitz_experiments'  => ['ZAPIER_ANNOUNCEMENT_SPLITZ'],
            ],
        ],
        [
            'id'          => 'Cardsgolive_Sub_Sep2021',
            'title'       => 'Cards are back on Subscriptions!',
            'description' => "IMPORTANT UPDATE - Your favourite payment method ‘Cards’ is back and live on Razorpay Subscriptions!",
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/subscriptions-nav.svg',
            'start_ts'    => 1632984489,
            'end_ts'      => 1638254889,
            'filters'     => [
                'splitz_experiments'  => ['CARDS_GO_LIVE_SUBSCRIPTIONS_SPLITZ', 'CARDS_GO_LIVE_CAW_SPLITZ'],
            ],
        ],
        [
            'id'          => 'SEP21-ULTRALOC-ANNCMNT',
            'title'       => 'Credit Line to Grow your Business',
            'description' => 'Borrow collateral-free money that goes to your bank account within seconds. Withdraw 24/7 and Repay in easy instalments.',
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/cash_advance_icon.svg',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Enable now',
                    'id'    => 'ultra-p2-cash-advance-cta-1',
                    'url'   => '/capital/cash-advance',
                ],
                [
                    'type'  => 'primary-inverted',
                    'label' => 'Learn more',
                    'id'    => 'announcement-details-l2',
                    'url'   => '/announcements/SEP21-ULTRALOC-ANNCMNT/'
                ],
            ],
            'start_ts'    => 1632476392,
            'end_ts'      => 1637746792,
            'filters'     => [
                'splitz_experiments' => ['ULTRA_P2_CASH_ADVANCE_ANNOUNCEMENT_SPLITZ'],
            ],
            'l2_content'  => [
                'content'     => "<div><div>Never run out of money with Cash Advance - a line of credit facility available to you at zero joining fee. Pay interest only on the amount withdrawn - No annual fee or maintenance fee.</div> <div class='image' style='padding: 20px 0 10px 0;'><img src='https://cdn.razorpay.com/static/assets/whats-new/cash-advance/cash-advance-p2-hero.png'></div><div class='paragraph'><b>Easy withdrawal and repayments: </b>Withdraw cash up to your credit limit, repay when customers pay and borrow again when you need cash.</div><div><b>Instant additional cash: </b>Borrow collateral-free money that goes directly into your bank account within seconds.</div><div class='paragraph'><b>No unnecessary approvals: </b>Once enabled, you don't need to apply for approval every time. Just log in to the dashboard and withdraw funds within seconds.</div></div>",
                'buttons'     => [
                    [
                        'type'  => 'button',
                        'label' => 'Apply Now',
                        'url'   => '/capital/cash-advance',
                        'id'    => 'ultra-p2-cash-advance-cta-1',
                    ],
                ],
            ],
        ],
    ];

    //insert data in data field, that is dynamically loaded based on the sub-campaign
    const ANNOUNCEMENT_ID_TO_SUB_CAMPAIGN_DETAIL_MAPPING = [
        'projectNitro'=>[
            [
                'data'  => [
                    'version'     =>  'nitro_hyderabad_v4',
                    "version_description" => 'Nitro for hyderabad',
                ],
                'splitz_experiments'         => ['ANNOUNCEMENT_NITRO_HYDERABAD_SPLITZ', 'ANNOUNCEMENT_NITRO_CORPORATE_CARDS_HYDERABAD_SPLITZ'],
            ],
            [
                'data'  => [
                    'version'     =>  'nitro_kolkata_v1',
                    "version_description" => 'Nitro for kolkata',
                ],
                'splitz_experiments'         => ['ANNOUNCEMENT_NITRO_KOLKATA_V1_SPLITZ', 'ANNOUNCEMENT_NITRO_CORPORATE_CARDS_KOLKATA_V1_SPLITZ', 'ANNOUNCEMENT_NITRO_CORPORATE_CARDS_TEST_ACCOUNT_SPLITZ'],
            ],
            [
                'data'  => [
                    'version'     =>  'nitro_surat_v1',
                    "version_description" => 'Nitro for surat',
                ],
                'splitz_experiments'         => ['ANNOUNCEMENT_NITRO_SURAT_V1_SPLITZ', 'ANNOUNCEMENT_NITRO_CORPORATE_CARDS_SURAT_V1_SPLITZ'],
            ],
            [
                'data'  => [
                    'version'     =>  'nitro_jaipur_v1',
                    "version_description" => 'Nitro for jaipur',
                ],
                'splitz_experiments'         => ['ANNOUNCEMENT_NITRO_JAIPUR_V1_SPLITZ', 'ANNOUNCEMENT_NITRO_CORPORATE_CARDS_JAIPUR_V1_SPLITZ'],
            ],
            [
                'data'  => [
                    'version'     =>  'nitro_chennai_v1',
                    "version_description" => 'Nitro for chennai',
                ],
                'splitz_experiments'         => ['ANNOUNCEMENT_NITRO_CHENNAI_V1_SPLITZ', 'ANNOUNCEMENT_NITRO_CORPORATE_CARDS_CHENNAI_V1_SPLITZ'],
            ],
            [
                'data'  => [
                    'version'     =>  'project-nitro-gandhinagar-v1',
                    "version_description" => 'Nitro for gandhinagar',
                ],
                'splitz_experiments'         => ['ANNOUNCEMENT_NITRO_GANDHINAGAR_V1_SPLITZ', 'ANNOUNCEMENT_NITRO_CORPORATE_CARDS_GANDHINAGAR_V1_SPLITZ'],
            ],
            [
                'data'  => [
                    'version'     =>  'project-nitro-vadodara-v1',
                    "version_description" => 'Nitro for vadodara',
                ],
                'splitz_experiments'         => ['ANNOUNCEMENT_NITRO_VADODARA_V1_SPLITZ', 'ANNOUNCEMENT_NITRO_CORPORATE_CARDS_VADODARA_V1_SPLITZ'],
            ],
            [
                'data'  => [
                    'version'     =>  'project-nitro-ahmedabad-v1',
                    "version_description" => 'Nitro for ahmedabad',
                ],
                'splitz_experiments'         => ['ANNOUNCEMENT_NITRO_AHMEDABAD_V1_SPLITZ', 'ANNOUNCEMENT_NITRO_CORPORATE_CARDS_AHMEDABAD_V1_SPLITZ'],
            ],
            [
                'data'  => [
                    'version'     =>  'project-nitro-bangalore-v1',
                    "version_description" => 'Nitro for bangalore',
                ],
                'splitz_experiments'         => ['ANNOUNCEMENT_NITRO_BANGALORE_V1_SPLITZ', 'ANNOUNCEMENT_NITRO_CORPORATE_CARDS_BANGALORE_V1_SPLITZ'],
            ],
            [
                'data'  => [
                    'version'     =>  'project-nitro-delhi-v1',
                    "version_description" => 'Nitro for delhi',
                ],
                'splitz_experiments'         => ['ANNOUNCEMENT_NITRO_DELHI_V1_SPLITZ', 'ANNOUNCEMENT_NITRO_CORPORATE_CARDS_DELHI_V1_SPLITZ'],
            ],
            [
                'data'  => [
                    'version'     =>  'project-nitro-mumbai-v1',
                    "version_description" => 'Nitro for mumbai',
                ],
                'splitz_experiments'         => ['ANNOUNCEMENT_NITRO_MUMBAI_V1_SPLITZ', 'ANNOUNCEMENT_NITRO_CORPORATE_CARDS_MUMBAI_V1_SPLITZ'],
            ],
            [
                'data'  => [
                    'version'     =>  'project-nitro-pune-v1',
                    "version_description" => 'Nitro for pune',
                ],
                'splitz_experiments'         => ['ANNOUNCEMENT_NITRO_PUNE_V1_SPLITZ', 'ANNOUNCEMENT_NITRO_CORPORATE_CARDS_PUNE_V1_SPLITZ'],
            ],
            [
                'data'  => [
                    'version'     =>  'project-nitro-gurgaon-v1',
                    "version_description" => 'Nitro for gurgaon',
                ],
                'splitz_experiments'         => ['ANNOUNCEMENT_NITRO_GURGAON_V1_SPLITZ', 'ANNOUNCEMENT_NITRO_CORPORATE_CARDS_GURGAON_V1_SPLITZ'],
            ],
            [
                'data'  => [
                    'version'     =>  'project-nitro-nagpur-v1',
                    "version_description" => 'Nitro for nagpur',
                ],
                'splitz_experiments'         => ['ANNOUNCEMENT_NITRO_NAGPUR_V1_SPLITZ', 'ANNOUNCEMENT_NITRO_CORPORATE_CARDS_NAGPUR_V1_SPLITZ'],
            ],
            [
                'data'  => [
                    'version'     =>  'project-nitro-kolhapur-v1',
                    "version_description" => 'Nitro for kolhapur',
                ],
                'splitz_experiments'         => ['ANNOUNCEMENT_NITRO_KOLHAPUR_V1_SPLITZ', 'ANNOUNCEMENT_NITRO_CORPORATE_CARDS_KOLHAPUR_V1_SPLITZ'],
            ],
            [
                'data'  => [
                    'version'     =>  'project-nitro-coimbatore-v1',
                    "version_description" => 'Nitro for coimbatore',
                ],
                'splitz_experiments'         => ['ANNOUNCEMENT_NITRO_COIMBATORE_V1_SPLITZ', 'ANNOUNCEMENT_NITRO_CORPORATE_CARDS_COIMBATORE_V1_SPLITZ'],
            ],
            [
                'data'  => [
                    'version'     =>  'nitro-othercities-v1',
                    "version_description" => 'Nitro for others cities v1',
                ],
                'splitz_experiments'         => ['ANNOUNCEMENT_NITRO_OTHER_CITIES_V1_SPLITZ', 'ANNOUNCEMENT_NITRO_CORPORATE_CARDS_OTHER_CITIES_V1_SPLITZ'],
            ],
            [
                'data'  => [
                    'version'     =>  'project-nitro-appswitcher',
                    "version_description" => 'Nitro for appswitcher merchants',
                ],
                'splitz_experiments'         => ['ANNOUNCEMENT_NITRO_APP_SWITCHER_SPLITZ'],
            ]

        ],
        'Cardsgolive_Sub_Sep2021' => [
            [
                'data' => [
                    'buttons'     => [
                        [
                            'type'  => 'button',
                            'label' => 'Know More',
                            'url'   => 'https://razorpay.com/docs/announcements/rbi-card-mandate-guidelines/subscriptions/cards/',
                        ],
                    ],
                ],
                'splitz_experiments'         => ['CARDS_GO_LIVE_SUBSCRIPTIONS_SPLITZ'],
            ],
            [
                'data' => [
                    'buttons'     => [
                        [
                            'type'  => 'button',
                            'label' => 'Know More',
                            'url'   => 'https://razorpay.com/docs/announcements/rbi-card-mandate-guidelines/recurring-payments/cards/?utm_campaign=MandateHQ%20awareness%20campaign&utm_source=hs_email&utm_medium=email&_hsenc=p2ANqtz-_2BN3IFsMAeigJhehpUFqTutaXUAvrcwI202D0ApStea1l-E78TVYGUE9hbRPkSTOJentC',
                        ],
                    ],
                ],
                'splitz_experiments'         => ['CARDS_GO_LIVE_CAW_SPLITZ'],
            ]
        ],
        'whats-new-april21-m2mrewards-gtm'=> [
            [
                'data' => [
                    'version' => 'v1'
                ],
                'splitz_experiments'         => ['ANNOUNCEMENT_CHECKOUT_REWARDS_ENABLED_SPLITZ'],
            ],
            [
                'data' => [
                    'version' => 'v2',
                    'l2_content'  => [
                        'content'     => "<p>Thank you for your interest in Checkout Rewards. We are glad to inform you that the wait is finally over. You can now activate Checkout Rewards and watch your sales increase 🚀</p><div class='paragraph'> <b>What are Checkout Rewards?</b><p> Checkout Rewards are essentially FREE rewards that your customers will receive upon completing each successful payment from your website/app.</p><div class='image'> <img src='https://cdn.razorpay.com/static/assets/whats-new/checkout_rewards.svg' /></div></div><div> <b>How does Checkout Rewards work?</b><p>The GIF below shows what the flow looks like from an end users’ perspective.</p><div class='image'> <img src='https://cdn.razorpay.com/static/assets/whats-new/checkout-rewards-flow.gif' /></div><p>The flow is as simple as it can be. Here’s an overview:</p><ol><li>User arrives on the checkout page of a merchant (Acme Corp in this example)</li><li>User selects their preferred payment type and completes the payment</li><li> User receives an email & SMS with payment confirmation and details about the reward</li><li>User can directly redeem the reward on the website/app of reward provider</li></ol><p> Just to make it obvious, the customer did NOT have to select any reward manually. A customer will automatically receive 1 exciting reward on completing a payment.</p> <br /><p> Please Note - Checkout Rewards is completely <b>Free</b> for you and your end users!</p></div><div class='paragraph'> <b>What to expect from Checkout Rewards</b><ol><li>Increase in sales volume 🚀</li><li>Better user experience 😃</li><li>Higher conversion rates 📈</li></ol></div><div> <b>How to activate Checkout Rewards?</b><p> Well, the beauty of Checkout Rewards lies in its simplicity. You can activate Checkout Rewards in less than 30 seconds. All you have to do is:</p><ol><li>Navigate to “Checkout Rewards” on the left panel</li><li>Click “Activate” to activate a Reward</li><li>That’s it. Your customers will start receiving rewards within 24 hours</li></ol><div class='paragraph'> So, what are you waiting for? Click “Try Now” to start using Checkout Rewards!</div></div>",
                        'buttons'     => [
                            [
                                'type'  => 'button',
                                'label' => 'Try Now',
                                'url'   => '/checkout-rewards',
                            ],
                            [
                                'type'  => 'primary-inverted',
                                'label' => 'Read More',
                                'url'   => 'https://razorpay.com/docs/payment-gateway/checkout-rewards/',
                            ],
                        ],
                    ],
                ],
                'splitz_experiments'         => ['ANNOUNCEMENT_CHECKOUT_REWARDS_INTERESTED_SPLITZ'],
            ],
            [
                'data' => [
                    'version' => 'v3',
                    'title' => 'New Checkout Rewards available! 🎁',
                    'description' => 'Activate new Checkout Rewards from Pharmeasy, Zoomcar, Medibuddy & more!',
                    'buttons'     => [
                        [
                            'type'  => 'button',
                            'label' => 'See Rewards',
                            'url'   => '/checkout-rewards',
                        ],
                        [
                            'type'  => 'primary-inverted',
                            'label' => 'Read More',
                            'id'    => 'announcement-details-l2',
                            'url'   => '/announcements/whats-new-april21-m2mrewards-gtm/'
                        ],
                    ],
                    'l2_content'  => [
                        'content'     => "<p>We are glad to inform you that we have recently added new Checkout Rewards such as Medibuddy, Pharmeasy and Zoomcar. Checkout Rewards are essentially FREE rewards that your customers will receive upon completing each successful payment from your website/app.</p><div class='paragraph'> <b>What are Checkout Rewards?</b><p>Checkout Rewards are essentially FREE rewards that your customers will receive upon completing each successful payment from your website/app.</p><div class='image'> <img src='https://cdn.razorpay.com/static/assets/whats-new/checkout_rewards_available.svg' /></div></div><div> <b>How does Checkout Rewards work?</b><p>The GIF below shows what the flow looks like from an end users’ perspective.</p><div class='image'> <img src='https://cdn.razorpay.com/static/assets/whats-new/checkout-rewards-flow.gif' /></div><p>The flow is as simple as it can be. Here’s an overview:</p><ol><li>User arrives on the checkout page of a merchant (Acme Corp in this example)</li><li>User selects their preferred payment type and completes the payment</li><li>User receives an email & SMS with payment confirmation and details about the reward</li><li>User can directly redeem the reward on the website/app of reward provider</li></ol><p>Just to make it obvious, the customer did NOT have to select any reward manually. A customer will automatically receive 1 exciting reward on completing a payment.</p> <br /><p>Please Note - Checkout Rewards is completely <b>Free</b> for you and your end users!</p></div><div class='paragraph'> <b>What to expect from Checkout Rewards</b><ol><li>Increase in sales volume 🚀</li><li>Better user experience 😃</li><li>Higher conversion rates 📈</li></ol></div><div> <b>How to activate Checkout Rewards?</b><p>Well, the beauty of Checkout Rewards lies in its simplicity. You can activate Checkout Rewards in less than 30 seconds. All you have to do is:</p><ol><li>Navigate to “Checkout Rewards” on the left panel</li><li>Click “Activate” to activate a Reward</li><li>That’s it. Your customers will start receiving rewards within 24 hours</li></ol><div class='paragraph'>So, what are you waiting for? Click “See Rewards” to start using Checkout Rewards!</div></div>",
                        'buttons'     => [
                            [
                                'type'  => 'button',
                                'label' => 'See Rewards',
                                'url'   => '/checkout-rewards',
                            ],
                            [
                                'type'  => 'primary-inverted',
                                'label' => 'Read More',
                                'url'   => 'https://razorpay.com/docs/payment-gateway/checkout-rewards/',
                            ],
                        ],
                    ],
                ],
                'splitz_experiments'         => ['ANNOUNCEMENT_CHECKOUT_REWARDS_LIVE_SPLITZ'],
            ],
            [
                'data' => [
                    'version' => 'v4',
                    'title' => 'Checkout Rewards now available! 🎁',
                    'description' => 'Delight your customers with exciting rewards after every purchase & watch your sales grow, for FREE. Go Live in less than 30 seconds!',
                    'l2_content'  => [
                        'content'     => "<p>We are glad to inform you that the wait is finally over. Checkout Rewards is now available for your account for FREE!</p><div class='paragraph'> <b>What are Checkout Rewards?</b><p> Checkout Rewards are essentially FREE rewards that your customers will receive upon completing each successful payment from your website/app.</p><div class='image'> <img src='https://cdn.razorpay.com/static/assets/whats-new/checkout_rewards.svg' /></div></div><div> <b>How does Checkout Rewards work?</b><p>The GIF below shows what the flow looks like from an end users’ perspective.</p><div class='image'> <img src='https://cdn.razorpay.com/static/assets/whats-new/checkout-rewards-flow.gif' /></div><p>The flow is as simple as it can be. Here’s an overview:</p><ol><li>User arrives on the checkout page of a merchant (Acme Corp in this example)</li><li>User selects their preferred payment type and completes the payment</li><li> User receives an email & SMS with payment confirmation and details about the reward</li><li>User can directly redeem the reward on the website/app of reward provider</li></ol><p> Just to make it obvious, the customer did NOT have to select any reward manually. A customer will automatically receive 1 exciting reward on completing a payment.</p> <br /><p> Please Note - Checkout Rewards is completely <b>Free</b> for you and your end users!</p></div><div class='paragraph'> <b>What to expect from Checkout Rewards</b><ol><li>Increase in sales volume 🚀</li><li>Better user experience 😃</li><li>Higher conversion rates 📈</li></ol></div><div> <b>How to activate Checkout Rewards?</b><p> Well, the beauty of Checkout Rewards lies in its simplicity. You can activate Checkout Rewards in less than 30 seconds. All you have to do is:</p><ol><li>Navigate to “Checkout Rewards” on the left panel</li><li>Click “Activate” to activate a Reward</li><li>That’s it. Your customers will start receiving rewards within 24 hours</li></ol><div class='paragraph'> So, what are you waiting for? Click “Try Now” to start using Checkout Rewards!</div></div>",
                        'buttons'     => [
                            [
                                'type'  => 'button',
                                'label' => 'Try Now',
                                'url'   => '/checkout-rewards',
                            ],
                            [
                                'type'  => 'primary-inverted',
                                'label' => 'Read More',
                                'url'   => 'https://razorpay.com/docs/payment-gateway/checkout-rewards/',
                            ],
                        ],
                    ],
                ],
                'splitz_experiments'         => ['ANNOUNCEMENT_CHECKOUT_REWARDS_INTERESTED_SEGMENT2_SPLITZ'],
            ],
            [
                'data' => [
                    'version' => 'v5',
                    'description' => 'Delight your customers with exciting rewards after every purchase & watch your sales grow, for FREE. Go Live in less than 30 seconds!',
                ],
                'splitz_experiments'         => ['ANNOUNCEMENT_CHECKOUT_REWARDS_GO_LIVE_READY_SPLITZ'],
            ],
        ]
    ];

    const nitroSplitzExperimentsList = [
        'ANNOUNCEMENT_NITRO_HYDERABAD_SPLITZ',
        'ANNOUNCEMENT_NITRO_KOLKATA_V1_SPLITZ',
        'ANNOUNCEMENT_NITRO_CHENNAI_V1_SPLITZ',
        'ANNOUNCEMENT_NITRO_SURAT_V1_SPLITZ',
        'ANNOUNCEMENT_NITRO_JAIPUR_V1_SPLITZ',
        'ANNOUNCEMENT_NITRO_GANDHINAGAR_V1_SPLITZ',
        'ANNOUNCEMENT_NITRO_VADODARA_V1_SPLITZ',
        'ANNOUNCEMENT_NITRO_AHMEDABAD_V1_SPLITZ',
        'ANNOUNCEMENT_NITRO_BANGALORE_V1_SPLITZ',
        'ANNOUNCEMENT_NITRO_DELHI_V1_SPLITZ',
        'ANNOUNCEMENT_NITRO_MUMBAI_V1_SPLITZ',
        'ANNOUNCEMENT_NITRO_PUNE_V1_SPLITZ',
        'ANNOUNCEMENT_NITRO_GURGAON_V1_SPLITZ',
        'ANNOUNCEMENT_NITRO_NAGPUR_V1_SPLITZ',
        'ANNOUNCEMENT_NITRO_KOLHAPUR_V1_SPLITZ',
        'ANNOUNCEMENT_NITRO_COIMBATORE_V1_SPLITZ',
        'ANNOUNCEMENT_NITRO_OTHER_CITIES_V1_SPLITZ',
        'ANNOUNCEMENT_NITRO_APP_SWITCHER_SPLITZ'
    ];
    const nitroCorporateCardsSplitzExperimentsList = [
        'ANNOUNCEMENT_NITRO_CORPORATE_CARDS_HYDERABAD_SPLITZ',
        'ANNOUNCEMENT_NITRO_CORPORATE_CARDS_KOLKATA_V1_SPLITZ',
        'ANNOUNCEMENT_NITRO_CORPORATE_CARDS_CHENNAI_V1_SPLITZ',
        'ANNOUNCEMENT_NITRO_CORPORATE_CARDS_SURAT_V1_SPLITZ',
        'ANNOUNCEMENT_NITRO_CORPORATE_CARDS_JAIPUR_V1_SPLITZ',
        'ANNOUNCEMENT_NITRO_CORPORATE_CARDS_GANDHINAGAR_V1_SPLITZ',
        'ANNOUNCEMENT_NITRO_CORPORATE_CARDS_VADODARA_V1_SPLITZ',
        'ANNOUNCEMENT_NITRO_CORPORATE_CARDS_AHMEDABAD_V1_SPLITZ',
        'ANNOUNCEMENT_NITRO_CORPORATE_CARDS_BANGALORE_V1_SPLITZ',
        'ANNOUNCEMENT_NITRO_CORPORATE_CARDS_DELHI_V1_SPLITZ',
        'ANNOUNCEMENT_NITRO_CORPORATE_CARDS_MUMBAI_V1_SPLITZ',
        'ANNOUNCEMENT_NITRO_CORPORATE_CARDS_PUNE_V1_SPLITZ',
        'ANNOUNCEMENT_NITRO_CORPORATE_CARDS_GURGAON_V1_SPLITZ',
        'ANNOUNCEMENT_NITRO_CORPORATE_CARDS_NAGPUR_V1_SPLITZ',
        'ANNOUNCEMENT_NITRO_CORPORATE_CARDS_KOLHAPUR_V1_SPLITZ',
        'ANNOUNCEMENT_NITRO_CORPORATE_CARDS_COIMBATORE_V1_SPLITZ',
        'ANNOUNCEMENT_NITRO_CORPORATE_CARDS_OTHER_CITIES_V1_SPLITZ',
        'ANNOUNCEMENT_NITRO_CORPORATE_CARDS_TEST_ACCOUNT_SPLITZ',
    ];

    public static function getNotifications(): array
    {
        return self::NOTIFICATIONS;
    }

    public static function getSplitzBasedNotifications(): array
    {
        return self::SPLITZ_NOTIFICATION;
    }

    public static function getAnnouncementToSubCampaignDetailsMapping(): array
    {
        return self::ANNOUNCEMENT_ID_TO_SUB_CAMPAIGN_DETAIL_MAPPING;
    }
}
