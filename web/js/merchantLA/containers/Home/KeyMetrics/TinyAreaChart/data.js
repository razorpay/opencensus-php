const options = {
  responsive: true,
  legend: {
    display: false,
  },
  layout: {
    padding: {
      top: 0,
      left: 0,
      right: 0,
      bottom: 1,
    },
  },
  elements: {
    line: {
      borderColor: '#000000',
      borderWidth: 1,
    },
    point: {
      radius: 0,
    },
  },
  tooltips: {
    enabled: false,
  },
  scales: {
    yAxes: [
      {
        display: false,
        gridLines: {
          display: false,
        },
      },
    ],
    xAxes: [
      {
        display: false,
        gridLines: {
          display: false,
        },
      },
    ],
  },
};

export default options;
