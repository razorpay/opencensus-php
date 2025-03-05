import GstCompliantImage from '@apps/digital-bills/src/assets/icons/gst-compliant.svg';
import AdsOnBillsImage from '@apps/digital-bills/src/assets/icons/ads-on-bills.svg';
import ConsumerInsights from '@apps/digital-bills/src/assets/icons/consumer-insights.svg';
import ReportingAndAnalytics from '@apps/digital-bills/src/assets/icons/reporting-and-analytics.svg';

export const FEATURE_CARDS_DATA = [
  {
    id: 1,
    image: GstCompliantImage,
    title: 'Convert paper bills into GST-compliant digital bills',
    description:
      'Generate tax-compliant digital bills based on product price and pin code. Send bills directly to customers’ phones through WhatsApp, Email & SMS.',
  },
  {
    id: 2,
    image: AdsOnBillsImage,
    title: 'Drive traffic with ads on digital bills',
    description:
      'Engage customers with promotions, personalized offers and discounts through the engagement touchpoints and drive repeat purchases.',
  },
  {
    id: 3,
    image: ConsumerInsights,
    title: 'Gather deeper consumer insights',
    description:
      'Embed feedback collection and surveys to collect additional customer data to understand customers better and retain them',
  },
  {
    id: 4,
    image: ReportingAndAnalytics,
    title: 'Reporting & analytics for actionable insights',
    description:
      'Manage bills, POS, stores and users from a unified dashboard. Track real-time sales of online and offline stores.',
  },
];

export const STORE_COUNT_OPTIONS_MAP = {
  '1-9': {
    minStoreCount: 1,
    maxStoreCount: 9,
  },
  '10-24': {
    minStoreCount: 10,
    maxStoreCount: 24,
  },
  '25-49': {
    minStoreCount: 25,
    maxStoreCount: 49,
  },
  '50+': {
    minStoreCount: 50,
    maxStoreCount: null,
  },
};

export const STORES_COUNT_OPTIONS = [
  {
    label: '1 - 9',
    value: '1-9',
  },
  {
    label: '10 - 24',
    value: '10-24',
  },
  {
    label: '25 - 49',
    value: '25-49',
  },
  {
    label: '50+',
    value: '50+',
  },
];

export const ONBOARDING_STATUS = {
  NEW: 'NEW',
  ACTIVATED: 'ACTIVATED',
  PENDING: 'PENDING',
  DEACTIVATED: 'DEACTIVATED',
  INITIATED: 'INITIATED',
} as const;

export const DIGITAL_BILLING_PRODUCT_TYPE = 'DIGITAL_BILLING';
