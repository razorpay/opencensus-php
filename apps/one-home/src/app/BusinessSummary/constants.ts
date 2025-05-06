export const WORKSPACE_WIDTH = 1200;

export const CARD_WIDTH_MIN_MOBILE_VIEW = `${0.18 * WORKSPACE_WIDTH}px`;
export const EARNINS_CARD_MIN_WIDTH = `${0.3 * WORKSPACE_WIDTH}px`;
export const SPENDS_CARD_MIN_WIDTH = `${0.3 * WORKSPACE_WIDTH}px`;
export const BALANCE_CARD_MIN_WIDTH = `${0.18 * WORKSPACE_WIDTH}px`;

const CARD_SHIMMER_WIDTH = '296px';
const CARD_SHIMMER_HEIGHT = '208px';
const CARD_SHIMMER_MIN_WIDTH = '224px';
const BALANCE_CARD_SHIMMER_WIDTH = '224px';
const BALANCE_CARD_SHIMMER_MIN_WIDTH = '196px';
const BALANCE_CARD_SHIMMER_HEIGHT = '144px';

export const EASY_ONBOARDING_URL = 'https://easy.razorpay.com/onboarding';

const messages = {
  businessSummarySection: {
    title: 'Your business with Razorpay',
    errorMessage:
      'We couldn’t load the buiness information due to a technical issue. Please try again or check back later.',
    analytics: {
      title: 'Business Summary',
      widgetId: 'one_home_business_summary',
    },
  },
  earningsCard: {
    title: 'EARNINGS',
    growthCardTitle: 'Payments',
    growthCardDescription:
      'Collect payments online or offline, within India or internationally, through seamless payments.',
    growthCardCtaLabel: 'Unlock Payments',
    lockedCardTitle: 'Payments',
    lockedCardDescription:
      'Your organisation is collecting payments through Razorpay. You can ask your admin for access.',
    errorMessage:
      'We couldn’t load the Earnings information due to a technical issue. Please try again or check back later.',
    analytics: {
      widgetId: 'one_home_business_summary',
      subwidgetId: 'one_home_business_summary_earnings_card',
      cardType: 'Earnings',
    },
  },
  balanceCard: {
    currentAccountBtnText: 'Get a Current Account',
    errorMessage:
    'We couldn’t load the Balance information due to a technical issue. Please try again or check back later.',
    analytics: {
      widgetId: 'one_home_business_summary',
      subwidgetId: 'one_home_business_summary_balance_card',
      cardType: 'Balance',
    },
  },
  spendsCard: {
    title: 'SPENDS',
    errorMessage:
      'We couldn’t load the Spends information due to a technical issue. Please try again or check back later.',
    growthCardTitle: 'Banking+',
    growthCardDescription:
      'A full-stack business finance suite to pay your vendors, employees or your customers.',
    growthCardCtaLabel: 'Unlock Banking+',
    lockedCardTitle: 'Banking',
    lockedCardDescription:
      'Your organisation is banking with RazorpayX. You can ask your admin for access.',
    analytics: {
      widgetId: 'one_home_business_summary',
      subwidgetId: 'one_home_business_summary_spends_card',
      cardType: 'Spends',
    },
  },
};

export {
  messages,
  CARD_SHIMMER_WIDTH,
  CARD_SHIMMER_HEIGHT,
  CARD_SHIMMER_MIN_WIDTH,
  BALANCE_CARD_SHIMMER_WIDTH,
  BALANCE_CARD_SHIMMER_HEIGHT,
  BALANCE_CARD_SHIMMER_MIN_WIDTH,
};
