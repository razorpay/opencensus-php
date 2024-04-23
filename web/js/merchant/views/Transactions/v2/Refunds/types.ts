import { Currency, Status } from 'merchant/views/Transactions/v2/Payments/types';
import { Collection } from 'merchant/views/Transactions/v2/common/types';

export type Refunds = Collection<Item>;

export interface Item {
  id: string;
  entity: 'refund';
  amount: number;
  currency: Currency;
  payment_id: string;
  batch_id: null | string;
  acquirer_data: AcquirerData;
  created_at: number;
  status: Status;
  source_channel: 'online' | 'in_person' | null;
}

export interface AcquirerData {
  rrn?: string;
  arn?: string;
}
