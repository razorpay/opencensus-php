import { ModeT } from 'common/services/mode';

export interface ListApiParams {
  skip?: number;
  count?: number;
  mode: ModeT;
  merchantId: string;
  merchant_name?: string;
}

export interface ListApiResponse<T> {
  entity: string;
  total_count: number;
  count: number;
  items: T[];
}

export interface ResellersBalance {
  id: string;
  merchant_id: string;
  merchant_name: string;
  logo: string;
  type: string;
  status: string;
  balance: number;
  created_at: number;
  pool_account_id?: string | null;
  account_id?: string | null;
}

export interface BrandBalance {
  id: string;
  account_id: string;
  balance: number;
  created_at: number;
}

export interface TransactionListApiParams {
  skip?: number;
  count?: number;
  from?: number;
  to?: number;
  mode?: ModeT;
  account_id?: string;
  reference_id?: string;
}
