import { isProductionEnv } from 'common/utils/rzp-utils';
import tallyIcon from 'assets/x_banking_widget/tally_icon.svg';
import taxIcon from 'assets/x_banking_widget/tax_icon.svg';
import payoutIcon from 'assets/x_banking_widget/payout_icon.svg';
import vendorIcon from 'assets/x_banking_widget/vendor_icon.svg';
import otpIcon from 'assets/x_banking_widget/otp_icon.svg';
import reconIcon from 'assets/x_banking_widget/recon_icon.svg';
import razorpayxLogo from 'assets/x_banking_widget/razorpayx_logo.svg';

const isProd = isProductionEnv();

export const fallbackViewData = {
  error: null,
  loading: false,
  x_banking_widgets: [
    {
      id: 'test_fallback_x_banking_widget',
      slide: [
        {
          background_illustration: {
            alt_text: 'Background Illustration',
            url: `${window.cdnBaseUrl}/static/assets/razorpayx/razorpayx-connected-banking-bg.svg`,
          },
          cards: [
            {
              illustration: {
                alt_text: 'tally icon',
                url: tallyIcon,
              },
              priority: '0',
              text: 'Integrate with <b>Tally, ZohoBooks</b>, & more',
            },
            {
              illustration: {
                alt_text: 'tax icon',
                url: taxIcon,
              },
              priority: '1',
              text: 'Pay <b>taxes</b> in 30 minutes',
            },
            {
              illustration: {
                alt_text: 'payout icon',
                url: payoutIcon,
              },
              priority: '2',
              text: 'Approve payouts <b>on the Mobile App</b>',
            },
            {
              illustration: {
                alt_text: 'vendor icon',
                url: vendorIcon,
              },
              priority: '3',
              text: '1-click <b>Vendor Payments</b>',
            },
            {
              illustration: {
                alt_text: 'otp icon',
                url: otpIcon,
              },
              priority: '4',
              text: 'Go <b>OTP</b> free with easy approvals',
            },
            {
              illustration: {
                alt_text: 'recon icon',
                url: reconIcon,
              },
              priority: '5',
              text: 'Automated <b>Reconciliation</b>',
            },
          ],
          get_started_cta: {
            label: 'Get started',
            type: 'button',
            url: `${
              isProd ? 'https://x.razorpay.com' : 'https://x.dev.razorpay.in'
            }/welcome?campaign=pg_x_widget&intent=current_account`,
          },
          headline:
            'Gear up to scale faster with credit cards, payments, taxes, and accounting — automated with a RazorpayX Account.',
          left_illustration: {
            alt_text: 'Banking Illustration',
            url: `${window.cdnBaseUrl}/static/assets/razorpayx/widget/razorpayx-landing-image.png`,
          },
          logo: {
            alt_text: 'razorpayx',
            url: razorpayxLogo,
          },
          next_cta: {
            label: 'Get Started',
            type: 'button',
          },
          sub_headline: 'Banking that powers finances for fast-growing businesses',
        },
      ],
    },
  ],
};
