import React from 'react';
import { Theme, useTheme } from '@razorpay/blade/components';
import { ChartComponentProps, Line } from 'react-chartjs-2';

import { TOOLTIP_CHART_CONFIG } from 'merchant/widgets/common/utils';

const commonChartOptions = {
  maintainAspectRatio: false,
  responsive: true,
  legend: {
    display: false,
  },
  elements: {
    line: {
      tension: 0.1,
    },
    point: {
      radius: 0, // hide point on chart
      hoverRadius: 4, // make the point bigger when user hovers
    },
  },
  layout: {
    padding: {
      top: 5,
      left: 0,
      right: 10,
      bottom: 0,
    },
  },
};

export const getLineChartOptions = (
  colors: Theme['colors'],
  chartData: ChartComponentProps['data'],
): ChartComponentProps['options'] => {
  return {
    ...commonChartOptions,
    scales: {
      xAxes: [
        {
          gridLines: {
            display: false,
          },
          ticks: {
            autoSkip: true,
            fontSize: 12,
            fontColor: colors.surface.text.gray.subtle,
            display: false,
          },
        },
      ],
      yAxes: [
        {
          ticks: {
            padding: 0,
            fontSize: 12,
            fontColor: colors.surface.text.gray.subtle,
            display: false,
            beginAtZero: true,
            callback: (value) => {
              if (chartData) {
                const dataset = chartData.datasets[0];
                if (dataset) {
                  const schemaY = dataset.schema.y;
                  if (schemaY && schemaY.type === 'amount') {
                    const currencySymbol = dataset.currency_symbol || '₹';
                    return `${currencySymbol}${value}`;
                  }
                }
              }
              return value;
            },
          },
          gridLines: {
            display: false,
          },
        },
      ],
    },
    ...TOOLTIP_CHART_CONFIG,
  };
};

export const EmptyLineChart = () => {
  const { theme } = useTheme();
  const handleChartData = () => ({
    datasets: [
      {
        label: '',
        data: [0, 0],
        borderColor: theme.colors.surface.text.gray.subtle,
        backgroundColor: theme.colors.surface.background.gray.subtle,
      },
    ],
  });

  const emptyChartOptions = {
    ...commonChartOptions,
    scales: {
      yAxes: [
        {
          ticks: {
            display: false,
            suggestedMin: 0,
            suggestedMax: 100,
          },
          gridLines: {
            display: false,
          },
        },
      ],
    },
    tooltips: {
      enabled: false,
    },
  };

  return <Line data={handleChartData} options={emptyChartOptions} />;
};
