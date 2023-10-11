import { Store } from 'common/typings';
import { Currency } from 'merchant/views/Transactions/v2/Payments/types';

export enum PaymentStatus {
  CREATED = 'created',
  REFUNDED = 'refunded',
  FAILED = 'failed',
  CAPTURED = 'captured',
  AUTHORIZED = 'authorized',
  AUTHENTICATED = 'authenticated',
}

export enum RefundStatus {
  CREATED = 'created',
  PROCESSED = 'processed',
  PROCESSING = 'processing',
  FAILED = 'failed',
}

export enum DisputeStatus {
  WON = 'won',
  CLOSED = 'closed',
  LOST = 'lost',
  OPEN = 'open',
  UNDER_REVIEW = 'under_review',
}

// Reference taken from web/js/merchant/views/Transactions/v1/Payments/components/PaymentDisputes.js
interface IDispute {
  id: string;
  amount: number;
  phase: string;
  currency: Currency;
  respond_by: number;
  status: DisputeStatus;
}

type Card = null | {
  id: string;
  entity: string;
  name: string;
  last4: string;
  network: string;
  type: string;
  issuer: string;
  international: false;
  emi: boolean;
  sub_type: string;
  token_iin: null;
};

interface PaymentTransaction {
  id: string;
  entity: string;
  entity_id: string;
  type: string;
  debit: number;
  credit: number;
  amount: number;
  currency: string;
  fee: number;
  tax: number;
  on_hold: boolean;
  settled: boolean;
  created_at: number;
  settled_at: number;
  settlement_id: string;
  posted_at: null;
  credit_type: string;
  settlement: {
    id: string;
    entity: string;
    amount: number;
    status: string;
    fees: number;
    tax: number;
    utr: string;
    created_at: number;
  };
}

type PaymentMethod =
  | 'card'
  | 'netbanking'
  | 'upi'
  | 'wallet'
  | 'cardless_emi'
  | 'cod'
  | 'app'
  | 'bank_transfer'
  | 'emi';
export interface IPaymentDetails {
  id: string;
  amount: number;
  currency: Currency;
  base_amount: number;
  status:
    | PaymentStatus.CREATED
    | PaymentStatus.AUTHENTICATED
    | PaymentStatus.AUTHORIZED
    | PaymentStatus.CAPTURED
    | PaymentStatus.REFUNDED
    | PaymentStatus.FAILED;
  order_id: string;
  international: boolean;
  method: PaymentMethod;
  amount_refunded: number;
  amount_transferred: number;
  refund_status: null | 'partial' | 'full';
  captured: boolean;
  description: null;
  invoice_id: string;
  card_id: string;
  card: Card | null;
  bank: null | string;
  wallet: null | string;
  vpa: null | string;
  email?: string;
  contact?: string;
  notes: {
    name?: string;
    email?: string;
    phone?: string;
  };
  fee: number;
  tax: number;
  error_code: null;
  error_description: null | string;
  error_source: null | string;
  error_step: null | string;
  error_reason: null | string;
  acquirer_data: {
    auth_code?: string;
    arn?: string;
    rrn?: string;
  };
  emi_plan: null;
  disputes: {
    entity: string;
    count: 0;
    items: Array<IDispute>;
  };
  created_at: number;
  fee_bearer: 'platform' | 'customer';
  transaction: PaymentTransaction;
  instant_refund_support: boolean;
  gateway_refund_support: boolean;
  direct_settlement_refund: boolean;
  authentication: {
    version: string;
    authentication_channel: string;
  };
  optimizer_provider: string;
}

export interface IPaymentIdRefundDetail {
  acquirer_data: {
    arn: string;
  };
  amount: number;
  batch_id: null;
  created_at: number;
  currency: Currency;
  entity: string;
  id: string;
  notes: {
    comment: string;
  };
  payment_id: string;
  receipt: null;
  speed: string;
  speed_processed: string;
  speed_requested: string;
  status: RefundStatus;
  processed_at: number;
}
export type IPaymentIdRefundDetails = Array<IPaymentIdRefundDetail>;

interface BankTransferReceivers {
  id: string;
  entity: string;
  ifsc: string;
  bank_name: string;
  name: string;
  notes?: any[];
  account_number: string;
}

export interface IBankTransfer {
  id: string;
  entity: string;
  payment_id: string;
  mode: string;
  bank_reference: string;
  amount: number;
  payer_bank_account: {
    id: string;
    entity: string;
    ifsc: string;
    bank_name: string;
    name: string;
    notes: any[];
    account_number: string;
  };
  virtual_account_id: string;
  virtual_account: {
    id: string;
    name: string;
    entity: string;
    status: string;
    description: null | string;
    amount_expected: null | number;
    notes: any[];
    amount_paid: number;
    customer_id: null | string;
    receivers: BankTransferReceivers[];
    close_by: null | number;
    closed_at: number;
    created_at: number;
  };
}

export interface ICurrentBalance {
  id: string;
  merchant_id: string;
  type: string;
  currency: string;
  name: null | string;
  balance: number;
  credits: number;
  fee_credits: number;
  refund_credits: number;
  account_number: null | string;
  account_type: null | string;
  channel: null | string;
  updated_at: number;
  locked_balance: number;
  last_fetched_at: number;
}

export interface ApplicationDetails {
  name: string;
  id: string;
}

export interface ITransfer {
  id: string;
}

export interface ITransfers {
  loading: boolean;
  items: ITransfer[];
}

export interface IPaymentTransfers {
  paymentDetails: IPaymentDetails;
  fetchTransfers: (arg: { fetchTransfers: () => void }) => void;
  transfers: ITransfers;
}

export interface ITransferList {
  transfers: ITransfers;
}

export interface IPaymentTransferNew {
  user: Store['session']['user'];
  fetchTransfers: (arg: { fetchTransfers: () => void }) => void;
  id: string;
}
