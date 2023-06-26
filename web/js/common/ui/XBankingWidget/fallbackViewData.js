import { isProductionEnv } from 'common/utils/rzp-utils';
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
          variant: 'V2',
          headline: 'Manage your businesses’ finances',
          sub_headline: 'all in one place.',
          left_illustration: {
            alt_text: 'Banking Illustration',
            url: `${window.cdnBaseUrl}/static/assets/campaignhq/banking-widget-V2-left-illustration.png`,
          },
          cards: [
            {
              illustration: {
                alt_text: 'razorpayx logo',
                prefix: 'The',
                postfix: 'advantage',
                url: razorpayxLogo,
              },
              text: 'Go OTP-free with easy approvals,250 Free NEFT/RTGS/UPI Payouts every month,Instant beneficiary addition & smart dashboard for tax compliance, Premium Support & Relationship Manager',
              footer: 'all from one dashboard.',
              priority: '0',
            },
            {
              heading: 'Standard banking features',
              subheading: '(also available in X)',
              text: 'Chequebook, Unlimited deposits, Withdrawals, Account statements',
              priority: '1',
            },
          ],
          get_started_cta: {
            label: 'Open Current Account',
            type: 'button',
            url: `${
              isProd ? 'https://x.razorpay.com' : 'https://x.dev.razorpay.in'
            }/welcome?campaign=pg_x_widget&intent=current_account`,
          },
          next_cta: {
            label: 'Get Started',
            type: 'button',
          },
        },
      ],
    },
  ],
};
