import { RESELLERS_STATUS } from 'merchant/views/GCMS/shared/constants';

export type Reseller = {
  id: string;
  merchant_id: string;
  merchant_name: string;
  merchant_detail_id: string;
  logo: string;
  type: string;
  status: string;
  order_count: 0;
  aggregate_order_value: number;
  eligible_programs: number;
  created_at: number;
};

export interface ListApiResponse<T> {
  entity: string;
  count: number;
  has_more: boolean;
  items: T[];
}

export type ResellerBalance = {
  id: string;
  merchant_id: string;
  merchant_name: string;
  logo: string;
  type: string;
  status: [keyof typeof RESELLERS_STATUS];
  pool_account_id: string;
  account_id: string;
  balance: number;
  created_at: number;
};
