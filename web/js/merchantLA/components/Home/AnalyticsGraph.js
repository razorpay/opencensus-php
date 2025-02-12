import { createLineData, timeScale } from 'common/utils/chart/index.js';
import { Line } from 'react-chartjs-2';
import LoaderDots from 'common/ui/LoaderDots';

export default ({ title, data, loading, error, yLabel, xLabel }) => {
  if (!data) {
    error = true;
  }

  const renderMessage = () => {
    if (loading) {
      return <LoaderDots />;
    }
    if (error) {
      return <div className="text-danger">Error occurred in loading data!</div>;
    }
    return <div className="text-muted">No data available for the selected time period</div>;
  };

  return (
    <div className="panel">
      <div className="panel-body">
        <h4 className="text-muted">{title}</h4>
        {loading || error || !data.datasets[0].data.length ? (
          <div className="centered">{renderMessage()}</div>
        ) : (
          <Line options={timeScale({ yLabel, xLabel })} data={data} />
        )}
      </div>
    </div>
  );
};
