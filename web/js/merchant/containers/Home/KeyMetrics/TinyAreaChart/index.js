import { Line } from 'react-chartjs-2';
import { namedColors } from 'rzp/utils/chart/colors';

const options = {
  "responsive": true,
  "legend": {
    "display": false
  },
  "layout": {
    "padding": {
      "top": 0,
      "left": 0,
      "right": 0,
      "bottom": 1
    },
  },
  "elements": {
    "line": {
      "borderColor": "#000000",
      "borderWidth": 1
    },
    "point": {
      "radius": 0
    }
  },
  "tooltips": {
    "enabled": false
  },
  "scales": {
    "yAxes": [
      {
        "display": false,
        "gridLines": {
          "display": false
        }
      }
    ],
    "xAxes": [
      {
        "display": false,
        "gridLines": {
          "display": false
        }
      }
    ]
  }
};

const _getChartData = (histogram, isActive, canvas) => {


  const ctx        = canvas.getContext("2d"),
        graphColor = isActive
                       ? namedColors.primaryColor
                       : namedColors.blueishGrey,
        gradient   = ctx.createLinearGradient(0,0,0,40),
		// reducing opacity of primary color
		startColor = (graphColor).replace(/1\)$/, "0.5)");

  gradient.addColorStop(0, startColor);
  gradient.addColorStop(1, 'rgba(255, 255, 255, 0)');

  let { datasets } = histogram;

  if (datasets && datasets[0]) {

    datasets[0].backgroundColor = gradient;
    datasets[0].borderColor = graphColor;
	datasets[0].borderWidth = 1;
  }

  return {...histogram, datasets};
};


export default ({histogram, isActive}) => {

  const getChartData = _getChartData.bind(null, histogram, isActive);

  if (!histogram) {

    return null;
  }

  return <Line options={options} data={getChartData}/>
};
