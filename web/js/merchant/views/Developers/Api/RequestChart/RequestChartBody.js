import React from 'react';
import { Line } from 'react-chartjs-2';
import Spinner from 'common/ui/Spinner';
import { timeScale } from './axes';
import { getChartData } from './getChartData';
import NoDataMessage from '../../components/RequestChartNoDataMessage';

export default function RequestChartBody(props) {
  const { data, selectedAggregation, duration, filteredStatusCodeList } = props;

  const chartOptions = {
    ...timeScale({ breakdown: getBreakdown(selectedAggregation) }),
    layout: {
      padding: {
        top: 0,
        left: 0,
        right: 0,
        bottom: 0,
      },
    },
    plugins: {
      legend: {
        position: 'bottom',
      },
    },
  };

  return (
    <div className="request-chart-body">
      {data.loading ? (
        <div className="text-center">
          <Spinner />
        </div>
      ) : null}
      {data.error ? <NoDataMessage title="Oh snap! Couldn’t load graph data." /> : null}
      {!data.loading && !data.error && !data.data?.stats?.length ? (
        <NoDataMessage title="No request logs found for selected time range" />
      ) : null}
      {!data.loading && data.data ? (
        <Line
          options={chartOptions}
          data={getChartData(data.data, selectedAggregation, duration, filteredStatusCodeList)}
        />
      ) : null}
    </div>
  );
}

function getBreakdown(selectedAggregation) {
  if (selectedAggregation.value === 'hour' || selectedAggregation.value === 'minute')
    return 'hourly';
  return 'daily';
}
