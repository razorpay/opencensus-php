import React from 'react';
import { COLORS } from 'merchant/containers/Home/RTUX/colors';
import { ChartComponentProps, Line } from 'react-chartjs-2';

export const getTabbedChartOptions = (): ChartComponentProps['options'] => {
  return {
    maintainAspectRatio: false,
    responsive: true,
    legend: {
      display: false,
    },
    elements: {
      line: {
        tension: 0.1,
      },
    },
    scales: {
      xAxes: [
        {
          gridLines: {
            display: false,
          },
          ticks: {
            autoSkip: true,
            fontSize: 12,
            fontColor: COLORS.lightBlue,
            display: false,
          },
        },
      ],
      yAxes: [
        {
          ticks: {
            padding: 0,
            fontSize: 12,
            fontColor: COLORS.lightBlue,
            display: false,
            beginAtZero: true,
          },
          gridLines: {
            display: false,
          },
        },
      ],
    },
    tooltips: {
      enabled: true,
      position: 'nearest',
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
};

export const EmptyLineChart = () => {
  const handleChartData = () => ({
    datasets: [
      {
        label: '',
        data: [0, 0],
        borderColor: COLORS.lightBlue,
      },
    ],
  });

  const emptyChartOptions = {
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
