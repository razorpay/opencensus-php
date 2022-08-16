import { timeScale } from 'common/utils/chart/new';
import { getSuitableY } from './helper';

/**************************************** Overview Chart Config ****************************************/

export const overviewGraphOptions = {
  responsive: true,
  layout: {
    padding: {
      top: 0,
      left: 0,
      right: 0,
      bottom: 1,
    },
  },
  elements: {
    line: {
      borderColor: '#000000',
      borderWidth: 1,
    },
    point: { radius: 0 },
  },
  legend: { display: false },
  tooltips: { enabled: false },
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
      },
    ],
  },
  animation: false,
};

/****************************************************************************************************/

/**************************************** Line Chart Config ****************************************/

export const getChartAreaConfig = ({ breakdown, startDate, yLabel }) => {
  const generalConfig = timeScale({
    breakdown,
    startDate,
    yLabel,
  });

  const chartOptions = {
    layout: {
      padding: {
        top: 0,
        left: 0,
        right: 0,
        bottom: 0,
      },
    },
    ...generalConfig,
    tooltips: { enabled: false },
    scales: {
      xAxes: [{ ...generalConfig.scales.xAxes[0] }],
      yAxes: [
        {
          ...generalConfig.scales.yAxes[0],
          stacked: false,
          ticks: {
            min: 0,
            max: 100,
            stepSize: 20,
          },
        },
      ],
    },
    animation: false,
  };

  return chartOptions;
};

/**************************************** Pie Chart Config ****************************************/

export const pieChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  legend: {
    display: false,
  },
  layout: {
    padding: {
      top: 30,
      left: 0,
      right: 0,
      bottom: 30,
    },
  },
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
        const centerPoint = arc.getCenterPoint();
        const model = arc._model;
        const color = '#818EA3';
        const angle = Math.atan2(
          centerPoint.y - chartCenterPoint.y,
          centerPoint.x - chartCenterPoint.x,
        );
        // important point 2, this point overlapsed with existed points
        // so we will reduce y by 14 if it's on the right
        // or add by 14 if it's on the left
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

        const edgePointX = point2X < chartCenterPoint.x ? 50 : chart.width - 50;

        if (point2X < chartCenterPoint.x) {
          leftLabelCoordinates.push(point2Y);
        } else {
          rightLabelCoordinates.push(point2Y);
        }
        // Draw Line
        // first line: connect between arc's center point and outside point
        ctx.strokeStyle = color;
        ctx.beginPath();
        ctx.moveTo(centerPoint.x, centerPoint.y);
        ctx.lineTo(point2X, point2Y);
        ctx.stroke();
        // second line: connect between outside point and chart's edge
        ctx.beginPath();
        ctx.moveTo(point2X, point2Y);
        ctx.lineTo(edgePointX, point2Y);
        ctx.stroke();
        //fill custom label
        const labelAlignStyle = edgePointX < chartCenterPoint.x ? 'left' : 'right';
        const labelX = edgePointX;
        const labelY = point2Y;
        ctx.textAlign = labelAlignStyle;
        ctx.textBaseline = 'bottom';
        ctx.fillStyle = '#818EA3';
        ctx.fillText(label, labelX, labelY);
      });
      ctx.restore();
    },
  },
];

/******************************************************************************************/
