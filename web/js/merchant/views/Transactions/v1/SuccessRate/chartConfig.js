import moment from 'moment';
import { getFormattedNumber, getSuitableY, formatIntervals, formatTime } from './helper';
import {
  chartFontColor,
  DOWNTIME_X,
  DOWNTIME_Y,
  gridLineColor,
  namedColors,
  SCATTER,
  SR_X,
  SR_Y,
  TAG_MAP,
} from './constants';
import { custumTooltip } from './customTooltip';

/**************************************** Overview Chart Config ****************************************/

export const overviewGraphOptions = {
  responsive: true,
  layout: { padding: 0 },
  elements: {
    line: { borderColor: namedColors['black.500'], borderWidth: 1 },
    point: { radius: 0, hoverRadius: 0 },
  },
  legend: { display: false },
  tooltips: { enabled: false },
  animation: false,
  scales: {
    xAxes: [
      {
        display: false,
        gridLines: { display: false },
        type: 'time',
        distribution: 'series',
      },
    ],
    yAxes: [
      {
        display: false,
        gridLines: { display: false },
        offset: true,
        ticks: {
          beginAtZero: true,
          min: 0,
          max: 100,
        },
      },
    ],
  },
};

/****************************************************************************************************/

/**************************************** Line Chart Config ****************************************/
const timeAxisUnit = {
  hourly: {
    unit: 'hour',
    stepSize: 1,
  },
  daily: {
    unit: 'day',
    stepSize: 1,
  },
  weekly: {
    unit: 'day',
    stepSize: 7,
  },
  monthly: {
    unit: 'month',
    stepSize: 1,
  },
};

export const getChartAreaConfig = ({ breakdown, xLabel, yLabel }) => {
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
        radius: (ctx) => {
          const { type } = ctx.dataset;
          const isLastPoint = ctx.dataIndex === ctx.dataset.data.length - 1; // change the point radius for last data point
          if (type === 'scatter') return 2;
          if (isLastPoint) return 4;
          return 2;
        },
        hoverRadius: (ctx) => {
          const { type } = ctx.dataset;

          if (type === 'scatter') return 2;
          return 4;
        },
      },
    },
    hover: {
      mode: 'nearest',
      intersect: true,
    },
    tooltips: {
      enabled: false,
      mode: 'point',
      custom: custumTooltip,
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
          const { type } = datasets?.[tooltipItem?.datasetIndex] || {};
          const { from, to } =
            datasets?.[tooltipItem?.datasetIndex]?.data?.[tooltipItem?.index] || {};

          const intervals = formatIntervals({ from, to });

          if (type === SCATTER) return `${intervals} | Downtimes`;

          return intervals;
        },
        afterTitle: ([tooltipItem], { datasets }) => {
          if (breakdown !== 'hourly') return null;

          const { type } = datasets[tooltipItem?.datasetIndex] || {};

          // Need to show the text only if the graph is `not` of type scatter.
          if (type === SCATTER) return null;

          const { from, to } =
            datasets[tooltipItem?.datasetIndex]?.data?.[tooltipItem?.index] || {};

          const startTime = from ? formatTime(from) : '';
          const endTime = to ? formatTime(to) : '';

          return `${startTime} - ${endTime}`;
        },
        beforeLabel: (tooltipItem, { datasets }) => {
          if (breakdown !== 'hourly') return null;

          const { type } = datasets[tooltipItem?.datasetIndex] || {};

          // Need to show the text only if the graph is of type scatter.
          if (type !== SCATTER) return null;

          const { from, to, severity } =
            datasets[tooltipItem?.datasetIndex]?.data?.[tooltipItem?.index] || {};

          const startTime = from ? formatTime(from) : '';
          const endTime = to ? formatTime(to) : 'Present';

          return `${startTime} - ${endTime}, ${severity}`;
        },
        label: (tooltipItem, { datasets }) => {
          const { datasetIndex, yLabel, index } = tooltipItem;

          const datapoint = datasets[datasetIndex]?.data?.[index];
          const { label, type } = datasets[datasetIndex];
          const labelText = TAG_MAP[label] ?? label;

          if (type === SCATTER) {
            return `${labelText}`;
          }

          return `${labelText}: ${yLabel}% | Total payments: ${getFormattedNumber(
            datapoint?.total || 0,
          )}`;
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
          id: SR_X,
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
        {
          id: DOWNTIME_X,
          type: 'time',
          distribution: 'linear',
          gridLines: {
            display: false,
          },
          ticks: {
            display: false,
          },
        },
      ],
      yAxes: [
        {
          id: SR_Y,
          stacked: false,
          offset: true,
          gridLines: {
            color: gridLineColor,
            drawOnChartArea: true,
          },
          ticks: {
            beginAtZero: true,
            suggestedMax: 10,
            maxTicksLimit: 10,
            min: 0,
            max: 100,
            stepSize: 20,
            fontColor: chartFontColor,
          },
        },
        {
          id: DOWNTIME_Y,
          offset: false,
          gridLines: {
            display: false,
          },
          ticks: {
            display: false,
            beginAtZero: true,
          },
        },
      ],
    },
  };

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

/**************************************** Pie Chart Config ****************************************/

export const pieChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  layout: { padding: { top: 40, right: 0, bottom: 40, left: 0 } },
  legend: { display: false },
  tooltips: { enabled: false },
  hover: { mode: null },
  animation: false,
};

export const piePlugins = [
  {
    afterDraw: (chart) => {
      const ctx = chart.chart.ctx;
      ctx.save();
      ctx.font = "12px 'Lato'";
      const leftLabelCoordinates = [];
      const rightLabelCoordinates = [];
      const chartCenterPoint = {
        x: (chart.chartArea.right - chart.chartArea.left) / 2 + chart.chartArea.left,
        y: (chart.chartArea.bottom - chart.chartArea.top) / 2 + chart.chartArea.top,
      };
      chart.config.data.labels.forEach((label, i) => {
        const meta = chart.getDatasetMeta(0);
        const arc = meta.data[i];
        const dataset = chart.config.data.datasets[0];
        const value = dataset.data[i];
        const centerPoint = arc.getCenterPoint();
        const model = arc._model;
        const angle = Math.atan2(
          centerPoint.y - chartCenterPoint.y,
          centerPoint.x - chartCenterPoint.x,
        );
        // important point 2, this point overlapsed with existed points
        // so we will reduce y by 10 if it's on the right
        // or add by 10 if it's on the left
        const point2X = chartCenterPoint.x + Math.cos(angle) * (model.outerRadius + 10);
        let point2Y = chartCenterPoint.y + Math.sin(angle) * (model.outerRadius + 10);

        let suitableY;
        if (point2X < chartCenterPoint.x) {
          // on the left
          suitableY = getSuitableY(point2Y, leftLabelCoordinates, 'left');
        } else {
          // on the right
          suitableY = getSuitableY(point2Y, rightLabelCoordinates, 'right');
        }

        point2Y = suitableY;

        // Added 15 to prevent overlapping of labels if they are on the same side and too close to each other.
        const edgePointX = point2X + 40 < chartCenterPoint.x ? 20 : chart.width - 50;

        if (point2X < chartCenterPoint.x) leftLabelCoordinates.push(point2Y);
        else rightLabelCoordinates.push(point2Y);

        // Draw Line
        // first line: connect between arc's center point and outside point
        ctx.strokeStyle = namedColors['grey.800'];
        ctx.beginPath();
        ctx.moveTo(centerPoint.x, centerPoint.y);
        ctx.lineTo(point2X, point2Y);
        ctx.stroke();
        // second line: connect between outside point and chart's edge
        ctx.beginPath();
        ctx.moveTo(point2X, point2Y);
        ctx.lineTo(edgePointX, point2Y);
        ctx.stroke();
        // fill custom label
        const labelAlignStyle = edgePointX < chartCenterPoint.x ? 'left' : 'right';
        const labelX = edgePointX;
        const labelY = point2Y + 15;
        const valueY = point2Y;
        ctx.textAlign = labelAlignStyle;
        ctx.textBaseline = 'bottom';
        ctx.fillStyle = namedColors['grey.800'];
        ctx.fillText(`${value} %`, labelX, valueY);
        ctx.fillText(label, labelX, labelY);
      });
      ctx.restore();
    },
  },
];

/******************************************************************************************/
