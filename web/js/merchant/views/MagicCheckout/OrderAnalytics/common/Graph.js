import { memo, useEffect, useState } from 'react';
import { Bar, Line } from 'react-chartjs-2';
// eslint-disable-next-line import/no-cycle
import { LINE_CHARTS } from 'merchant/views/MagicCheckout/OrderAnalytics/constants';
import { getChartOptions } from 'merchant/views/MagicCheckout/OrderAnalytics/utils';
import { useOrderAnalyticsContext } from 'merchant/views/MagicCheckout/OrderAnalytics/OrderAnalyticsContext';

const Graph = ({ data, customOptions, widgetName, extend = true, aggregation }) => {
  const [chartOptions, setChartOptions] = useState(null);
  const { analyticsData } = useOrderAnalyticsContext();
  useEffect(() => {
    setChartOptions(getChartOptions(customOptions, analyticsData?.aggregate, extend));
  }, [customOptions, extend, aggregation]);
  return chartOptions ? (
    <div className="magic-chart-container">
      {LINE_CHARTS.includes(widgetName) ? (
        <Line options={chartOptions ?? {}} data={data ?? {}} />
      ) : (
        <Bar options={chartOptions ?? {}} data={data ?? {}} />
      )}
    </div>
  ) : null;
};

export default memo(Graph);
