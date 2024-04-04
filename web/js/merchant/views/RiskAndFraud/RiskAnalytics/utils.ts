import {
  CHART_COLORS_MAPPING,
  VALUE_OF_REPORTED_ENTITY,
  TOTAL_SALES_VALUE,
  ENTITY_RATIO,
} from './ChartContainer/constants';
import { ChartData, GenerateChartDataType } from './ChartContainer/types';
import { Stats } from './StatsOverview/types';
import { METRIC_VALUE, METRIC_COUNT, RISK_DECLINED } from './constants';

import type {
  PresetValue,
  ChartInterval,
  QueryResponseItem,
} from 'merchant/views/RiskAndFraud/RiskAnalytics/types';

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
    case '12m':
    case '24m':
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
      totalSales.push(
        metric === METRIC_VALUE
          ? parseFloat(item.payment[METRIC_VALUE]) / 100
          : parseFloat(item.payment[METRIC_COUNT]),
      );
      reportedEntities.push(
        metric === METRIC_VALUE
          ? parseFloat(item.entity_data[METRIC_VALUE]) / 100
          : parseFloat(item.entity_data[METRIC_COUNT]),
      );
    }

    const ratio =
      parseFloat(
        ((parseFloat(item.entity_data[metric]) / parseFloat(item.payment[metric])) * 100).toFixed(
          2,
        ),
      ) || 0;
    entityRatio.push(ratio);
  });

  const datasets = graphOptions.map((option) => {
    const label = `${entity}:${metric}:${option}`;
    const datasetColor = CHART_COLORS_MAPPING[option];

    if (option === TOTAL_SALES_VALUE) {
      return {
        label,
        data: totalSales,
        backgroundColor: datasetColor,
        order: 1,
        yAxisID: 'A',
        hidden: false,
      };
    } else if (option === VALUE_OF_REPORTED_ENTITY) {
      return {
        label,
        data: reportedEntities,
        backgroundColor: datasetColor,
        order: graphOptions.includes(TOTAL_SALES_VALUE) ? 2 : 1,
        yAxisID: 'A',
        hidden: false,
      };
    } else if (option === ENTITY_RATIO) {
      return {
        label,
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

export const formatAmount = (
  amount = 0,
): {
  value: string;
  decimal: string;
} => {
  // Format integer part with commas
  const formattedInteger = amount.toLocaleString('en-IN', { maximumFractionDigits: 0 });

  // Extract decimal part
  const decimalPart = amount % 1 !== 0 ? amount.toFixed(2).split('.')[1] : '00';

  return {
    value: formattedInteger,
    decimal: decimalPart,
  };
};

export function calculateStats(data: QueryResponseItem[], metric = METRIC_VALUE): Stats {
  // Initialize variables
  let totalPaymentAmount = 0;
  let entityPaymentAmount = 0;
  let totalEntityCount = 0;
  let totalPaymentCount = 0;

  // Iterate over the API response data
  data.forEach((item) => {
    totalPaymentAmount += parseFloat(item?.payment?.amount) || 0; // total sales value
    entityPaymentAmount += parseFloat(item?.entity_data?.amount) || 0; // value reported
    totalEntityCount += parseInt(item?.entity_data?.count, 10) || 0; // number of reported entity
    totalPaymentCount += parseInt(item?.payment?.count, 10) || 0; // total number of transactions
  });

  // Calculate ratios
  const entityPaymentRatio =
    metric === METRIC_VALUE
      ? ((entityPaymentAmount / totalPaymentAmount) * 100).toFixed(2)
      : ((totalEntityCount / totalPaymentCount) * 100).toFixed(2);

  /**
   * Format the result
   * Convert payment and entity amounts from paise to rupees (divide by 100)
   */
  const result = {
    total_payment_amount: formatAmount(totalPaymentAmount / 100),
    entity_payment_amount: formatAmount(entityPaymentAmount / 100),
    total_entity_count: totalEntityCount,
    total_payment_count: totalPaymentCount,
    entity_payment_ratio: entityPaymentRatio,
  };

  return result;
}
