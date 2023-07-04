import { memo, useEffect, useState } from 'react';
import { Line } from 'react-chartjs-2';
import { getCostSavedChartOptions } from 'merchant/views/MagicCheckout/RTOAnalytics/widgets/CostSaved/utils';

const Graph = ({ data, breakdown, isManualReviewOpted }) => {
  const [chartOptions, setChartOptions] = useState(null);

  useEffect(() => {
    setChartOptions(getCostSavedChartOptions(breakdown, isManualReviewOpted));
  }, [setChartOptions, breakdown]);

  return (
    <div className="costSaved-graph-container">
      <Line options={chartOptions ?? {}} data={data ?? {}} />
    </div>
  );
};

export default memo(Graph);
