import { memo, useEffect, useState } from 'react';
import { Bar, Line } from 'react-chartjs-2';
import { getChartOptions } from 'merchant/views/MagicCheckout/RTOAnalytics/utils';
import {
  OVERALL_LINE_CHARTS,
  BREAKDOWN,
} from 'merchant/views/MagicCheckout/RTOAnalytics/constants';

const Graph = ({ data, breakdown, customOptions, isChartStacked = false, widgetName }) => {
  const [chartOptions, setChartOptions] = useState(null);

  useEffect(() => {
    setChartOptions(getChartOptions(breakdown, customOptions, isChartStacked));
  }, [breakdown, isChartStacked, customOptions]);

  return (
    <div className="rto-graph-container">
      {breakdown === BREAKDOWN.days || OVERALL_LINE_CHARTS.includes(widgetName) ? (
        <Line options={chartOptions ?? {}} data={data ?? {}} />
      ) : (
        <Bar options={chartOptions ?? {}} data={data ?? {}} />
      )}
    </div>
  );
};

export default memo(Graph);
