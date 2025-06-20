import { DASHBOARD_MODE } from '@libs/shared-types';
import { ModeT } from 'common/services/mode';

export interface ListApiParams {
  skip?: number;
  count?: number;
  mode: ModeT;
  accountId?: string;
  merchantId?: string;
  resellerId?: string;
  resellerDetailId?: string;
  orderId?: string;
}

export interface LinkResellerToProgramParams {
  merchantId: string;
  resellerIds: string[];
  programId: string;
  defaultDiscount?: number;
  status?: string;
  mode?: string;
}

export interface AddProgramDetailsParams {
  merchantId: string;
  formData: object;
  mode: DASHBOARD_MODE;
  urlUpdate?: boolean;
  programId?: string;
}

export interface ListApiResponse<T> {
  entity: string;
  count: number;
  has_more: boolean;
  total_count: number;
  items: T[];
  order_items: T[];
  data: T[];
}

export type MerchantResellerRole = 'reseller' | 'merchant';

export type MerchantResellerBillingDetail = {
  billing_label: string;
  business_name: string;
  gst_number: string;
  company_pan: string;
  authorized_signatory_pan: string;
  cin: string;
};
export type MerchantReseller = {
  primary_contact: any;
  id: string;
  name: string;
  type: string;
  status: string;
  roles: MerchantResellerRole[];
  logo: string;
  tier_level: string;
  industry: string;
  region: string;
  billing_detail?: MerchantResellerBillingDetail;
  created_at: string;
  pool_account_id?: string;
};

export type ProgramResellers = {
  merchant_id: string;
  name: string;
  reseller_id: string;
  program_id: string;
  default_discount: number;
  status: string;
};

export type PageLayoutProps = {
  title: string;
  subtitle: string;
  leading?: React.ReactNode;
  children: React.ReactNode;
};
