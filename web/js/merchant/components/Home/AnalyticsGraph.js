import { createLineData, timeScale } from 'rzp/utils/chart/index.js';
import { Line } from 'react-chartjs-2';
import LoaderDots from 'rzp/ui/LoaderDots';

export default ({ title, data, loading, error, yLabel, xLabel }) => {
  return (
    <div class="panel">
      <div class="panel-body">
        <h4 class="text-muted">{title}</h4>
        {loading || !data.datasets[0].data.length || error ? (
          <div class="centered">
            {do {
              if (loading) {
                <LoaderDots />;
              } else if (error) {
                <div class="text-danger">Error occurred in loading data!</div>;
              } else {
                <div class="text-muted">
                  No data available for the selected time period
                </div>;
              }
            }}
          </div>
        ) : (
          <Line options={timeScale({ yLabel, xLabel })} data={data} />
        )}
      </div>
    </div>
  );
};
