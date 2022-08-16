import React, { forwardRef } from 'react';
import { Line } from 'react-chartjs-2';
import { getChartAreaConfig } from '../chartConfig';

const ChartArea = forwardRef((props, ref) => {
  const { startDate, interval, histogram } = props;
  const { datasets } = histogram;

  const chartOptions = getChartAreaConfig({
    breakdown: interval,
    startDate,
    yLabel: 'Success Rate',
  });

  if (!datasets) return null;

  return <Line ref={ref} options={chartOptions} data={{ datasets }} redraw={true} />;
});

export default ChartArea;
