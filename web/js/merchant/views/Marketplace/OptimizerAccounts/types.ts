export interface OptimizerAccount {
  id: string;
  account_name: string;
  account_status: string;
  accounts_map: {
    provider_id: string;
    gateway_account_id: string;
    provider_name: string;
  }[];
  created_at: number;
  updated_at: number;
}

export interface CreateOptimizerLinkAccountPayload {
  provider_id: string;
  gateway_account_id: string;
  account_name: string;
  provider_name: string;
}
