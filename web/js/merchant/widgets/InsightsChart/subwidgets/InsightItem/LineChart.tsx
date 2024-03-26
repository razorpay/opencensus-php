import React, { useCallback } from 'react';
import { Line } from 'react-chartjs-2';

import { getTabbedChartOptions } from './utils';
import { getChartData } from 'merchant/widgets/common/utils';
import { useTheme } from '@razorpay/blade/components';
import { COLORS } from 'merchant/containers/Home/RTUX/colors';
import { ChartProps } from 'merchant/widgets/common/types';

const LineChart = ({
  chartData,
  isChangePositive,
  unit,
}: ChartProps & { isChangePositive: boolean }): JSX.Element | null => {
  const { theme } = useTheme();
  const handleChartData = useCallback(
    (canvas) => {
      const ctx = canvas.getContext('2d');
      const gradient = ctx.createLinearGradient(0, -50, 0, 100);
      gradient.addColorStop(0, isChangePositive ? COLORS.lightGreen : COLORS.lightRed);
      gradient.addColorStop(1, COLORS.backgroundSecondarySubtle);

      const { labels, datasets } = getChartData(chartData, unit);

      datasets[0].backgroundColor = gradient;
      datasets[0].borderColor = isChangePositive
        ? theme.colors.feedback.positive.action.text.link.default.lowContrast
        : theme.colors.feedback.negative.action.text.link.default.lowContrast;
      datasets[0].fill = true;

      return {
        labels,
        datasets,
      };
    },
    [chartData],
  );

  const options = getTabbedChartOptions();

  return <Line data={handleChartData} options={options} />;
};

export default LineChart;
