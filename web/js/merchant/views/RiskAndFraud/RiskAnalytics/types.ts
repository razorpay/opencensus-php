import { ChartData, SelectedGraphOption } from './ChartContainer/types';
import { Stats } from './StatsOverview/types';

export type AnalyticsEntity = 'fraud' | 'disputes' | 'risk_declined';
export type MetricOptions = 'count' | 'amount';

export type PresetUnit = 'days' | 'week' | 'month' | 'quarter';

export type PresetValue = '7d' | '14d' | '30d' | '60d' | '90d' | '6m' | '1y' | '2y' | 'custom';

export type Duration = 7 | 14 | 30 | 60 | 90 | 6 | 1 | 2;

export type DateRangePreset = {
  label: string;
  value: PresetValue;
  duration: Duration;
  unit: PresetUnit;
};

export type DateRange = {
  startDate: number | null; // startDate is a unix timestamp
  endDate: number | null; // endDate is a unix timestamp
  preset: DateRangePreset;
};

export type IntervalLabel =
  | 'Daily'
  | 'Weekly'
  | 'Daily'
  | 'Weekly'
  | 'Weekly'
  | 'Monthly'
  | 'Weekly'
  | 'Monthly'
  | 'Monthly'
  | 'Quarterly';

export type IntervalValue = 'day' | Exclude<PresetUnit, 'days'>;

export type ChartInterval = {
  label: IntervalLabel;
  value: IntervalValue;
  disabled?: boolean;
};

export type Ratios = Partial<{
  fraud_to_sales_ratio: number;
  disputes_to_sales_ratio: number;
  risk_declined_to_sales_ratio: number;
  industry_fraud_to_sales_ratio: number;
  industry_disputes_to_sales_ratio: number;
  industry_risk_declined_to_sales_ratio: number;
}>;

export type GetOnboardingSliderDots = {
  closeOnboarding: () => void;
  riskAndFraudProductOnBoarding: {
    isTour: boolean;
  };
};

export type RiskAndFraudOnboardingProps = {
  active: boolean;
  riskAndFraudProductOnBoarding: GetOnboardingSliderDots['riskAndFraudProductOnBoarding'];
  org: { business_name: string };
  closeOnboarding: () => void;
};

export type GetIsRiskAndFraudEnabled = {
  isEnabled: boolean;
};

export type QuickGuideStepProps = {
  title: string;
  onCloseClick: () => void;
  tiles: Array<{
    title: string;
    content: string;
  }>;
};

export type RiskAndFraudQuickGuideProps = {
  handleProductQuickGuide: (data: RiskAndFraudQuickGuideProps['currentOnboarding']) => void;
  currentOnboarding: {
    feature: string;
    showOnboarding: boolean;
    isQuickGuideOpen: boolean;
    isTour: boolean;
    isEnabled: boolean;
  };
  org: {
    business_name: string;
  };
};

export interface QueryResponseItem {
  start_date: string;
  end_date: string;
  payment: { [metric in MetricOptions]: string };
  entity_data: { [metric in MetricOptions]: string };
}

export type FetchRatiosResponse = Promise<Ratios>;

export type FetchAnalyticsParams = {
  entity: AnalyticsEntity;
  metric: MetricOptions;
  dateRange: DateRange;
  interval: string;
  graphOptions: SelectedGraphOption[];
};

export type FetchAnalyticsResponse = Promise<{
  data: QueryResponseItem[];
  stats: Stats;
  chartData: ChartData;
}>;

export type FormValues = {
  parameters: string | undefined;
  comments: string;
  email: string;
  file: File | null;
};

export type CreateFDTicketParams = {
  parameters: string;
  comments: string;
  email: string;
  file: File;
};

export type ActionContainerProps = {
  heading: string;
  description: string;
  note?: string;
  buttonText: string;
  showDownloadIcon?: boolean;
  onButtonClick: () => void;
};
