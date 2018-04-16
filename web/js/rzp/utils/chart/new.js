import { defaults } from 'react-chartjs-2';
import moment from 'moment';
import { humanReadableIndian } from '../numerals';

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

export const timeScale = ({ xLabel, yLabel, breakdown, startDate }) => {
  const now = moment();

  let scalesObj = {
    scales: {
      xAxes: [
        {
          type: 'time',
          distribution: 'series',
          time: {
            displayFormats: {
              hour: 'MMM D',
              month: 'MMM YYYY',
              day: 'MMM D',
              week: 'MMM YYYY',
              second: 'MMM D',
              millisecond: 'MMM D',
            },
            tooltipFormat: 'ddd DD MMM YYYY',
          },
          gridLines: {
            color: gridLineColor,
            drawOnChartArea: true,
          },
          ticks: {
            source: 'data',
            autoSkip: true,
            fontColor: 'rgba(45, 48, 51, 0.5)',
            maxRotation: 0,
            autoSkipPadding: 21,
            callback: (value, index, values) => {
              let currValue = moment(values[index].value),
                prevValue =
                  values[index - 1] && moment(values[index - 1].value),
                format = 'MMM D';

              if (breakdown === 'monthly') {
                format = 'MMM';
              } else if (breakdown === 'weekly') {
                if (currValue < startDate) {
                  currValue = startDate;
                }
              } else if (breakdown === 'hourly') {
                console.log(
                  prevValue && prevValue.toDate(),
                  currValue.toDate()
                );
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
          stacked: true,
          ticks: {
            beginAtZero: true,
            suggestedMax: 10,
            maxTicksLimit: 10,
            callback: value => {
              // if spaces are not added, the labels get
              // cut
              return '    ' + humanReadableIndian(value);
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

  if (xLabel) {
    scalesObj.scales.xAxes[0].scaleLabel = {
      display: true,
      labelString: xLabel,
    };
  }
  if (yLabel) {
    scalesObj.scales.yAxes[0].scaleLabel = {
      display: true,
      labelString: yLabel,
    };
  }

  return scalesObj;
};

export const getMillisecondsFromBreakdown = breakdown => {
  switch (breakdown) {
    case 'daily':
      return 24 * 60 * 60 * 1000;
    case 'weekly':
      return 7 * getMillisecondsFromBreakdown('daily');
    case 'monthly':
      return 4 * getMillisecondsFromBreakdown('weekly');
  }

  return 0;
};
