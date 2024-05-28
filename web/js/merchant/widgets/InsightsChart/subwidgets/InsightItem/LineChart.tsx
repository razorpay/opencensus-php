/* eslint-disable dot-notation */
import React, { useEffect, useState } from 'react';
import { useTheme } from '@razorpay/blade/components';
import { Line, ChartComponentProps } from 'react-chartjs-2';

import { ChartProps } from 'merchant/widgets/common/types';
import { getChartData } from 'merchant/widgets/common/utils';

import { getLineChartOptions } from './utils';

const LineChart = ({
  chartData,
  isChangePositive,
  unit,
}: ChartProps & { isChangePositive: boolean }): JSX.Element | null => {
  const [data, setData] = useState<ChartComponentProps['data']>([]);
  const [options, setOptions] = useState<ChartComponentProps['options']>({});
  const { theme } = useTheme();
  const isEmpty = chartData.data.length === 0;

  useEffect(() => {
    setData(generateChartData(chartData, isEmpty));
  }, [chartData, isEmpty]);

  useEffect(() => {
    setOptions(getLineChartOptions(theme.colors, data));
  }, [theme.colors, data]);

  function generateChartData(chartData, isEmpty) {
    if (isEmpty) {
      return { labels: [], datasets: [] };
    }

    const { labels, datasets } = getChartData(chartData, unit);

    // backgroundColor also takes a callback function (incorrect type) - https://stackoverflow.com/a/71178143/6127580
    // eslint-disable-next-line @typescript-eslint/ban-ts-comment
    // @ts-ignore
    datasets[0]['backgroundColor'] = (context) => {
      const ctx = context.chart.ctx;
      const gradient = ctx.createLinearGradient(0, 0, 0, 150);
      gradient.addColorStop(0, isChangePositive ? '#CBE9E5' : '#FEE4E2');
      gradient.addColorStop(1, isChangePositive ? '#F9FEFD' : '#FFF5F5');

      return gradient;
    };
    datasets[0].borderColor = isChangePositive
      ? theme.colors.interactive.text.positive.normal
      : theme.colors.interactive.text.negative.normal;
    datasets[0].fill = true;

    return {
      labels,
      datasets,
    };
  }

  return <Line data={data} options={options} />;
};

export default LineChart;
