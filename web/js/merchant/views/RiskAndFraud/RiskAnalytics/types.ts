export type AnalyticsEntity = 'fraud' | 'disputes' | 'risk_declined';
export type MetricOptions = 'count' | 'amount';

export type PresetUnit = 'days' | 'week' | 'month' | 'quarter';

export type PresetValue = '7d' | '14d' | '30d' | '60d' | '90d' | '6m' | '1y' | '2y' | 'custom';

export type Duration = 7 | 14 | 30 | 60 | 90 | 6 | 1 | 2;

export type DateRange = {
  startDate: number | null;
  endDate: number | null;
  preset: {
    label: string;
    value: PresetValue;
    duration: Duration;
    unit: PresetUnit;
  };
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

export interface Ratios {
  fraud_to_sales_ratio: number;
  disputes_to_sales_ratio: number;
  risk_declined_to_sales_ratio: number;
  industry_fraud_to_sales_ratio: number;
  industry_disputes_to_sales_ratio: number;
  industry_risk_declined_to_sales_ratio: number;
}

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

export interface ChartQueryDataItem {
  start_date: number;
  payment: { [metric in MetricOptions]: string };
  entity_data: { [metric in MetricOptions]: string };
}
