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

export const timeScale = ({ xLabel, yLabel, breakdown }) => {
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
            color: '#FFFFFF',
            drawOnChartArea: true,
          },
          ticks: {
            source: 'data',
            autoSkip: true,
            fontColor: 'rgba(45, 48, 51, 0.5)',
            maxRotation: 0,
            autoSkipPadding: 21,
            callback: (value, index, values) => {

              if (breakdown !== "hourly") {
              
                return value;
              }

              // make sure only days are displayed if 
              // the breakdown in hourly
              const prevValue = values[index - 1],
                    currValue = values[index];

              if (prevValue && moment(prevValue.value)
                                 .isSame(currValue.value, 'day')) {
                return null;
              }

              return moment(currValue.value).format('MMM D');
            }
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
              return "    "  + humanReadableIndian(value);
            },
            fontColor: 'rgba(45, 48, 51, 0.5)',
          },
          offset: true,
          gridLines: {
            color: '#FFFFFF',
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
