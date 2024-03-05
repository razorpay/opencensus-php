import paymentsFtuxImg from 'apps/self-serve/src/assets/payments-ftux.svg';
import failedPaymentsFtuxImg from 'apps/self-serve/src/assets/failed-payments-ftux.svg';
import refundsFtuxImg from 'apps/self-serve/src/assets/refunds-ftux.svg';
import { Config } from 'apps/self-serve/src/App/Transactions/v2/common/components/NoSearchResult/types';
import { Page } from 'apps/self-serve/src/App/Transactions/v2/common/types';

const { FAILED_PAYMENTS, PAYMENTS, REFUNDS } = Page;

export const config = (page: Page): Config => {
  switch (page) {
    case FAILED_PAYMENTS:
      return {
        image: {
          src: failedPaymentsFtuxImg,
          alt: 'failed payments',
        },
        title: 'Track your failed payments',
        subtitle:
          'Unsuccessful payments due to customer, bank, or business related errors appear here',
      };
    case REFUNDS:
      return {
        image: {
          src: refundsFtuxImg,
          alt: 'refunds',
        },
        title: 'Issue full, partial, or instant refunds',
        subtitle: 'Refunds to customers get deducted from your current balance and appear here',
        link: {
          href: 'https://razorpay.com/docs/payments/refunds/',
          linkText: 'Refunds guide',
        },
      };
    case PAYMENTS:
    default:
      return {
        image: {
          src: paymentsFtuxImg,
          alt: 'payments',
        },
        title: 'Start collecting payments',
        subtitle:
          'Use Payment Links, Payment Pages, Payment Gateway, and others to collect payments from your customers',
        link: {
          href: 'https://razorpay.com/docs/#home-payments',
          linkText: 'Explore payment products',
        },
      };
  }
};
