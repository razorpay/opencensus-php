import { IconComponent, ButtonProps } from '@razorpay/blade/components';

export type useInsightsProp = {
  key: string;
  aliasKey: string;
  input_data: string;
};

export interface DataSummary {
  last_updated?: string;
  input_time?: string;
  current_data?: string | number;
  previous_data?: string;
  percentage_change?: number;
  [key: string]: any; // Allows any other properties with a string key
}

export interface RayInsight {
  data_summary?: DataSummary;
  current_top_payment_method?: string;
  previous_top_payment_method?: string;
  payment_failure_error_code?: string;
  payment_failure_error_description?: string;
  [key: string]: any; // Allows any other properties with a string key
}

export interface Payout {
  data_summary?: DataSummary;
  business_banking_enabled?: boolean;
  locked?: boolean;
  ray_insight?: RayInsight;
}

export interface Payment {
  data_summary?: DataSummary;
  payment_enabled?: boolean;
  payment_locked?: boolean;
  ray_insight?: RayInsight;
  online_international_amount?: string;
  online_domestic_amount?: string;
  offline_payment_enabled?: boolean;
}

export interface SuccessRate {
  data_summary?: DataSummary;
  ray_insight?: RayInsight;
}

export interface Refund {
  data_summary?: DataSummary;
  ray_insight?: RayInsight;
}

export interface Seasonal {
  data_summary?: DataSummary;
}

export interface InsightSummary {
  data_summary?: DataSummary;
  payment_enabled?: boolean;
  payment_locked?: boolean;
}

export interface OneHomeData {
  other_insight?: {
    payout?: Payout;
    insight_summary?: InsightSummary;
  };
  insight?: {
    payment?: Payment;
    success_rate?: SuccessRate;
    refund?: Refund;
    seasonal?: Seasonal;
    insight_summary?: InsightSummary;
  };
}

export interface Error {
  code?: string;
  message?: string;
}

export interface ActionParams {
  path?: string;
  url?: string;
}

interface Properties {
  variant: ButtonProps['variant'];
}

export interface Action {
  title: string;
  action: string;
  type: string;
  icon: string;
  icon_position: string;
  action_params: ActionParams;
  properties: Properties;
}

export interface Component {
  id?: string;
  type?: string;
  title?: string;
  actions?: Action[];
  inputs?: any[];
  components?: Component[];
  data?: {
    one_home_data?: OneHomeData;
  };
  alias?: string;
  analytics?: any;
  styles?: any;
  description?: string;
  error?: Error;
}

export interface SummaryData {
  one_home_data?: OneHomeData;
}

export interface InsightResponse {
  id?: string;
  type?: string;
  title?: string;
  actions?: any[];
  inputs?: any[];
  components?: Component[];
  alias?: string;
  analytics?: any;
  styles?: any;
  error?: Error;
  data?: SummaryData;
}

export interface PaymentInsightsWithErrorBoundaryProp {
  insightsData: InsightResponse | undefined;
  selectedFilter: string;
  isError: boolean;
  isLoading: boolean;
  isMobile: boolean;
  paymentLocked?: boolean;
  error?: Error | unknown;
}

export interface OtherInsightsWithErrorBoundaryProp extends PaymentInsightsWithErrorBoundaryProp {}

export interface Analytics {
  enabled?: boolean;
  [key: string]: unknown;
}

export type InsightCardProp = {
  cardType: InsightCardType | OtherInsightCardType;
  componentData: Component | undefined;
  isLoading: boolean;
  isMobile: boolean;
  source: string;
  analytics?: Analytics | null;
  paymentLocked?: boolean;
};

export type InsightCardWithErrorBoundaryProp = {
  cardType: InsightCardType | OtherInsightCardType;
  componentData: Component | undefined;
  isMobile: boolean;
  source: string;
  analytics?: Analytics | null;
  paymentLocked?: boolean;
};

export type InsightCardType = 'payment' | 'success_rate' | 'refund';
export type OtherInsightCardType = 'payout';
export type NonInsightCardType = 'payroll' | 'earnings' | 'customers' | 'payout';

export type InsightCardStaticData = {
  title: string;
  tooltipContent: string;
  defaultIcon: IconComponent;
  redirectionUrl: string;
  lockCardDescription?: string;
  emptyCardDescription?: string;
};

export type NonInsightCardStaticData = {
  title: string;
  description: string;
  path: string;
};

export interface LockedCardProp extends InsightCardHeaderProp {}
export interface EmptyCardProp extends InsightCardHeaderProp {}

export type InsightCardHeaderProp = {
  insightsStaticData: InsightCardStaticData;
  isMobile: boolean;
  componentData: Component;
  analytics?: Analytics | null;
  showViewDetails?: boolean;
};

export type InsightCardContentProp = {
  cardType: InsightCardType | OtherInsightCardType;
  isMobile: boolean;
  dataSummary: DataSummary;
};

export type InsightCardRayProp = {
  cardType: InsightCardType | OtherInsightCardType;
  insightsStaticData: InsightCardStaticData;
  rayInsightsData: RayInsight;
  inputTime?: string;
};

export type InsightCardButtonProp = {
  insightsStaticData: InsightCardStaticData;
  analytics?: Analytics | null;
};

interface SeasonalBaseProp {
  isMobile: boolean;
  componentData: Component | undefined;
}
export interface SeasonalCardProp extends SeasonalBaseProp {
  isLoading: boolean;
}

export interface SeasonalCardBodyProp extends SeasonalBaseProp {}

export interface NonInsightCardProp {
  cardType: NonInsightCardType;
  isLoading: boolean;
  isMobile: boolean;
  componentData?: Component;
  analytics?: Analytics | null;
}

export interface NonInsightCardBodyProp {
  isMobile: boolean;
  componentData?: Component;
  analytics?: Analytics | null;
  cardType: NonInsightCardType;
}

export interface NonInsightContentProp {
  isMobile: boolean;
  componentData: Component;
  analytics?: Analytics | null;
}

export interface NonInsightCardImageProp {
  cardType: NonInsightCardType;
}

export type DateRangeOption = 'yesterday' | 'last_7_days' | 'last_30_days';
