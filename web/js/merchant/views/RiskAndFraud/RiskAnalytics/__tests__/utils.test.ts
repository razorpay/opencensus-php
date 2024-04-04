import { PresetValue } from 'merchant/views/RiskAndFraud/RiskAnalytics/types';
import { getChartInterval } from 'merchant/views/RiskAndFraud/RiskAnalytics/utils';

describe('getChartInterval', () => {
  it('should return daily and disabled weekly for 7d and 14d', () => {
    const intervals = getChartInterval('7d');
    expect(intervals).toEqual([
      { label: 'Daily', value: 'day' },
      { label: 'Weekly', value: 'week', disabled: true },
    ]);
    expect(getChartInterval('14d')).toEqual(intervals);
  });

  it('should return daily and weekly for 30d', () => {
    expect(getChartInterval('30d')).toEqual([
      { label: 'Daily', value: 'day' },
      { label: 'Weekly', value: 'week' },
    ]);
  });

  it('should return weekly and monthly for 60d and 90d', () => {
    const intervals = getChartInterval('60d');
    expect(intervals).toEqual([
      { label: 'Weekly', value: 'week' },
      { label: 'Monthly', value: 'month' },
    ]);
    expect(getChartInterval('90d')).toEqual(intervals);
  });

  it('should return disabled weekly and monthly for 6m', () => {
    expect(getChartInterval('6m')).toEqual([
      { label: 'Weekly', value: 'week', disabled: true },
      { label: 'Monthly', value: 'month' },
    ]);
  });

  it('should return monthly and quarterly for 1y and 2y', () => {
    const intervals = getChartInterval('12m');
    expect(intervals).toEqual([
      { label: 'Monthly', value: 'month' },
      { label: 'Quarterly', value: 'quarter' },
    ]);
    expect(getChartInterval('24m')).toEqual(intervals);
  });

  it('should return daily and weekly for default case', () => {
    expect(getChartInterval('invalid' as PresetValue)).toEqual([
      { label: 'Daily', value: 'day' },
      { label: 'Weekly', value: 'week', disabled: true },
    ]);
  });
});
