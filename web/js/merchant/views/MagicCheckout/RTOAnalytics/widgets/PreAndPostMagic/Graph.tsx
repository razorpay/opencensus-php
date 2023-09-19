import React, { memo, useEffect, useState } from 'react';
import { Line } from 'react-chartjs-2';
import { getPreAndPostMagicChartOptions } from 'merchant/views/MagicCheckout/RTOAnalytics/widgets/PreAndPostMagic/utils';

const Graph = ({
  data,
  breakdown,
}: {
  data: Record<string, any> | null;
  breakdown: string;
}): JSX.Element => {
  const [chartOptions, setChartOptions] = useState<Record<string, any> | null>(null);

  useEffect((): void => {
    setChartOptions(getPreAndPostMagicChartOptions(breakdown));
  }, [setChartOptions, breakdown]);

  return (
    <div className="costSaved-graph-container">
      <Line options={chartOptions ?? {}} data={data ?? {}} />
    </div>
  );
};

export default memo(Graph);
