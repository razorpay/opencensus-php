<?php

namespace App\Merchant\Notifications;

use App\MerchantDetails;

class Constants
{
    const NOTIFICATIONS = [
        [
            'title'       => 'You are eligible for our badge of trust',
            'description' => 'Razorpay Trusted Badge helps you increase conversion on checkout and can be added to your point of sale',
            'start_ts'    => 1616594400,
            'end_ts'      => 1622505599,
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/rtb_announcement.svg',
            'track_event' => true,
            'id'          => 'trusted-badge-mar2021',
            'buttons'     => [
                [
                    'type'  => 'primary-inverted',
                    'label' => 'Know More',
                    'url'   => '/trustedbadge',
                ],
            ],
            'filters'     => [
                'activation_status' => ['activated'],
                'not_features' => ["rzp_trusted_badge"],
            ],
        ],
        [
            'title'       => 'Settlements on Hold',
            'description' => 'Settlements on Hold: Your settlements are on hold due to regulatory requirements. Please provide clarification on the email received on your registered email to resolve the issue',
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/rtb_announcement.svg',
            'track_event' => true,
            'id'          => 'bulk-risk-action-merchant-FOH',
            'filters'     => [
                'tags' => ["bulk_action_merchant_foh"]
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
            'id'          => 'bulk-risk-action-merchant-disabled',
            'filters'     => [
                'tags' => ["bulk_action_disabled"]
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
            'title'       => 'You have earned the Razorpay Trusted Badge!',
            'description' => 'You are now a Razorpay trusted merchant. The badge has been added to your checkout and is ready to be flaunted.',
            'start_ts'    => 1616594400,
            'end_ts'      => 1622505599,
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/rtb_announcement.svg',
            'track_event' => true,
            'id'          => 'trusted-badge-enabled',
            'buttons'     => [
                [
                    'type'  => 'primary-inverted',
                    'label' => 'Know More',
                    'url'   => '/trustedbadge',
                ],
            ],
            'filters'     => [
                'activation_status' => ['activated'],
                'features' => ["rzp_trusted_badge"],
            ],
        ],
        [
            'title'       => 'The Payments Mobile App is Live!',
            'description' => 'Track payments, create payment links and issue refunds from anywhere with the new Payments mobile app. Get the mobile app now.',
            'start_ts'    => 1608229800,
            'end_ts'      => 1610994599,
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/payments-mobile-app.svg',
            'track_event' => true,
            'id'          => 'Payments-Mobile-App',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Download on iOS',
                    'url'   => 'https://apps.apple.com/in/app/razorpay-payments-dashboard/id1497250144',
                ],
                [
                    'type'  => 'button',
                    'label' => 'Download on Android',
                    'url'   => 'https://play.google.com/store/apps/details?id=com.razorpay.payments.app',
                ]
            ],
            'filters'     => [
                'activation_status' => ['activated'],
                'role'  => ['owner', 'admin', 'manager', 'operations'],
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
            'title'       => 'Get 1.65% pricing with RazorpayX',
            'description' => 'Open a current account with RazorpayX & reduce your transaction fee to 1.65%.',
            'start_ts'    => 1621987200,
            'end_ts'      => 1629936000,
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/nitro.svg',
            'id'          => 'projectNitro',
            'campaign'    => 'nitro',
            "target_product_feature" => 'XCA',
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
                'experiments'         => ['nitro_hyderabad_v2', 'nitro_hyderabad_v3', 'nitro_midmarket_mumbai_v1'],
            ],
        ],
        [
            'title'       => 'Get 1.65% pricing with RazorpayX',
            'description' => 'Open a current account with RazorpayX & reduce your transaction fee to 1.65%.',
            'start_ts'    => 1621987200,
            'end_ts'      => 1629936000,
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/nitro.svg',
            'id'          => 'projectNitro',
            'campaign'    => 'nitro',
            "target_product_feature" => 'XCA',
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
                'splitz_experiments'         => self::nitroSplitzExperimentsList,
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
            'id'          => 'whats-new-MAY21-CA-GROWTH',
            'title'       => 'Approved for Cash Advance!',
            'description' => 'Withdraw additional cash for business needs in seconds 24x7. You are pre-approved for a FREE line of credit from Razorpay.',
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/cash_advance_icon.svg',
            'image_url'   => 'https://cdn.razorpay.com/static/assets/whats-new/cash-advance/cash-advance-showcase.gif',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Enable now',
                    'id'    => 'cash-advance-cta-1',
                    'url'   => '/capital/cash-advance',
                ],
                [
                    'type'  => 'primary-inverted',
                    'label' => 'Know more',
                    'id'    => 'announcement-details-l2',
                    'url'   => '/announcements/whats-new-MAY21-CA-GROWTH/'
                ],
            ],
            'start_ts'    => 1621254600,
            'end_ts'      => 1625077799,
            'filters'     => [
                'features' => ['loc'],
                'not_features' => ['withdraw_loc'],
            ],
            'l2_content'  => [
                'content'     => "<div><div>Don’t run out of cash and don’t stop growing by getting additional money for urgent business needs at any time or day. Withdraw instantly, repay easily and borrow again when needed.</div><div class='paragraph'><b>Instant cash anytime even on holidays: </b>Get backup for unexpected cash needs without a fresh application process every time</div> <div class='image' style='padding: 10px 0;'><img src='https://cdn.razorpay.com/static/assets/whats-new/cash-advance/cash-advance-dashboard.gif'></div><div class='paragraph'><b>Customer payments in advance: </b>Borrow expected revenue amount and repay from future settlements with actual customer payments</div><div><b>No interest until you use it: </b>Pay interest only when you withdraw the amount and only for the days used before repaying</div><div class='paragraph'><b>Easy but safe and reliable: </b>We have made Cash Advance not just easy to use but also safe and responsible in terms of repayment and risk to you.</div> </div>",
                'buttons'     => [
                    [
                        'type'  => 'button',
                        'label' => 'Get it now!',
                        'url'   => '/capital/cash-advance',
                        'id'    => 'cash-advance-cta-1',
                    ],
                ],
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
        ]
    ];

    //insert data in data field, that is dynamically loaded based on the sub-campaign
    const ANNOUNCEMENT_ID_TO_SUB_CAMPAIGN_DETAIL_MAPPING = [
        'projectNitro'=>[
            [
                'data'  => [
                    'version'     =>  'nitro_hyderabad_v2',
                    "version_description" => 'Nitro for hyderabad',
                ],
                'experiments'         => ['nitro_hyderabad_v2'],
            ],
            [
                'data'  => [
                    'version'     =>  'nitro_hyderabad_v3',
                    "version_description" => 'Nitro for hyderabad',
                ],
                'experiments'         => ['nitro_hyderabad_v3'],
            ],
            [
                'data'  => [
                    'version'     =>  'nitro_hyderabad_v4',
                    "version_description" => 'Nitro for hyderabad',
                ],
                'splitz_experiments'         => ['ANNOUNCEMENT_NITRO_HYDERABAD_SPLITZ'],
            ],
            [
                'data'  => [
                    'version'     =>  'nitro_kolkata_v1',
                    "version_description" => 'Nitro for kolkata',
                ],
                'splitz_experiments'         => ['ANNOUNCEMENT_NITRO_KOLKATA_V1_SPLITZ'],
            ],
            [
                'data'  => [
                    'version'     =>  'nitro_surat_v1',
                    "version_description" => 'Nitro for surat',
                ],
                'splitz_experiments'         => ['ANNOUNCEMENT_NITRO_SURAT_V1_SPLITZ'],
            ],
            [
                'data'  => [
                    'version'     =>  'nitro_jaipur_v1',
                    "version_description" => 'Nitro for jaipur',
                ],
                'splitz_experiments'         => ['ANNOUNCEMENT_NITRO_JAIPUR_V1_SPLITZ'],
            ],
            [
                'data'  => [
                    'version'     =>  'nitro_chennai_v1',
                    "version_description" => 'Nitro for chennai',
                ],
                'splitz_experiments'         => ['ANNOUNCEMENT_NITRO_CHENNAI_V1_SPLITZ'],
            ],
            [
                'data'  => [
                    'version'     =>  'project-nitro-gandhinagar-v1',
                    "version_description" => 'Nitro for gandhinagar',
                ],
                'splitz_experiments'         => ['ANNOUNCEMENT_NITRO_GANDHINAGAR_V1_SPLITZ'],
            ],
            [
                'data'  => [
                    'version'     =>  'project-nitro-vadodara-v1',
                    "version_description" => 'Nitro for vadodara',
                ],
                'splitz_experiments'         => ['ANNOUNCEMENT_NITRO_VADODARA_V1_SPLITZ'],
            ],
            [
                'data'  => [
                    'version'     =>  'project-nitro-ahmedabad-v1',
                    "version_description" => 'Nitro for ahmedabad',
                ],
                'splitz_experiments'         => ['ANNOUNCEMENT_NITRO_AHMEDABAD_V1_SPLITZ'],
            ],
            [
                'data'  => [
                    'version'     =>  'project-nitro-bangalore-v1',
                    "version_description" => 'Nitro for bangalore',
                ],
                'splitz_experiments'         => ['ANNOUNCEMENT_NITRO_BANGALORE_V1_SPLITZ'],
            ],
            [
                'data'  => [
                    'version'     =>  'nitro_midmarket_mumbai_v1',
                    "version_description" => 'Nitro for mumbai mid market',
                ],
                'experiments'         => ['nitro_midmarket_mumbai_v1'],
            ],
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
    ];

    public static function getNotifications(): array
    {
        return self::NOTIFICATIONS;
    }

    public static function getAnnouncementToSubCampaignDetailsMapping(): array
    {
        return self::ANNOUNCEMENT_ID_TO_SUB_CAMPAIGN_DETAIL_MAPPING;
    }
}
