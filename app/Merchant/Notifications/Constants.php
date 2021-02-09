<?php

namespace App\Merchant\Notifications;

use App\MerchantDetails;

class Constants
{
    const NOTIFICATIONS = [
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
            'start_ts'    => 1597390475,
            'end_ts'      => 1617167373,
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/nitro.svg',
            'id'          => 'projectNitro',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Learn More',
                    'url'   => '',
                    'id'    => 'announcement-projectNitro-cta1',
                ],
            ],
            'filters'     => [
                'experiments'         => ['project_nitro', 'project_nitro_1'],
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
        [
            'id'          => 'SHOW_CREDIT_SCORE',
            'title'       => 'Free Credit Score!',
            'description' => 'Click Here to get your credit score along with the credit report for FREE!',
            'start_ts'    => 1604320769,
            'end_ts'      => 1617167373,
            'icon'        => 'https://cdn.razorpay.com/static/assets/notifs/badge.svg',
            'buttons'     => [
                [
                    'type'  => 'button',
                    'label' => 'Get Free Credit Report',
                    'url'   => '/dashboard#creditscore',
                ],
            ],
            'filters'     => [
                'features'  => ['show_credit_score'],
                'role'  => ['owner'],
            ],
        ],
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
                'content'     => "<div class='title'> <img src='https://cdn.razorpay.com/static/assets/notifs/payment-button.svg' width='32px' /><p>Introducing Subscriptions on Payment Button</p></div><div><div>Razorpay was the first Indian player to launch Subscriptions for the domestic market. We want to do it again. We want to punch a bit higher this time. We are now the first Indian player to launch a no-code subscription button for the Indian market.</div><div class='paragraph'> <b>What is Subscriptions on Payment button?</b><p>Subscriptions on payment button lets one collect subscriptions or single payments by providing a convenient snippet of code that can be pasted on any webpage without any specific sort of integration requirement. Razorpay subscription button is a simple no-code tool that lets you collect subscription payments from your customers without having to integrate APIs on subscriptions. You can even collect payments as low as INR 10!</p></div><div> <b>How does it help your business?</b><p>Subscriptions help you in increasing the CTLV i.e your customer’s lifetime value. Your customers are more likely to pay you more over a period of time and it is not just the power of time but also related to the fact that a growing business has upsell and cross-sell  more offerings. You also maintain a longer relationship with your customers. You are here to stay and offer the same quality of services over and over - this helps building trust. A better relationship leads to better stickiness. In short a perfect recipe for loyalty.</p></div><div class='paragraph'> <b>What are the popular use-cases on subscriptions?</b><p>If you’re still wondering how this helps you, let's take a look at what some players in the market are using subscriptions for:</p></div><div><ol><li> <b>Gated content:</b> If you’re offering your own courses on a weekly or a monthly basis behind a paywall, copy and paste a few lines of code on your website to make it subscription ready. </li><li class='paragraph'> <b>Crowdfunding a passion project:</b> Your fans can now become customers. You fund your project yourself and plan a roadmap with ease.  </li><li> <b>Recurring product packs:</b> If you’re looking to start out on a D2C brand with novel content every week, we solve payments for you.  </li><li class='paragraph'> <b>Supporting a cause:</b> This is the best way to have a long term relationship with your patrons to collect smaller amounts over a longer period of time</li></ol></div></div>",
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
                'experiments'         => ['shopify_gtm_notification'],
            ]
        ],
    ];

    public static function getNotifications(): array
    {
        return self::NOTIFICATIONS;
    }
}
