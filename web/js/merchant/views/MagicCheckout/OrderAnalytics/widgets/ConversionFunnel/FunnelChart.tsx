import React from 'react';
import { HorizontalBar } from 'react-chartjs-2';
import { CHART_CONFIG } from './constants';
import { chartLabelPlugin } from './utils';

const FunnelChart = ({ data }) => {
  return (
    <div className="magic-chart-container">
      <HorizontalBar plugins={[chartLabelPlugin]} options={CHART_CONFIG ?? {}} data={data ?? {}} />
    </div>
  );
};

export default FunnelChart;
