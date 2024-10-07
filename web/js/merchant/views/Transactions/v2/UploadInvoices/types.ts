import { Collection } from 'merchant/views/Transactions/v2/common/types';
import { Item as PaymentItem } from 'merchant/views/Transactions/v2/Payments/types';

export type Payments = Collection<Item>;
export interface Item extends PaymentItem {
  b2b_export_invoice: null | string;
  sender_address?: {
    name: null | string;
    country: null | string;
  };
}
