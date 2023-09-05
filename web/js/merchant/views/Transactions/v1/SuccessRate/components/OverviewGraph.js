import { Line } from 'react-chartjs-2';

import { overviewGraphOptions as options } from 'merchant/views/Transactions/v1/SuccessRate/chartConfig';
import { namedColors } from 'common/utils/chart/colors';

const _getChartData = (histogram, isActive, canvas) => {
  const ctx = canvas.getContext('2d');
  const gradient = ctx.createLinearGradient(0, -20, 0, 50);
  const graphColor = isActive ? namedColors.primaryColor : namedColors.blueishGrey;
  const gradientStartColor = graphColor.replace(/1\)$/, '0.55)');
  gradient.addColorStop(0, gradientStartColor);
  gradient.addColorStop(1, '#ffffff00');

  const datasets = [
    {
      data: histogram,
      borderWidth: 1,
      backgroundColor: gradient,
      borderColor: graphColor,
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
