import { createLineData, timeScale } from 'rzp/utils/chart';
import { Line } from 'react-chartjs-2';

export default ({
  title,
  style,
  data,
  loading,
  panelClass,
  error,
  panelStyle,
}) => {
  return (
    <div class={panelClass} style={panelStyle}>
      <h4 class="font-thin text-muted">{title}</h4>
      <div style={style}>
        {loading || !data.datasets[0].data.length || error
          ? <span
              class={`${loading ? 'h1 ' : error ? 'text-danger ' : ''}text-thin`}
            >
              {
                do {
                  if (loading) {
                    ('...');
                  } else if (error) {
                    ('Error occurred in loading data');
                  } else {
                    ('No data available for the selected time period');
                  }
                }
              }
            </span>
          : <Line options={timeScale} data={data} />}
      </div>
    </div>
  );
};
