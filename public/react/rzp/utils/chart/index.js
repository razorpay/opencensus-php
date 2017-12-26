import { defaults } from 'react-chartjs-2';
import moment from 'moment';
import { humanReadableIndian } from '../numerals';

const global = defaults.global;
global.maintainAspectRatio = false;
global.legend.display = false;

// by default straight lines ofr line graph
global.elements.line.tension = 0;
global.elements.line.borderColor = 'rgba(0, 0, 0, 0)';

// by default no gap between each pie
global.elements.arc.borderWidth = 0;

const tooltips = global.tooltips;
tooltips.mode = 'index';
tooltips.multiKeyBackground = 'rgba(0, 0, 0, 0)';
tooltips.bodySpacing = 10;
tooltips.intersect = false;

global.hover.mode = 'index';
global.hover.intersect = false;

global.elements.point.radius = 0;
global.elements.point.hoverRadius = 0;
global.elements.point.hitRadius = 0;

export const colors = [
  [75, 84, 113],
  [95, 127, 185],
  [117, 194, 216],
  [172, 172, 231],
  [235, 120, 120],
  [35, 183, 229],
  [52, 152, 219],
  [46, 204, 113],
  [230, 126, 34],
  [241, 196, 15],
  [155, 89, 182],
  [231, 76, 60],
  [26, 188, 156],
];

const rgb = (array, alpha) => {
  if (alpha) return `rgba(${array[0]}, ${array[1]}, ${array[2]}, ${alpha})`;
  return `rgb(${array[0]}, ${array[1]}, ${array[2]})`;
};

export const timeScale = ({ xLabel, yLabel }) => {
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
              hour: 'MMM D',
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
            callback: value => humanReadableIndian(value),
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

export const processLineData = data => {
  data.datasets.map((d, index) => {
    let color = colors[index];
    d.pointBorderColor = '#fff';
    d.pointHoverBorderColor = '#fff';
    d.pointBackgroundColor = rgb(color);
    d.borderColor = rgb(color);
    d.backgroundColor = rgb(color, 0.4);
  });
  return data;
};

export const createLineData = (rawData, column, title) => {
  return processLineData({
    labels: rawData.map(d => moment(d.created_at * 1e3)),
    datasets: [
      {
        label: title,
        data: rawData.map(d => {
          return d[column];
        }),
      },
    ],
  });
};
