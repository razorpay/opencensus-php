import {
  CHART_COLORS_MAPPING,
  CHART_OPTIONS_MAPPING,
  VALUE_OF_REPORTED_ENTITY,
  TOTAL_SALES_VALUE,
  ENTITY_RATIO,
} from './ChartContainer/constants';
import { ChartData, GenerateChartDataType } from './ChartContainer/types';
import { RISK_DECLINED } from './constants';

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

export const generateChartData = ({
  entity,
  metric,
  graphOptions,
  queryData,
}: GenerateChartDataType): ChartData => {
  const Xlabels: number[] = [];
  const totalSales: number[] = [];
  const reportedEntities: number[] = [];
  const entityRatio: number[] = [];

  queryData.forEach((item) => {
    Xlabels.push(Number(item.start_date) * 1000);
    if (entity !== RISK_DECLINED) {
      totalSales.push(parseFloat(item.payment[metric]) / 100);
      reportedEntities.push(parseFloat(item.entity_data[metric]) / 100);
    }

    const ratio =
      parseFloat(
        ((parseFloat(item.entity_data[metric]) / parseFloat(item.payment[metric])) * 100).toFixed(
          3,
        ),
      ) || 0;
    entityRatio.push(ratio);
  });
  const datasets = graphOptions.map((option) => {
    const datasetColor = CHART_COLORS_MAPPING[option];
    const metricOption = CHART_OPTIONS_MAPPING[entity][metric].find(
      ({ value }) => value === option,
    );

    if (option === TOTAL_SALES_VALUE) {
      return {
        label: metricOption?.label ?? 'Transaction Value',
        data: totalSales,
        backgroundColor: datasetColor,
        order: 1,
        yAxisID: 'A',
        hidden: false,
      };
    } else if (option === VALUE_OF_REPORTED_ENTITY) {
      return {
        label: metricOption?.label ?? 'Reported Entity',
        data: reportedEntities,
        backgroundColor: datasetColor,
        order: graphOptions.includes(TOTAL_SALES_VALUE) ? 2 : 1,
        yAxisID: 'A',
        hidden: false,
      };
    } else if (option === ENTITY_RATIO) {
      return {
        label: metricOption?.label ?? 'Entity Ratio',
        data: entityRatio,
        backgroundColor: 'rgba(21, 102, 241, 1)',
        borderColor: datasetColor,
        borderWidth: 1,
        pointRadius: 0,
        fill: false,
        pointHoverRadius: 0,
        pointHoverBorderWidth: 0,
        type: 'line',
        order: 0,
        lineTension: 0.25,
        yAxisID: entity !== RISK_DECLINED ? 'B' : 'A',
        hidden: false,
      };
    } else {
      return null;
    }
  });

  return {
    labels: Xlabels,
    datasets,
  };
};
