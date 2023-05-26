export interface Transaction {
  id: string;
  account_id: string;
  contact: string;
  credit: number;
  debit: number;
  entity: 'transaction';
  instrument_id: string;
  reference_id: string;
  source: string;
  type: string;
  created_at: number;
  amount: number;
  currency: string;
  status: 'success' | 'failure';
}

export interface FundsSummary {
  id: string;
  available_balance: number;
  limits: Limit[];
}

export interface Limit {
  max_allowed_balance: number;
  monthly_load_limit: number;
  monthly_load_limit_used: number;
  monthly_load_limit_balance: number;
  yearly_load_limit: number;
  yearly_load_limit_used: number;
  yearly_load_limit_balance: number;
}
