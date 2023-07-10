import type { ModeT } from 'common/services/mode';

enum AccountStatus {
  active = 'active',
  pending_activation = 'pending_activation',
  inactive = 'inactive',
}

enum WalletPaymentType {
  user = 'user',
  merchant = 'merchant',
}

enum WalletPaymentStatus {
  success = 'success',
  failure = 'failure',
}

export interface Account {
  account_id: string;
  contact: string;
  account_holder_name: string;
  merchant: string;
  program: string;
  balance: number;
  status: AccountStatus;
  created_at: number;
  email?: string;
  full_kyc?: boolean;
  user_id: string;
  type: string;
  partner_customer_id: string;
}

export interface WalletPayment {
  id: string;
  created_at: number;
  amount: number;
  currency: string;
  type: WalletPaymentType;
  description: string;
  status: WalletPaymentStatus;
  failure_reason: string;
  notes: string;
  updated_at: number;
  contact: string;
  email: string;
  account_id: string;
  account_holder_name: string;
  program_name: string;
  merchant_id: string;
}

export interface WalletLoad {
  id: string;
  created_at: number;
  amount: number;
  currency: string;
  user_load: boolean;
  description: string;
  status: WalletPaymentStatus;
  contact: string;
  email: string;
  account_id: string;
  account_holder_name: string;
  program_name: string;
  failure_reason: string;
  notes: string;
}

export interface AccountBalance {
  available_balance: number;
  id: string;
  limits: {
    monthly_load_limit: number;
    monthly_load_limit_used: number;
    monthly_load_limit_balance: number;
    yearly_load_limit: number;
    yearly_load_limit_used: number;
    yearly_load_limit_balance: number;
  };
}

export interface ListApiResponse<T> {
  entity: string;
  count: number;
  has_more: boolean;
  items: T[];
}
export interface ListApiParams {
  skip: number;
  count: number;
  mode: ModeT;
  issuing_account_id?: string;
}

export interface DetailApiParams {
  id: string;
  mode: ModeT;
}

export interface AccountFilterParams {
  user_id?: string;
  id?: string;
  type?: string;
  contact?: string;
  status?: AccountStatus;
}

export type AccountListApiParams = AccountFilterParams & ListApiParams;

export interface Column<T> {
  title: string | JSX.Element;
  value: (item: T) => JSX.Element;
}
export interface Transaction {
  contact: string;
  id: string;
  entity: string;
  account_id: string;
  instrument_id: string;
  reference_id: string;
  source: string;
  type: string;
  amount: number;
  credit: number;
  debit: number;
  created_at: number;
}

export interface TransactionFilterParams {
  id?: string;
  from?: number;
  to?: number;
}

export type TransactionListApiParams = TransactionFilterParams & ListApiParams;

export interface LoadsFilterParams {
  issuing_account_id?: string;
  from: number;
  to: number;
  load_id?: string;
}

export type AccountLoadsListApiParams = LoadsFilterParams & ListApiParams;

export interface PaymentsFilterParams {
  issuing_account_id?: string;
  from: number;
  to: number;
  payment_id?: string;
}

export type AccountPaymentsListApiParams = PaymentsFilterParams & ListApiParams;
