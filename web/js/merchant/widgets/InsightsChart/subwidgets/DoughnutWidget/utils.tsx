import React from 'react';
import { ChartComponentProps, Doughnut } from 'react-chartjs-2';

import { ChartDataType } from 'merchant/widgets/InsightsChart/subwidgets/InsightItem/types';
import { TOOLTIP_CHART_CONFIG } from 'merchant/widgets/common/utils';

export const getOptions = (): ChartComponentProps['options'] => {
  return {
    responsive: true,
    cutoutPercentage: 80,
    legend: {
      display: false,
      position: 'left',
    },
    borderWidth: 0,
    layout: {
      padding: {
        top: 10,
        left: 10,
        right: 10,
        bottom: 10,
      },
    },
    ...TOOLTIP_CHART_CONFIG,
  };
};

export const EmptyDoughnutChart = () => {
  const handleChartData = () => ({
    labels: [''],
    datasets: [
      {
        label: '',
        data: [1],
        fill: false,
        backgroundColor: ['#cbd5e2'],
        borderWidth: 0,
      },
    ],
  });

  const options = {
    ...getOptions(),
    tooltips: {
      enabled: true,
      position: 'nearest',
      callbacks: {
        label: () => {
          return '';
        },
        title: () => {
          return 'No data';
        },
      },
    },
  };

  return <Doughnut data={handleChartData} options={options} />;
};

export const emptyChartTableData: ChartDataType = {
  type: 'doughnut',
  labels: [''],
  schema: {
    x: {
      type: 'string',
      unit: '',
    },
    y: {
      type: 'number',
      unit: '',
    },
  },
  data: [
    {
      label: '',
      points: [
        {
          x: 'Credit Cards',
          y: '0',
        },
        {
          x: 'UPI',
          y: '0',
        },
        {
          x: 'Netbanking',
          y: '0',
        },
        {
          x: 'Others',
          y: '0',
        },
      ],
    },
  ],
};
