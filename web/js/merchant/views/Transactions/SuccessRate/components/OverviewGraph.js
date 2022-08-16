import { Line } from 'react-chartjs-2';

import { overviewGraphOptions as options } from '../chartConfig';
import { namedColors } from '../constants';

const _getChartData = (histogram, isActive, canvas) => {
  const ctx = canvas.getContext('2d');
  const borderColor = isActive ? namedColors['blue.500'] : namedColors['black.500'];
  const gradientColor = isActive ? namedColors['blue.400'] : namedColors['black.400'];
  const gradient = ctx.createLinearGradient(0, -20, 0, 50);
  const startColor = borderColor.replace(/1\)$/, '0.5)'); // reducing opacity of primary color

  gradient.addColorStop(0, startColor);
  gradient.addColorStop(1, gradientColor);

  const datasets = [
    {
      data: histogram,
      borderWidth: 1,
    },
  ];

  return { datasets };
};

const OverviewGraph = ({ histogram, isActive }) => {
  if (!histogram?.length) return null;

  const getChartData = _getChartData.bind(null, histogram, isActive);
  return <Line options={options} data={getChartData} />;
};

export default OverviewGraph;
