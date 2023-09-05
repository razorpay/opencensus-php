import React, { useState, useEffect, memo, useMemo, forwardRef } from 'react';
import { Line } from 'react-chartjs-2';
import { getChartAreaConfig } from 'merchant/views/Transactions/v1/SuccessRate/chartConfig';
import cloneDeep from 'lodash/cloneDeep';

const ChartArea = forwardRef((props, ref) => {
  const { interval, histogram = {} } = props;
  const { datasets = [] } = histogram;
  const [stateDatasets, setDatasets] = useState([]);

  const chartOptions = useMemo(
    () => getChartAreaConfig({ breakdown: interval, yLabel: 'Success Rate' }),
    [interval],
  );

  useEffect(() => {
    const datasetsClone = cloneDeep(datasets);
    setDatasets(datasetsClone);
  }, [datasets]);

  if (!datasets) return null;

  return <Line ref={ref} options={chartOptions} data={{ datasets: stateDatasets }} redraw />;
});

export default memo(ChartArea);
