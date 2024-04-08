import { Theme } from '@razorpay/blade/components';

export const getTabbedChartOptions = (colors: Theme['colors']): unknown => {
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
    tooltips: {
      enabled: true,
      position: 'nearest',
    },
    layout: {
      padding: {
        top: 0,
        left: 0,
        right: 0,
        bottom: 0,
      },
    },
  };
};
