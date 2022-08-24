import { Line } from 'react-chartjs-2';

import { overviewGraphOptions as options } from '../chartConfig';

const _getChartData = (histogram, isActive, canvas) => {
  const ctx = canvas.getContext('2d');
  const gradient = ctx.createLinearGradient(0, 0, 0, 50);
  const primaryGraphColor = isActive ? 'rgba(19, 38, 68, 1)' : 'rgba(166, 176, 191, 1)';
  const gradientStartColor = primaryGraphColor.replace(/1\)$/, '0.55)');
  gradient.addColorStop(0, gradientStartColor);
  gradient.addColorStop(1, '#ffffff00');

  const datasets = [
    {
      data: histogram,
      borderWidth: 1.5,
      backgroundColor: gradient,
      borderColor: primaryGraphColor,
      borderDash: isActive ? [5, 2] : [],
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
