import React from 'react';
import { connect } from 'react-redux';
import { Line } from 'react-chartjs-2';
import moment from 'moment';

import { namedColors } from 'common/utils/chart/colors';
import Spinner from 'common/ui/Spinner';
import Input from 'common/new-ui/Input';
import Form from 'common/new-ui/Form';

import { fetchStats } from 'merchant/reducers/webhooks';

const MS_IN_ONE_MIN = 1000 * 60;
const SECONDS_IN_30_MIN = 30 * 60;
const DEFAULT_TIME_PERIOD = 'last3Days';
const DEFAULT_METRIC = 'latency';

const timePeriodOptions = [
  { label: 'Last 4 hours', name: 'last4Hours' },
  { label: 'Last 24 hours', name: 'last24Hours' },
  { label: 'Last 3 Days', name: 'last3Days' },
  { label: 'Last 7 Days', name: 'last7Days' },
];

const metricOptions = [
  { label: 'Event Status', name: 'response_status' },
  { label: 'Response Time', name: 'latency' },
];

const metricMap = {
  '0.99': { label: '99 Percentile', color: namedColors.blue },
  '0.95': { label: '95 Percentile', color: namedColors.brown },
  mean: { label: 'Average', color: namedColors.orange },
  success: { label: 'Successfull Events', color: namedColors.blue },
  fail: { label: 'Failed Events', color: namedColors.red },
};

class WebhookStats extends React.Component {
  formValues = {
    metricType: DEFAULT_METRIC,
    timePeriod: DEFAULT_TIME_PERIOD,
  };

  timeStamps = {};

  componentDidMount() {
    this.fetchStats();
  }

  fetchStats = () => {
    const { timePeriod, metricType } = this.formValues;
    let endTime, startTime, granularity;
    const now = moment();
    switch (timePeriod) {
      case 'last4Hours':
        endTime = floorTo30Min(now);
        startTime = moment(endTime, 'X').subtract(4, 'hours').format('X');
        granularity = 'minute';
        break;
      case 'last24Hours':
        endTime = floorTo30Min(now);
        startTime = moment(endTime, 'X').subtract(24, 'hours').format('X');
        granularity = 'minute';
        break;
      case 'last3Days':
        endTime = now.startOf('day').format('X');
        startTime = now.subtract(3, 'days').format('X');
        granularity = 'hour';
        break;
      case 'last7Days':
        endTime = now.startOf('day').format('X');
        startTime = now.subtract(7, 'days').format('X');
        granularity = 'hour';
        break;
      default:
        return null;
    }

    const options = {
      interval: {
        ts_start: startTime,
        ts_end: endTime,
      },
      granularity,
      report_type: metricType,
    };

    this.timeStamps = { startTime, endTime };

    return this.props.fetchStats(this.props.id, options);
  };

  onChange = ({ target }) => {
    const { value, name } = target;
    this.formValues[name] = value;

    this.fetchStats();
  };

  getChartData = () => {
    const data = this.props.stats.data;
    const metricType = this.formValues.metricType;
    return {
      datasets: filterRequiredDataSets(data.metric_family.metrics, metricType).map((metric) => {
        const metricName = getMetricName(metric.labels, metricType);
        return {
          label: metricMap[metricName].label,
          data: metric.values.map(({ v, t }) => ({
            x: new Date(Number(t) * 1000),
            y: v,
          })),

          // styling
          ...getDataStylingOptions(metricName),
        };
      }),
    };
  };

  render() {
    return (
      <div class="WebhookStats">
        <Form layout="tabular">
          <Input.Group class="InputGroup--inline" label="Webhook Stats">
            <div class="Input-content">
              <Input.Select
                name="metricType"
                size="half_small"
                options={metricOptions}
                onChange={this.onChange}
                disabled={this.props.stats.loading}
                defaultValue={DEFAULT_METRIC}
              />

              <Input.Select
                name="timePeriod"
                size="half_small"
                options={timePeriodOptions}
                onChange={this.onChange}
                disabled={this.props.stats.loading}
                defaultValue={DEFAULT_TIME_PERIOD}
              />
            </div>
          </Input.Group>
        </Form>
        {this.props.stats.loading ? (
          <div class="page-spinner-container">
            <Spinner />
          </div>
        ) : (
          <Line
            options={getChartOptions(this.formValues, this.timeStamps)}
            data={this.getChartData}
          />
        )}
      </div>
    );
  }
}

const periodStepSizeMap = {
  last4Hours: 1,
  last24Hours: 4,
  last7Days: 24,
  last3Days: 12,
};

function getChartOptions(formValues, timeStamps) {
  return {
    scales: getScaleOptions(formValues, timeStamps),
    legend: {
      display: true,
      position: 'bottom',
      labels: {
        usePointStyle: true,
      },
    },
    tooltips: {
      backgroundColor: 'rgba(0,0,0,1)',
      bodySpacing: 15,
      position: 'nearest',
      callbacks: {
        label: (item, data) =>
          `${data.datasets[item.datasetIndex].label}: ${
            formValues.metricType === 'response_status' ? item.yLabel : getYAxesLabel(item.yLabel)
          }`,
        labelColor: (item, chart) => {
          const color = chart.config.data.datasets[item.datasetIndex].borderColor;
          return { backgroundColor: color, borderColor: color };
        },
      },
    },
    layout: {
      padding: {
        top: 0,
        left: 0,
        right: 0,
        bottom: 0,
      },
    },
  };
}

function getScaleOptions({ metric, timePeriod }, timeStamps) {
  return {
    xAxes: getXAxes({ timePeriod }, timeStamps),
    yAxes: getYAxes(metric),
  };
}

function getXAxes({ timePeriod }, { startTime, endTime }) {
  return [
    {
      type: 'time',
      distribution: 'linear',
      time: {
        displayFormats: {
          hour: 'MMM D',
        },
        unit: 'hour',
        tooltipFormat: 'hh:mm A MMM D, YYYY',
        stepSize: periodStepSizeMap[timePeriod],
      },
      ticks: {
        source: 'auto',
        min: Number(startTime) * 1000,
        max: Number(endTime) * 1000,
        beginAtZero: true,
        autoSkip: false,
        callback: timePeriod !== 'last7Days' ? getXAxesLabel : undefined,
        maxRotation: 0,
      },
      gridLines: {
        // color of zero line same as other grid lines
        zeroLineColor: 'rgba(0, 0, 0, 0.1)',
      },
    },
  ];
}

function getXAxesLabel(value, index, values) {
  const currentValue = moment(values[index].value);
  const labels = [currentValue.format('h:mm A')];

  const prevValue = index !== 0 && moment(values[index - 1].value);

  if (!prevValue || !prevValue.isSame(currentValue, 'day')) {
    labels.push(currentValue.format('MMM D'));
  }

  return labels;
}

function getYAxes(metric) {
  return [
    {
      ticks: {
        suggestedMax: 5,
        maxTicksLimit: 5,
        beginAtZero: true,
        autoSkip: true,
        // don't know why but sending undefined like in xAxes callback triggers an error
        callback: metric !== 'response_status' ? getYAxesLabel : (value) => value,
      },
    },
  ];
}

function getYAxesLabel(value) {
  if (value <= 500) {
    return `${value} ms`;
  } else if (value > 500 && value < MS_IN_ONE_MIN) {
    return `${value / 1000} s`;
  } else {
    return `${value / MS_IN_ONE_MIN} min`;
  }
}

function getDataStylingOptions(metricName) {
  return {
    backgroundColor: 'rgba(0,0,0,0)',
    borderColor: metricMap[metricName].color,
    borderWidth: 2,
    pointStyle: 'line',
  };
}

function filterRequiredDataSets(dataSets, selectedMetricType) {
  const dataSetsRequired = Object.keys(metricMap);
  return dataSets.filter(({ labels }) =>
    dataSetsRequired.includes(getMetricName(labels, selectedMetricType)),
  );
}

function getMetricName(nameContainer, metricType) {
  const nameKey = metricType === 'latency' ? 'stat_name' : 'status';
  return nameContainer[nameKey];
}

function floorTo30Min(time) {
  const unixEpoch = time.format('X');
  return unixEpoch - (unixEpoch % SECONDS_IN_30_MIN);
}

export default connect(
  (state) => ({
    stats: state.webhooks.stats,
  }),
  { fetchStats },
)(WebhookStats);
