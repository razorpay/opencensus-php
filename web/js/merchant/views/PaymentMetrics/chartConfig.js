import moment from 'moment';
import { customTooltip, chartHover } from './customTooltip';
import { formatIntervals, formatTime } from './helpers';
import { timeAxisUnit, gridLineColor, chartFontColor } from './constants';

export const getChartAreaConfig = ({ breakdown, xLabel, yLabel, xAxisID, yAxisID, btnAction }) => {
  const now = moment();

  const chartOptions = {
    layout: {
      padding: {
        top: 0,
        left: 0,
        right: 0,
        bottom: 0,
      },
    },
    elements: {
      point: {
        radius: () => 3,
        hoverRadius: () => 4,
      },
      line: {
        lineTension: 10,
      },
    },

    hover: {
      mode: 'nearest',
      intersect: true,
    },
    tooltips: {
      enabled: false,
      mode: 'point',
      custom(tooltipModal) {
        customTooltip.call(this, tooltipModal, btnAction);
      },
      position: 'nearest',
      intersect: true,
      bodySpacing: 4,
      borderWidth: 1,
      backgroundColor: '#ffffff',
      borderColor: '#e0e8f4',
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
        title: ([tooltipItem], { datasets }) => {
          const { from, to } =
            datasets?.[tooltipItem?.datasetIndex]?.data?.[tooltipItem?.index] || {};

          const intervals = formatIntervals({ from, to });

          return intervals;
        },
        afterTitle: ([tooltipItem], { datasets }) => {
          if (breakdown !== 'hourly') return null;

          const { from, to } =
            datasets[tooltipItem?.datasetIndex]?.data?.[tooltipItem?.index] || {};

          const startTime = from ? formatTime(from) : '';
          const endTime = to ? formatTime(to) : '';

          return `${startTime} - ${endTime}`;
        },
        beforeLabel: (tooltipItem, { datasets }) => {
          if (breakdown !== 'hourly') return null;

          const { from, to } =
            datasets[tooltipItem?.datasetIndex]?.data?.[tooltipItem?.index] || {};

          const startTime = from ? formatTime(from) : '';
          const endTime = to ? formatTime(to) : 'Present';

          return `${startTime} - ${endTime}`;
        },
        label: (tooltipItem, { datasets }) => {
          const { datasetIndex, yLabel } = tooltipItem;
          const { label } = datasets[datasetIndex];
          const labelText = label;

          return `${labelText}: ${yLabel}%`;
        },
        labelColor: (item, chart) => {
          const color = chart?.config?.data?.datasets[item.datasetIndex]?.borderColor;

          return { backgroundColor: color, borderColor: 'transparent' };
        },
      },
    },
    animation: false,
    scales: {
      xAxes: [
        {
          id: xAxisID,
          type: 'time',
          distribution: 'linear',
          time: {
            ...timeAxisUnit[breakdown],
          },
          gridLines: {
            color: gridLineColor,
            drawOnChartArea: true,
          },
          ticks: {
            maxRotation: 0,
            fontColor: chartFontColor,
            callback: (_, index, values) => {
              // _ is value
              let format = 'MMM D';
              const currValue = moment(values[index].value);
              if (breakdown === 'hourly') {
                format = 'h a';
              } else if (breakdown === 'monthly') {
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
      yAxes: [
        {
          id: yAxisID,
          stacked: false,
          offset: true,
          gridLines: {
            color: gridLineColor,
            drawOnChartArea: true,
          },
          ticks: {
            beginAtZero: true,
            maxTicksLimit: 10,
            fontColor: chartFontColor,
          },
        },
      ],
    },
  };

  if (btnAction) {
    chartOptions.onHover = chartHover;
  }

  if (xLabel) {
    chartOptions.scales.xAxes[0].scaleLabel = {
      display: true,
      labelString: xLabel,
    };
  }

  if (yLabel) {
    chartOptions.scales.yAxes[0].scaleLabel = {
      display: true,
      labelString: yLabel,
    };
  }

  return chartOptions;
};
