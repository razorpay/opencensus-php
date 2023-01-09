import moment from 'moment';

const gridLineColor = '#f0f3f7';

export const timeScale = ({ breakdown }) => {
  const now = moment();

  const scalesObj = {
    scales: {
      xAxes: [
        {
          type: 'time',
          distribution: 'series',
          time: {
            displayFormats: {
              hour: 'MMM D',
              month: 'MMM D',
              day: 'MMM D',
              week: 'MMM D',
              second: 'MMM D',
              millisecond: 'MMM D',
            },
            tooltipFormat: getToolTipFormat(breakdown),
          },
          gridLines: {
            display: false,
            color: gridLineColor,
            drawOnChartArea: true,
          },
          ticks: {
            source: 'data',
            autoSkip: false,
            fontColor: 'rgba(45, 48, 51, 0.5)',
            maxRotation: 0,
            autoSkipPadding: 1,
            callback: (_value, index, values) => {
              const currValue = moment(values[index].value);
              const prevValue = values[index - 1] && moment(values[index - 1].value);
              let format = 'MMM D';

              if (breakdown === 'hour' || breakdown === 'minute') {
                // make sure only days are displayed if
                // the breakdown in hourly or minutes
                if (prevValue && prevValue.isSame(currValue, 'day')) {
                  return null;
                }
              }
              if (!currValue.isSame(now, 'year')) {
                format += ' YYYY';
              }
              return currValue.format(format);
            },
          },
        },
      ],
      yAxes: [
        {
          stacked: false,
          ticks: {
            beginAtZero: true,
            maxTicksLimit: 5,
            callback: (value) => {
              // if spaces are not added, the labels get
              // cut
              return `    ${value}`;
            },
            fontColor: 'rgba(45, 48, 51, 0.5)',
          },
          offset: true,
          gridLines: {
            color: gridLineColor,
            drawOnChartArea: true,
          },
        },
      ],
    },
  };

  return scalesObj;
};

function getToolTipFormat(breakdown) {
  if (breakdown === 'minute') return 'hh:mm a';

  if (breakdown === 'hour') return 'DD MMM hh:mm a';

  return 'DD MMM';
}
