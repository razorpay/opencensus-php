import type { PresetValue, ChartInterval } from 'merchant/views/RiskAndFraud/RiskAnalytics/types';

export const getChartInterval = (presetValue: PresetValue): ChartInterval[] => {
  switch (presetValue) {
    case '30d':
      return [
        { label: 'Daily', value: 'day' },
        { label: 'Weekly', value: 'week' },
      ];
    case '60d':
    case '90d':
      return [
        { label: 'Weekly', value: 'week' },
        { label: 'Monthly', value: 'month' },
      ];
    case '6m':
      return [
        { label: 'Weekly', value: 'week', disabled: true },
        { label: 'Monthly', value: 'month' },
      ];
    case '1y':
    case '2y':
      return [
        { label: 'Monthly', value: 'month' },
        { label: 'Quarterly', value: 'quarter' },
      ];
    case '7d':
    case '14d':
    default:
      return [
        { label: 'Daily', value: 'day' },
        { label: 'Weekly', value: 'week', disabled: true },
      ];
  }
};
