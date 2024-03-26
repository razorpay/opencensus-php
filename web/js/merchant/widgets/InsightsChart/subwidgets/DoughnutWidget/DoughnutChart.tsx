import React, { useCallback } from 'react';
import { Doughnut } from 'react-chartjs-2';

import { getOptions } from 'merchant/widgets/InsightsChart/subwidgets/DoughnutWidget/utils';
import { doughnutColors } from 'merchant/containers/Home/RTUX/colors';
import { getChartData } from 'merchant/widgets/common/utils';
import { ChartProps } from 'merchant/widgets/common/types';

const DoughnutChart = ({ chartData, unit }: ChartProps): any => {
  const handleChartData = useCallback(() => {
    const { labels, datasets } = getChartData(chartData, unit);

    datasets[0].backgroundColor = doughnutColors;
    datasets[0].borderWidth = 0;

    return { labels, datasets };
  }, [chartData]);

  const options = getOptions();

  return <Doughnut data={handleChartData} options={options} />;
};

export default DoughnutChart;
