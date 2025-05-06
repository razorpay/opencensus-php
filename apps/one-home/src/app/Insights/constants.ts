import {
  ArrowDownRightIcon,
  AlertTriangleIcon,
  ArrowUpRightIcon,
  BulkPayoutsIcon,
} from '@razorpay/blade/components';
import { DateRangeOption } from './types';
const payrollUnused = require('../../assets/payroll_unused.png');
const bankingUnused = require('../../assets/banking_unused.png');
const comingSoon = require('../../assets/coming_soon.png');
const paymentsUnused = require('../../assets/payments_unused.png');

export const dateFilterOptions: Array<{ key: DateRangeOption; value: string }> = [
  {
    key: 'yesterday',
    value: 'Yesterday',
  },
  {
    key: 'last_7_days',
    value: 'Last Week',
  },
  {
    key: 'last_30_days',
    value: 'Last 30 Days',
  },
];

export const staticContent = {
  paymentInsightsHeader: 'Payment insights',
  dashboardUrl: '/dashboard',
  insightXUrl: '/insight-x',
  viewDetailsText: 'View details',
  seeDetailedInsightsText: 'See detailed insights',
  otherInsightsHeader: 'Other insights',
  showDetailedBreakdownText: 'Show detailed breakdown',
  rayInsightBadgeText: 'Insight by RAY',
  getStartedText: 'Get started',
  comingSoonText: 'Coming soon',
  paymentInsightWidgetId: 'one_home_payment_insights',
  otherInsightWidgetId: 'one_home_other_insights',
  errorText:
    "We couldn't load the insights due to a technical issue. Please try again or check back later.",
  nonInsightBtnLabel: 'Navigate to Growth page',
  insightsLinkLabel: 'View details link',
  mobileBtnLabel: 'Navigate to detailed breakdown',
  insightsBtnLabel: 'Navigate to detailed insights',
};

export const predefinedMappings: Record<string, string> = {
  upi: 'UPI',
  emi: 'EMI',
  card: 'Card',
  netbanking: 'Netbanking',
  wallet: 'Wallet',
  transfer: 'Transfer',
  bank_transfer: 'Bank Transfer',
  aeps: 'AEPS',
  emandate: 'E-Mandate',
  cardless_emi: 'Cardless EMI',
  paylater: 'PayLater',
  nach: 'NACH',
  app: 'App',
  cod: 'COD',
  offline: 'Offline',
  unselected: 'Unselected',
  intl_bank_transfer: 'International Bank Transfer',
  fpx: 'FPX',
  duitnow_pay: 'DuitNow Pay',
  razorpay_account: 'Razorpay Account',
  gift_cards: 'Gift Cards',
};

export const insightCardsStaticData = {
  payment: {
    title: 'Payments collected',
    tooltipContent: 'Total money collected via all payment collection methods',
    defaultIcon: ArrowDownRightIcon,
    redirectionUrl: '/payments',
    lockCardDescription:
      'Your organisation is collecting payments through Razorpay. You can ask your admin for access.',
    emptyCardDescription: 'Start collecting payments to get insights and recommendations.',
  },
  success_rate: {
    title: 'Success Rate',
    tooltipContent: 'This is the percentage of (successful payments / total payments attempted)',
    defaultIcon: AlertTriangleIcon,
    redirectionUrl: '/success-rate',
    lockCardDescription:
      'Your organisation is collecting payments through Razorpay. You can ask your admin for access.',
    emptyCardDescription: 'Start collecting payments to get insights and recommendations.',
  },
  refund: {
    title: 'Refunds',
    tooltipContent: '',
    defaultIcon: ArrowUpRightIcon,
    redirectionUrl: '/refunds',
    lockCardDescription:
      'Your organisation is collecting payments through Razorpay. You can ask your admin for access.',
    emptyCardDescription: 'Start collecting payments to get insights and recommendations.',
  },
  payout: {
    title: 'Payouts',
    tooltipContent: '',
    defaultIcon: BulkPayoutsIcon,
    redirectionUrl: '/banking/insights/?utm_source=r1_dashboard&utm_content=home_other_insight',
    lockCardDescription:
      'Your organisation is disbursing vendor payments/ refunds through Razorpay. You can ask your admin for access.',
    emptyCardDescription:
      'Start disbursing vendor payments/ refunds to get insights and recommendations.',
  },
};

export const nonInsightCardsStaticData = {
  payout: {
    title: 'Spends',
    description: 'Handle payouts and banking',
    path: '/banking/?utm_source=r1_dashboard&utm_content=home_other_insight',
  },
  payroll: {
    title: 'Employees and payroll',
    description: 'Run payroll with razorpay',
    path: '/payroll/?utm_source=r1_dashboard&utm_content=home_other_insight',
  },
};

export const otherInsightsImageMapping = {
  payroll: payrollUnused,
  payout: bankingUnused,
  earnings: paymentsUnused,
  customers: comingSoon,
};
