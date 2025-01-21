import React from 'react';
import { Line } from 'react-chartjs-2';

import { getCompactNumber } from '@apps/digital-bills/src/utils/helpers/numberFormatting';

type GraphProps = {
  graphData: {
    labels: string[];
    data: number[] | (number | null)[];
  };
  legendLabel: string;
};

const Graph = ({ graphData: { labels, data }, legendLabel }: GraphProps) => {
  const options = {
    animation: false,
    responsive: true,
    maintainAspectRatio: false,
    legend: {
      display: true,
      position: 'bottom',
      labels: {
        fontFamily: 'Inter',
        fontSize: 14,
        boxWidth: 14,
        padding: 20,
      },
    },
    tooltips: {
      xPadding: 12,
      yPadding: 12,
      titleFontFamily: 'Inter',
      titleFontSize: 14,
      bodyFontFamily: 'Inter',
      titleSpacing: 10,
      bodySpacing: 10,
      cornerRadius: 4,
      backgroundColor: 'rgba(47, 66, 86, 1)',
      displayColors: false,
      callbacks: {
        label: (tooltipItem: any) => {
          return `₹${tooltipItem?.yLabel}`;
        },
        title: (tooltipItem: any) => {
          return tooltipItem[0]?.xLabel
            ? new Date(tooltipItem[0]?.xLabel)?.toISOString().split('T')[0]
            : '';
        },
      },
    },
    scales: {
      xAxes: [
        {
          type: 'time',
          time: {
            unit: 'month',
            displayFormats: {
              month: 'MMM YY',
            },
          },
          ticks: {
            fontFamily: 'Inter',
            fontColor: 'rgba(118, 142, 167, 1)',
            padding: 10,
            callback: (value: string) => {
              return value.toUpperCase();
            },
          },
          gridLines: {
            display: false,
            drawOnChartArea: false,
            drawTicks: false,
          },
        },
      ],
      yAxes: [
        {
          ticks: {
            beginAtZero: false,
            fontFamily: 'Inter',
            fontColor: 'rgba(118, 142, 167, 1)',
            padding: 10,
            callback: (value: number) => {
              return getCompactNumber(value);
            },
            maxTicksLimit: 6,
          },
          gridLines: {
            display: false,
            drawOnChartArea: false,
            drawTicks: false,
          },
        },
      ],
    },
  };

  const chartData = {
    labels,
    datasets: [
      {
        fill: true,
        label: legendLabel,
        data,
        borderColor: 'rgba(48, 94, 255, 0.09)',
        backgroundColor: (context: any) => {
          const ctx = context.chart.ctx;
          const gradient = ctx.createLinearGradient(0, 0, 0, 240);
          gradient.addColorStop(0, 'rgba(48, 94, 255, 0.09)');
          gradient.addColorStop(1, 'rgba(255, 255, 255, 1)');
          return gradient;
        },
      },
    ],
  };
  return <Line options={options} data={chartData} />;
};

export default Graph;
