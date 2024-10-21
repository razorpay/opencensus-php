import { Tab } from './types';

export const INVOICE_STATUS = {
  INVOICE_MAPPED: 'total_mapped_invoices',
  INVOICE_NOT_MAPPED: 'total_unmapped_invoices',
  INVOICE_AUTO_SYNCED: 'invoice_auto_synced',
};

export const TABS: Tab[] = [
  {
    type: INVOICE_STATUS.INVOICE_MAPPED,
    name: 'Invoices mapped',
    tooltipText:
      'Total number of transactions for which invoices have been successfully fetched and linked to their corresponding payments',
  },
  {
    type: INVOICE_STATUS.INVOICE_NOT_MAPPED,
    name: 'Invoices not mapped',
    tooltipText:
      'Transactions pending invoice linkage. Please add or verify invoice details to complete mapping.',
  },
  {
    type: INVOICE_STATUS.INVOICE_AUTO_SYNCED,
    name: 'Invoices auto-synced',
    tooltipText:
      'Total number of transactions for which invoices have been successfully auto-fetched and linked to their corresponding payments',
  },
];

export const ONBOARDING_PARTNERS = {
  GST_PORTAL: 'gstportal',
  E_INVOICE: 'einvoice',
};

export const ONBOARDING_STATUS = {
  ONBOARDED: 'onboarded',
  EXPIRED: 'expired',
};

export const LEARN_MORE_URL = 'http://razorpay.com/fetch-einvoice-learn-more';
