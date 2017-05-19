import { createLineData, timeScale } from 'rzp/utils/chart';
import { Line } from 'react-chartjs-2';
import Spinner from 'rzp/ui/Spinner';

export default ({ title, style, data, loading, panelClass, error }) => {
  return (
    <div class={panelClass}>
      <h4 class="font-thin text-muted">{title}</h4>
      <div style={style}>
        {loading || !data.datasets[0].data.length || error
          ? <span
              class={`${loading ? 'h1 ' : error ? 'text-danger ' : ''}text-thin`}
            >
              {loading
                ? '...'
                : error
                    ? 'Error occurred in loading data'
                    : 'No data available for the selected time period'}
            </span>
          : <Line options={timeScale} data={data} />}
      </div>
    </div>
  );
};
