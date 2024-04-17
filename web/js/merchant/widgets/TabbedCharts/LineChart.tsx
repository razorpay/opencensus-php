/* eslint-disable dot-notation */
import React, { useCallback } from 'react';
import { Line } from 'react-chartjs-2';

import { getTabbedChartOptions } from 'merchant/widgets/TabbedCharts/utils';
import { getChartData } from 'merchant/widgets/common/utils';
import { ChartProps } from 'merchant/widgets/common/types';
import { useTheme } from '@razorpay/blade/components';

const LineChart: React.FC<ChartProps> = ({ chartData, unit }): JSX.Element | null => {
  const isEmpty = chartData.data.length === 0;
  const { theme } = useTheme();

  const handleChartData = useCallback(
    (canvas) => {
      if (isEmpty) {
        return { labels: [], datasets: [] };
      }
      const ctx = canvas.getContext('2d');
      const gradient = ctx.createLinearGradient(0, 0, 0, 300);
      gradient.addColorStop(0, theme.colors.surface.border.primary.normal);
      gradient.addColorStop(1, 'white');

      const { labels, datasets } = getChartData(chartData, unit);

      datasets[0]['backgroundColor'] = gradient;
      datasets[0]['borderColor'] = theme.colors.surface.border.primary.normal;
      datasets[0]['fill'] = true;

      return {
        labels,
        datasets,
      };
    },
    [chartData, isEmpty],
  );

  const options = getTabbedChartOptions(theme.colors);

  return <Line data={handleChartData} options={options} />;
};

export default LineChart;
