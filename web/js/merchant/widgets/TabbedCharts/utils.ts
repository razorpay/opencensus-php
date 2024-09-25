import { Theme } from '@razorpay/blade/components';
import { ChartComponentProps } from 'react-chartjs-2';

import { TOOLTIP_CHART_CONFIG } from '../common/utils';

export const getLineChartOptions = (
  colors: Theme['colors'],
  chartData: ChartComponentProps['data'],
): unknown => {
  const suggestedMax = getSuggestedMax(chartData);
  return {
    responsive: true,
    legend: {
      display: true,
      position: 'bottom',
      align: 'start',
      labels: {
        fontColor: colors.surface.text.gray.subtle,
        boxWidth: 12,
      },
    },
    elements: {
      point: {
        radius: 0, // hide point on chart
        hoverRadius: 4, // make the point bigger when user hovers
      },
    },
    // Browser Rendering issue - https://github.com/chartjs/Chart.js/issues/11310
    // animation: {
    //   easing: 'linear',
    // },
    scales: {
      xAxes: [
        {
          offset: true,
          gridLines: {
            display: false,
            drawOnChartArea: false,
            drawTicks: true,
          },
          ticks: {
            autoSkip: true,
            fontSize: 12,
            fontColor: colors.surface.text.gray.subtle,
          },
        },
      ],
      yAxes: [
        {
          ticks: {
            padding: 0,
            fontSize: 12,
            maxTicksLimit: 5,
            fontColor: colors.surface.text.gray.subtle,
            suggestedMax,
            // add currency symbol to y-axis labels based on chartData schema type
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
            color: colors.surface.background.gray.subtle,
            zeroLineColor: colors.surface.background.gray.subtle,
            display: true,
            drawTicks: false,
            drawBorder: false,
          },
        },
      ],
    },
    layout: {
      padding: {
        top: 0,
        left: 0,
        right: 0,
        bottom: 0,
      },
    },
    ...TOOLTIP_CHART_CONFIG,
  };
};

function getSuggestedMax(chartData) {
  const buffer = 15; // change percentage buffer to be used for max value
  if (chartData) {
    let max = 0;

    chartData.datasets.forEach((dataset) => {
      const maxDataset = Math.max(...dataset.data);
      if (maxDataset > max) {
        max = maxDataset;
      }
    });
    // 20% buffer - Chartjs automatically rounds off the max value to a nice number
    const newMax = max * (1 + buffer / 100);
    return newMax || 1000;
  }
  return undefined;
}
