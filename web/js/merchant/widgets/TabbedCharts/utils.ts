import { COLORS } from 'merchant/containers/Home/RTUX/colors';

export const getTabbedChartOptions = (): unknown => {
  return {
    responsive: true,
    legend: {
      display: true,
      position: 'bottom',
      align: 'start',
      labels: {
        fontColor: COLORS.dimBlue,
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
            fontColor: COLORS.lightBlue,
          },
        },
      ],
      yAxes: [
        {
          ticks: {
            padding: 0,
            fontSize: 12,
            maxTicksLimit: 5,
            fontColor: COLORS.lightBlue,
          },
          gridLines: {
            color: COLORS.backgroundPrimarySubtle,
            zeroLineColor: COLORS.backgroundPrimarySubtle,
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
