import { CurrencyCodeType } from '@razorpay/i18nify-js/currency';

import { Collection } from 'merchant/views/Transactions/v2/common/types';

export type Payments = Collection<Item>;

export interface Item {
  id: string;
  entity: 'payment';
  amount: number;
  currency: CurrencyCodeType;
  base_amount: number;
  status: Status;
  order_id: null | string;
  invoice_id: null | string;
  international: boolean;
  method: PaymentMethod;
  amount_refunded: number;
  amount_transferred: number;
  refund_status: null | string;
  captured: boolean;
  description: null | string;
  card_id: null | string;
  card: null | Card;
  bank: null | string;
  wallet: null | string;
  vpa: null | string;
  email: null | string;
  contact: null | string;
  notes: any[];
  fee: null | string;
  tax: null | string;
  error_code: null | string;
  error_description: null | string;
  error_source: null | string;
  error_step: null | string;
  error_reason: null | string;
  acquirer_data: AcquirerData;
  created_at: number;
  source_channel: null | 'online' | 'in_person';
}

export interface AcquirerData {
  auth_code: null | string;
  rrn?: string;
  arn?: string;
}

export interface Card {
  id: string;
  entity: 'card';
  name: string;
  last4: string;
  network: Network;
  type: CardType;
  issuer: Issuer;
  international: boolean;
  emi: boolean;
  sub_type: null | string;
  token_iin: null | string;
}

export type PaymentMethod =
  | 'cod'
  | 'bank_transfer'
  | 'upi'
  | 'emi'
  | 'unselected'
  | 'offline'
  | 'emandate'
  | 'netbanking'
  | 'intl_bank_transfer'
  | 'paylater'
  | 'app'
  | 'aeps'
  | 'fpx'
  | 'transfer'
  | 'cardless_emi'
  | 'nach'
  | 'card'
  | 'wallet';

export type Issuer = 'HDFC' | 'UTIB';

export type Network = 'Visa' | 'MasterCard';

export type CardType = 'debit' | 'credit';

export type Currency = 'INR' | 'MYR';

export type Status =
  | 'failed'
  | 'created'
  | 'captured'
  | 'authorized'
  | 'authenticated'
  | 'refunded'
  | 'pending';

export interface PaymentsTimeline {
  created_at: number | null;
  authorized_at: number | null;
  captured_at: number | null;
}
