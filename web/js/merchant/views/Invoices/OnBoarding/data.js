import { getCustomURL } from 'merchant/components/DocsLink';

// Features
export const FEATURES_DATA = [
  {
    icon: require('assets/product_onboarding/gst.svg'),
    title: 'GST compliant',
    desc: `Add GST, discounts and shipping details, all in an invoice and let our invoicing solution do the calculation for you.`,
  },
  {
    icon: require('assets/product_onboarding/partial_payments.svg'),
    title: 'Partial payments',
    desc: 'Enable partial payments for your customers at the time of invoice creation directly from the dashboard.',
  },
  {
    icon: require('assets/product_onboarding/download_option.svg'),
    title: 'Download Option',
    desc: 'Let your customers save and download .pdf version of invoices for future reference.',
  },
];

// Features
export const CURLEC_FEATURES_DATA = [
  {
    icon: require('assets/product_onboarding/gst.svg'),
    title: 'Discounts and Shipping Details',
    desc: `Add discounts and shipping details, all in an invoice and let our invoicing solution work for you.`,
  },
  FEATURES_DATA[1],
  FEATURES_DATA[2],
];

// Features Links
export const FEATURES_LINKS = [
  {
    label: 'Know more',
    url: getCustomURL('https://razorpay.com/docs/invoices/'),
  },
  {
    label: 'View API Docs',
    url: getCustomURL('https://razorpay.com/docs/api/invoices/'),
  },
];
