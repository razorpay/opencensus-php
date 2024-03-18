import moment from 'moment';

import { METRIC_COUNT, RISK_DECLINED } from 'merchant/views/RiskAndFraud/RiskAnalytics/constants';

import { CHART_LABELS_MAPPING } from './constants';
import {
  ChartConfigOptions,
  ChartDataset,
  ChartLabelsMapping,
  CalculateStepSizeResult,
  GetChartAreaConfigParams,
  TooltipsConfig,
  YAxisPosition,
} from './types';

const chartFontColor = '#858C9A';
const chartGridColor = '#F1F3F6';
const desiredTicksLimit = 6;

export function calculateStepSize(
  dataset: ChartDataset | null,
): CalculateStepSizeResult | Record<string, unknown> {
  if (!(dataset && dataset?.data?.length > 0)) return {};

  const { data } = dataset;
  const maxDataPoints = Math.max(...data);
  const interval = maxDataPoints / (desiredTicksLimit - 1);

  const yAxesTicks = Array.from({ length: desiredTicksLimit }, (_, index) =>
    Math.round(index * interval),
  );

  return { min: 0, max: maxDataPoints, stepSize: interval, labels: yAxesTicks };
}

export const getChartAreaConfig = ({
  entity,
  metric,
  selectedInterval,
  chartData,
}: GetChartAreaConfigParams): ChartConfigOptions => {
  const now = moment();
  const { x, y1, y2 } = (CHART_LABELS_MAPPING as ChartLabelsMapping)[entity][metric];

  const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    layout: {
      padding: { top: 0, left: 0, right: 0, bottom: 0 },
    },
    scales: {
      xAxes: [
        {
          id: x,
          fontFamily: 'Lato',
          categoryPercentage: 0.6,
          barPercentage: 1.0,
          border: { width: 1 },
          gridLines: { display: false },
          ticks: {
            maxRotation: 0,
            fontColor: chartFontColor,
            callback: (value: number) => {
              let format = 'MMM D';
              const currValue = moment(value);
              if (selectedInterval === 'monthly') {
                format = 'MMM';
              }
              if (!currValue.isSame(now, 'year')) {
                format += ' YYYY';
              }
              return currValue.format(format);
            },
          },
        },
      ],
      yAxes:
        entity !== RISK_DECLINED
          ? [
              {
                id: 'A',
                position: 'left' as YAxisPosition,
                gridLines: { color: chartGridColor },
                ticks: {
                  fontFamily: 'Lato',
                  beginAtZero: true,
                  maxTicksLimit: desiredTicksLimit,
                  ...calculateStepSize(chartData?.datasets?.[0]),
                  callback: (value: number) => {
                    if (metric === METRIC_COUNT) return value;
                    // Round to 1 decimal place for millions
                    if (value >= 1000000) return `${(value / 1000000).toFixed(1)}M`;
                    // Round to 0 decimal places for thousands
                    if (value >= 1000) return `${(value / 1000).toFixed(0)}K`;
                    return value;
                  },
                },
                scaleLabel: {
                  display: true,
                  labelString: y1,
                },
              },
              {
                id: 'B',
                position: 'right' as YAxisPosition,
                gridLines: { display: false },
                ticks: {
                  fontFamily: 'Lato',
                  beginAtZero: true,
                  min: 0,
                  stepSize: 0.25,
                  maxTicksLimit: desiredTicksLimit,
                  callback: (value: number) => {
                    if (value === 0) return '';
                    return `${value}%`;
                  },
                },
                scaleLabel: {
                  display: true,
                  labelString: y2,
                },
              },
            ]
          : [
              {
                id: 'A',
                position: 'left' as YAxisPosition,
                gridLines: { color: chartGridColor },
                ticks: {
                  fontFamily: 'Lato',
                  beginAtZero: true,
                  min: 0,
                  stepSize: 0.25,
                  maxTicksLimit: desiredTicksLimit,
                  callback: (value: number) => {
                    if (value === 0) return '';
                    return `${value}%`;
                  },
                },
                scaleLabel: {
                  display: true,
                  labelString: y1,
                },
              },
            ],
    },
    tooltips: {
      mode: 'point',
      position: 'nearest',
      intersect: true,
      bodySpacing: 4,
      borderWidth: 1,
      backgroundColor: '#FFFFFF',
      borderColor: '#E0E8F4',
      titleFontFamily: 'Lato',
      titleFontColor: '#262D3A',
      titleSpacing: 5,
      titleMarginBottom: 12,
      bodyFontFamily: 'Lato',
      bodyFontColor: '#262D3A',
      xPadding: 12,
      yPadding: 12,
      caretPadding: 5,
      cornerRadius: 4,
      callbacks: {
        title: ([tooltipItem]) => {
          return moment(tooltipItem.xLabel).format('DD MMM YYYY');
        },
      },
    } as TooltipsConfig,
  };

  return chartOptions;
};
