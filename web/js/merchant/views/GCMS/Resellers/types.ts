import { RESELLERS_STATUS } from 'merchant/views/GCMS/shared/constants';

export type Reseller = {
  id: string;
  merchant_id: string;
  merchant_name: string;
  merchant_detail_id: string;
  logo: string;
  type: string;
  status: string;
  order_count: number;
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
  virtual_account: VirtualAccount;
};

type VirtualAccount = {
  id: string;
  entity: string;
  name: string;
  bank_account_number: string;
  ifsc: string;
  bank_name: string;
  status: string;
};

export type ResellerDetails = {
  id: string;
  name: string;
  type: string;
  status: string; //TODO: confirm
  roles: string[];
  logo: string;
  tier_level: string;
  industry: string;
  region: string;
  billing_detail: {
    billing_label: string;
    business_name: string;
    gst_number: string;
    company_pan: string;
    authorized_signatory_pan: string;
    cin: string;
  };
  created_at: number;
  pool_account_id: string;
};
