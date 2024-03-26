/* eslint-disable dot-notation */
import React, { useCallback } from 'react';
import { Line } from 'react-chartjs-2';

import { getTabbedChartOptions } from 'merchant/widgets/TabbedCharts/utils';
import { getChartData } from 'merchant/widgets/common/utils';
import { COLORS } from 'merchant/containers/Home/RTUX/colors';
import { ChartProps } from 'merchant/widgets/common/types';

const LineChart: React.FC<ChartProps> = ({ chartData, unit }): JSX.Element | null => {
  const isEmpty = chartData.data.length === 0;

  const handleChartData = useCallback(
    (canvas) => {
      if (isEmpty) {
        return { labels: [], datasets: [] };
      }
      const ctx = canvas.getContext('2d');
      const gradient = ctx.createLinearGradient(0, -20, 0, 100);
      gradient.addColorStop(0, COLORS.blue);
      gradient.addColorStop(1, 'white');

      const { labels, datasets } = getChartData(chartData, unit);

      datasets[0]['backgroundColor'] = gradient;
      datasets[0]['borderColor'] = COLORS.blue;
      datasets[0]['fill'] = true;

      return {
        labels,
        datasets,
      };
    },
    [chartData, isEmpty],
  );

  const options = getTabbedChartOptions();

  return <Line data={handleChartData} options={options} />;
};

export default LineChart;
