import React, { memo, useMemo } from 'react';
import { Line } from 'react-chartjs-2';
import { getChartAreaConfig } from 'merchant/views/PaymentMetrics/chartConfig';
import { GraphProps } from 'merchant/views/PaymentMetrics/types';

const ChartArea = ({
  interval,
  data,
  xAxisID,
  yAxisID,
  xLabel,
  yLabel,
  btnAction,
  customUnit,
}: GraphProps): React.ReactElement => {
  const chartOptions = useMemo(
    () =>
      getChartAreaConfig({
        breakdown: interval,
        xLabel,
        yLabel,
        xAxisID,
        yAxisID,
        btnAction,
        customUnit,
      }),
    [interval],
  );

  return <Line options={chartOptions} data={data || { datasets: [] }} redraw />;
};

export default memo(ChartArea);
