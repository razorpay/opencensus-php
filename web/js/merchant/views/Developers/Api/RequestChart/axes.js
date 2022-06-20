import { defaults } from 'react-chartjs-2';
import moment from 'moment';

const global = defaults.global;
global.maintainAspectRatio = false;
global.legend.display = false;

// by default straight lines ofr line graph
global.elements.line.tension = 0;
global.elements.line.borderColor = 'rgba(0, 0, 0, 0.05)';

// by default no gap between each pie
global.elements.arc.borderWidth = 0;

global.elements.point.radius = 0;
global.elements.point.hoverRadius = 0;

const tooltips = global.tooltips;
tooltips.mode = 'index';
tooltips.multiKeyBackground = 'rgba(0, 0, 0, 0)';
tooltips.bodySpacing = 10;
tooltips.intersect = false;

global.hover.mode = 'index';
global.hover.intersect = false;

const gridLineColor = '#f0f3f7';

export const timeScale = ({ breakdown }) => {
  const now = moment();

  const scalesObj = {
    scales: {
      xAxes: [
        {
          type: 'time',
          distribution: 'series',
          time: {
            displayFormats: {
              hour: 'MMM D',
              month: 'MMM D',
              day: 'MMM D',
              week: 'MMM D',
              second: 'MMM D',
              millisecond: 'MMM D',
            },
            tooltipFormat: breakdown === 'hourly' ? 'DD MMM hh:mm' : 'DD MMM',
          },
          gridLines: {
            display: false,
            color: gridLineColor,
            drawOnChartArea: true,
          },
          ticks: {
            source: 'data',
            autoSkip: false,
            fontColor: 'rgba(45, 48, 51, 0.5)',
            maxRotation: 0,
            autoSkipPadding: 1,
            callback: (_value, index, values) => {
              const currValue = moment(values[index].value);
              const prevValue = values[index - 1] && moment(values[index - 1].value);
              let format = 'MMM D';

              if (breakdown === 'hourly') {
                // make sure only days are displayed if
                // the breakdown in hourly
                if (prevValue && prevValue.isSame(currValue, 'day')) {
                  return null;
                }
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
          stacked: false,
          ticks: {
            beginAtZero: true,
            maxTicksLimit: 5,
            callback: (value) => {
              // if spaces are not added, the labels get
              // cut
              return `    ${value}`;
            },
            fontColor: 'rgba(45, 48, 51, 0.5)',
          },
          offset: true,
          gridLines: {
            color: gridLineColor,
            drawOnChartArea: true,
          },
        },
      ],
    },
  };

  return scalesObj;
};
