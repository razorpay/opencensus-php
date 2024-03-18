import type { AnalyticsEntity, Ratios } from 'merchant/views/RiskAndFraud/RiskAnalytics/types';

export type OverviewTab = {
  [key: string]: {
    title: string;
    valueKey: string;
    comparisionKey: string;
    popoverContent: string;
  };
};

export type LabelComparisonResult = {
  iconColor: 'feedback.icon.negative.lowContrast' | 'surface.text.subdued.lowContrast';
  textColor: 'feedback.text.negative.lowContrast' | 'surface.text.subdued.lowContrast';
  label: string;
};

export interface OverviewCardProps {
  entity: AnalyticsEntity;
  selectedTab: string;
  ratios: Ratios | undefined;
  handleTabChange: (event: React.MouseEvent<HTMLButtonElement>) => void;
}

export interface RatioDuration {
  duration: number;
  unit: 'days' | 'weeks' | 'months';
}

export interface RatioPayload {
  startDate: number;
  endDate: number;
}
