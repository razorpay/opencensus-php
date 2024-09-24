import { humanize } from '@dashboard/shared-utils/rzp-utils';
import { Item } from 'apps/self-serve/src/App/Transactions/v2/Payments/types';

export const getPaymentMethod = ({ method, card, wallet }: Item): string => {
  switch (method) {
    case 'card':
      return `${card?.type ? humanize(card.type) : ''} Card`;
    case 'wallet':
      return `${humanize(wallet)} Wallet`;
    case 'cod':
      return 'Cash on Delivery';
    case 'bank_transfer':
      return 'Bank transfer';
    case 'upi':
      return 'UPI';
    case 'emi':
      return 'EMI';
    case 'offline':
      return 'Offline';
    case 'emandate':
      return 'e-Mandate';
    case 'netbanking':
      return 'Netbanking';
    case 'intl_bank_transfer':
      return 'International bank transfer';
    case 'paylater':
      return 'Pay Later';
    case 'app':
      return 'CRED pay';
    case 'aeps':
      return 'AePS';
    case 'fpx':
      return 'FPX';
    case 'transfer':
      return 'Route transfer';
    case 'cardless_emi':
      return 'Cardless EMI';
    case 'nach':
      return 'eNach';
    case 'paynow':
      return 'Paynow';
    case 'unselected':
    default:
      return '--';
  }
};

export const getSourceChannelType = (source_channel: string | null) => {
  if (source_channel === 'online') return 'Online';
  else if (source_channel === 'in_person') return 'In Person';
  else return null;
};
