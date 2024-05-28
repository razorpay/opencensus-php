/* eslint-disable dot-notation */
import React, { useEffect, useState } from 'react';
import { Line, ChartComponentProps } from 'react-chartjs-2';

import { getLineChartOptions } from 'merchant/widgets/TabbedCharts/utils';
import { getChartData } from 'merchant/widgets/common/utils';
import { ChartProps } from 'merchant/widgets/common/types';
import { useTheme } from '@razorpay/blade/components';

const LineChart: React.FC<ChartProps> = ({ chartData, unit }): JSX.Element | null => {
  const [data, setData] = useState<ChartComponentProps['data']>([]);
  const [options, setOptions] = useState<ChartComponentProps['options']>({});
  const isEmpty = chartData.data.length === 0;
  const { theme } = useTheme();

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
      const gradient1 = ctx.createLinearGradient(0, 0, 0, 350);
      gradient1.addColorStop(0, '#B4CDFD');
      gradient1.addColorStop(1, '#F5F8FF');
      return gradient1;
    };
    datasets[0]['lineTension'] = 0;
    datasets[0]['borderColor'] = theme.colors.interactive.border.primary.default;
    datasets[0]['fill'] = true;
    if (datasets[1]) {
      // eslint-disable-next-line @typescript-eslint/ban-ts-comment
      // @ts-ignore
      datasets[1]['backgroundColor'] = (context) => {
        const ctx = context.chart.ctx;
        const gradient2 = ctx.createLinearGradient(0, 0, 0, 300);
        gradient2.addColorStop(0, '#D1DAF1');
        gradient2.addColorStop(1, 'rgba(210, 219, 241, 0.40)');
        return gradient2;
      };
      datasets[1]['borderColor'] = theme.colors.surface.border.gray.normal;
      datasets[1]['fill'] = true;
      datasets[1]['lineTension'] = 0;
    }

    return {
      labels,
      datasets,
    };
  }

  return <Line data={data} options={options} />;
};

export default LineChart;
