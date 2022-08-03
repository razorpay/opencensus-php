import { memo, useEffect, useState } from 'react';
import { Bar, Line } from 'react-chartjs-2';
import { getChartOptions } from 'merchant/views/MagicCheckout/RTOAnalytics/utils';

const Graph = ({ data, breakdown }) => {
  const [chartOptions, setChartOptions] = useState(null);

  useEffect(() => {
    setChartOptions(getChartOptions(breakdown));
  }, [setChartOptions, breakdown]);

  return (
    <div className="rto-graph-container">
      {breakdown === 'daily' ? (
        <Line options={chartOptions ?? {}} data={data ?? {}} />
      ) : (
        <Bar options={chartOptions ?? {}} data={data ?? {}} />
      )}
    </div>
  );
};

export default memo(Graph);
