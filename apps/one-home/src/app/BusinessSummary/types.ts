import { DateRangeOption } from './utils';

export interface APIError {
  code: 'CONFIG_ERROR' | 'NETWORK_ERROR' | 'UNKNOWN_ERROR';
  message: string;
}

export interface PaymentDataSummary {
  last_updated: string;
  input_time: 'yesterday' | 'last_7_days' | 'last_30_days';
  current_data?: string;
  previous_data?: string;
  percentage_change?: number;
}

export interface PaymentData {
  data_summary: PaymentDataSummary;
  online_international_amount?: string;
  online_domestic_amount?: string;
  offline_amount?: string;
  payment_enabled: boolean;
  payment_locked: boolean;
  offline_payment_enabled: boolean;
}

// Success Response
export interface PaymentsSuccessResponse {
  id: string;
  type: 'home_business_summary_item';
  alias: 'home_summary_earnings_item';
  data: {
    one_home_data: {
      business_summary: {
        payment: PaymentData;
      };
    };
  };
}

// Error Response
export interface PaymentsErrorResponse {
  id: string;
  type: 'home_business_summary_item';
  alias: 'home_summary_earnings_item';
  error: APIError;
}

export type PaymentsComponent = PaymentsSuccessResponse | PaymentsErrorResponse;

export interface BalanceDataSummary {
  last_updated: string;
  input_time: 'yesterday' | 'last_7_days' | 'last_30_days';
  current_data?: string;
  percentage_change?: number;
}

export interface BalanceData {
  data_summary: BalanceDataSummary;
  locked_current_account: boolean;
  locked_settlement_account: boolean;
  current_account_balance_present?: string;
  settlement_account_balance_present?: string;
  current_account_present: boolean;
  settlement_account_present: boolean;
}

export interface BalanceSuccessResponse {
  id: string;
  type: 'home_business_summary_item';
  alias: 'home_summary_balance_item';
  data: {
    one_home_data: {
      business_summary: {
        balance: BalanceData;
      };
    };
  };
}

// Error Response
export interface BalanceErrorResponse {
  id: string;
  type: 'home_business_summary_item';
  alias: 'home_summary_balance_item';
  error: APIError;
}

// Union Type
export type BalanceComponent = BalanceSuccessResponse | BalanceErrorResponse;

export interface PayoutDataSummary {
  last_updated: string;
  input_time: 'yesterday' | 'last_7_days' | 'last_30_days';
  current_data?: string;
  previous_data?: string;
  percentage_change?: number;
}

export interface Payroll {
  enabled: boolean;
}

export interface PayoutData {
  data_summary: PayoutDataSummary;
  api_and_bulk_payouts_amount?: string;
  vendor_payouts_amount?: string;
  business_banking_enabled: boolean;
  locked: boolean;
  payroll: Payroll;
  vendor_payouts_enabled: boolean;
  api_and_bulk_payouts_enabled: boolean;
}

// Success Response
export interface SpendsSuccessResponse {
  id: string;
  type: 'home_business_summary_item';
  alias: 'home_summary_spends_item';
  data: {
    one_home_data: {
      business_summary: {
        payout: PayoutData;
      };
    };
  };
}

// Spends Error Response
export interface SpendsErrorResponse {
  id: string;
  type: 'home_business_summary_item';
  alias: 'home_summary_spends_item';
  error: APIError;
}

export type SpendsComponent = SpendsSuccessResponse | SpendsErrorResponse;

// Success Response
export interface BusinessSummarySuccessResponse {
  id: string;
  type: 'home_business_summary';
  alias: 'home_business_summary';
  inputs: {
    type: 'select';
    values: ('yesterday' | 'last_7_days' | 'last_30_days')[];
    default_value: 'yesterday' | 'last_7_days' | 'last_30_days';
    name: 'date';
  }[];
  components: (PaymentsComponent | BalanceComponent | SpendsComponent)[];
  data: {
    one_home_data: {
      business_summary: {
        data_summary: {
          last_updated: string;
          input_time: 'yesterday' | 'last_7_days' | 'last_30_days';
        };
      };
    };
  };
}

export interface BusinessSummaryErrorResponse {
  id: string;
  type: 'home_business_summary';
  alias: 'home_business_summary';
  error?: APIError;
  components: [];
}

// Union Type (Use this in API response handling)
export type BusinessSummaryResponse = BusinessSummarySuccessResponse | BusinessSummaryErrorResponse;

export interface BusinessSummaryContentProps {
  isLoading: boolean;
  data: {
    earningsData?: PaymentsComponent;
    balanceData?: BalanceComponent;
    spendsData?: SpendsComponent;
  };
  error: BusinessSummaryErrorResponse | APIError | null;
  dateFilter: DateRangeOption;
}
