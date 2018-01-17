export default {
  title: {
    text: '',
  },
  subtitle: {
    text: '',
  },
  credits: {
    enabled: false,
  },
  tooltip: {
    borderWidth: 0,
    backgroundColor: 'rgba(255, 255, 255, 1)',
    borderRadius: 0,
    shared: true,
  },
  xAxis: {
    crosshair: {
      width: 2.5,
      zIndex: 5,
      color: '#FFFFFF',
    },
    gridLineColor: '#FFFFFF',
    gridZIndex: 20,
    gridLineWidth: 0.5,
    tickColor: 'transparent',
  },
  yAxis: {
    gridLineColor: 'transparent',
    title: {
      text: '',
    },
  },
  plotOptions: {
    area: {
      marker: {
        enabled: false,
        states: {
          hover: {
            enabled: false,
          },
        },
      },
      fillOpacity: 1,
    },
  },
  colors: ['#4B5471', '#5F7FB9', '#75C2D8', '#ACACE7', '#EB7878', '#ACACE7'],
  legend: {
    enabled: false,
  },
};
