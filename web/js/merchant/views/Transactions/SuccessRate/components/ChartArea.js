import React, { forwardRef } from 'react';
import { Line } from 'react-chartjs-2';
import { getChartAreaConfig } from '../chartConfig';

const ChartArea = forwardRef((props, ref) => {
  const { interval, histogram } = props;
  const { datasets } = histogram;

  const chartOptions = getChartAreaConfig({
    breakdown: interval,
    yLabel: 'Success Rate',
  });

  if (!datasets) return null;

  return <Line ref={ref} options={chartOptions} data={{ datasets }} redraw={true} />;
});

export default ChartArea;
