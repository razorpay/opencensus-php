import { defaults } from 'react-chartjs-2';

defaults.global.defaultFontColor = '#666';
defaults.global.defaultFontSize = 11;
defaults.global.layout = {
  padding: {
    left: 10,
    bottom: 15,
    top: 5,
    right: 5,
  },
};

const global = defaults.global;
global.maintainAspectRatio = true;
global.elements.line.lineTension = 0;
global.legend.display = false;

const tooltips = global.tooltips;
tooltips.mode = 'index';
tooltips.multiKeyBackground = 'rgba(0, 0, 0, 0)';
tooltips.bodySpacing = 10;
tooltips.intersect = false;

global.hover.mode = 'index';
global.hover.intersect = false;

const colors = [
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
          minUnit: 'day',
          time: {
            displayFormats: {
              day: 'DD MMM',
            },
            parser: utcMoment => utcMoment.utcOffset('+0000'),
            tooltipFormat: 'ddd DD MMM YYYY',
          },
          gridLines: {
            color: '#f8f8f8',
          },
        },
      ],
      yAxes: [
        {
          ticks: {
            beginAtZero: true,
            suggestedMax: 10,
            maxTicksLimit: 10,
          },
          gridLines: {
            color: '#f8f8f8',
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

export const createLineData = result => {
  return processLineData({
    labels: result.map(r => r.timestamp * 1000),
    datasets: [
      {
        data: result.map(r => r.value),
      },
    ],
  });
};
