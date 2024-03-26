import React from 'react';
import { ChartComponentProps, Doughnut } from 'react-chartjs-2';
import { ChartDataType } from '../InsightItem/types';

export const getOptions = (): ChartComponentProps['options'] => {
  return {
    responsive: true,
    cutoutPercentage: 80,
    legend: {
      display: false,
      position: 'left',
    },
    borderWidth: 0,
    tooltips: {
      enabled: true,
      position: 'nearest',
    },
    layout: {
      padding: {
        top: 5,
        left: 0,
        right: 0,
        bottom: 5,
      },
    },
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
        backgroundColor: ['#D8E4FD'],
        borderWidth: 0,
      },
    ],
  });

  const options = {
    ...getOptions(),
    tooltips: {
      enabled: false,
      position: 'nearest',
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
          x: '--',
          y: 0,
        },
      ],
    },
  ],
};
