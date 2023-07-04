import moment from 'moment';
import cloneDeep from 'lodash/cloneDeep';
import {
  BREAKDOWN_MAP,
  defaultOptions,
  CHART_COLORS,
  DATASET_LABEL_MAP,
  LINE_CHART_GRAPH_COLOR,
  BREAKDOWN,
  OVERALL_LINE_CHARTS,
  REQUEST_LIMIT,
} from 'merchant/views/MagicCheckout/RTOAnalytics/constants';

export function getLabels(startTime, endTime, breakdown) {
  const timestamps = [];
  const breakdownValue = BREAKDOWN_MAP[breakdown]?.value;
  const startMoment = moment(startTime).startOf(
    breakdown === BREAKDOWN.weeks ? 'isoWeek' : breakdownValue,
  );

  const endMoment = moment(endTime).startOf(
    breakdown === BREAKDOWN.weeks ? 'isoWeek' : breakdownValue,
  );

  timestamps.push(startMoment.toDate().getTime());
  for (let i = 0; i < endMoment.diff(startMoment, breakdownValue); i++) {
    const prevTimestamp = timestamps[timestamps.length - 1];
    timestamps.push(moment(prevTimestamp).add(1, breakdownValue).toDate().getTime());
  }

  return timestamps;
}

export function getChartOptions(breakdown, customOptions = {}, isChartStacked = false) {
  const options = cloneDeep({ ...defaultOptions, ...customOptions });

  const {
    tooltips,
    scales: { xAxes, yAxes },
  } = options;

  if (breakdown === BREAKDOWN.days) {
    yAxes[0].stacked = true;
  }

  xAxes[0].offset = true;

  if (breakdown !== BREAKDOWN.days && isChartStacked) {
    xAxes[0].stacked = isChartStacked;
    yAxes[0].stacked = isChartStacked;
  }

  xAxes[0].ticks.callback = (_value, index, values) => {
    const currVal = moment(values[index].value);
    let format = 'MMM D';

    if (breakdown === BREAKDOWN.months) {
      format = 'MMM';
    }

    return currVal.format(format);
  };

  tooltips.callbacks = {
    ...tooltips.callbacks,
    footer([tooltipItem]) {
      let displayLabel;
      const breakdownValue = BREAKDOWN_MAP[breakdown]?.value;
      const label = tooltipItem?.xLabel;
      const labelStartMoment = moment(label);
      const labelEnd = labelStartMoment
        .clone()
        .endOf(breakdown === BREAKDOWN.weeks ? 'isoWeek' : breakdownValue);
      let format = 'ddd DD MMM YYYY';

      if (breakdown === BREAKDOWN.weeks || breakdown === BREAKDOWN.months) {
        format = 'MMM DD YYYY';
        displayLabel = `${labelStartMoment.format(format)} - ${labelEnd.format(format)}`;
      } else {
        displayLabel = labelStartMoment.format(format);
      }

      return displayLabel;
    },
  };

  return options;
}

export const chartsDataFormatter = (...args) => {
  const [
    rawData,
    breakdown,
    startTime,
    endTime,
    datasetsNameList = [],
    isChartStacked = false,
    widgetName,
    datasetKey,
  ] = args;

  const breakdownValue = BREAKDOWN_MAP[breakdown]?.value;
  const startMoment = moment(startTime).startOf(
    breakdown === BREAKDOWN.weeks ? 'isoWeek' : breakdownValue,
  );
  let labels = getLabels(startTime, endTime, breakdown);
  const datasetsObj = {};

  let datasetsList = datasetsNameList;
  // case in which a particular dataset needs to be displayed
  if (datasetKey) {
    const updatedDatasetsNameList = datasetsList.filter((dataKey) => dataKey === datasetKey);

    if (updatedDatasetsNameList.length !== 0) datasetsList = updatedDatasetsNameList;
  }

  if (isChartStacked) {
    datasetsList = datasetsList.slice(1);
  }
  // initialise objects for all datasets in datasetsList
  datasetsList.forEach((datasetKey) => {
    datasetsObj[datasetKey] = {
      backgroundColor: CHART_COLORS[datasetKey],
      data: [],
      label: DATASET_LABEL_MAP[datasetKey].label,
      barThickness: 12,
    };

    // for line chart in case of daily breakdown
    if (breakdown === BREAKDOWN.days || OVERALL_LINE_CHARTS.includes(widgetName)) {
      datasetsObj[datasetKey].borderColor = CHART_COLORS[datasetKey];
      datasetsObj[datasetKey].fill = 'origin';
      datasetsObj[datasetKey].tension = 0;
      datasetsObj[datasetKey].borderWidth = 2;
      datasetsObj[datasetKey].backgroundColor = (context) => {
        const chart = context.chart;
        const { ctx, chartArea } = chart;

        if (!chartArea) {
          // This case happens on initial chart load
          return null;
        }
        const gradient = ctx.createLinearGradient(0, chartArea.bottom, 0, chartArea.top);
        gradient.addColorStop(0, LINE_CHART_GRAPH_COLOR[datasetKey]);
        gradient.addColorStop(1, '#FEFFFE');

        return gradient;
      };
    }
  });

  let prevTimestamp;

  rawData?.forEach((dataObj) => {
    const timestamp = moment(Number(dataObj.period) * 1000)
      .startOf(breakdown === BREAKDOWN.weeks ? 'isoWeek' : breakdownValue)
      .toDate()
      .getTime();

    if (!prevTimestamp) {
      prevTimestamp = startMoment.clone().toDate().getTime();
      if (prevTimestamp < timestamp) {
        // this is the timestamp for the very first non-empty entry we get for a period
        // within the selected date range
        pushDataToDatasets(0, datasetsObj, true);
      }
    }

    let numPointsGap = Math.abs(moment(timestamp).diff(prevTimestamp, breakdownValue));
    // filling in the gaps for periods b/w date range with no BE data
    while (numPointsGap > 1) {
      pushDataToDatasets(0, datasetsObj, true);
      numPointsGap--;
    }

    datasetsList.forEach((datasetKey) => {
      pushDataToDatasets(
        dataObj[DATASET_LABEL_MAP[datasetKey].response_key] ?? 0,
        datasetsObj,
        false,
        datasetKey,
      );
    });

    prevTimestamp = timestamp;
  });

  // the above forEach loop only fills in the gaps b/w the startTime and
  // the latest endTime that is there in the data and not till the selected endTime
  // e.g. BE data can have sparse data till 15th Jan while endTime selected is 23rd Jan
  // in this case, it will fill in the gaps till 15th Jan and not after it since there's
  // no data anymore. The following piece of code takes care that we do cut the tail end
  // and do not display it on the chart.

  // for when the tail end of the data is empty
  const datasetsArray = Object.values(datasetsObj);
  if (datasetsArray[0].data.length < labels.length) {
    labels = labels.slice(0, datasetsArray[0].data.length);
  }

  return { labels, datasets: datasetsArray };
};

function pushDataToDatasets(datapoint, datasetObj, sameForAll = false, keyForData) {
  if (sameForAll) {
    Object.keys(datasetObj).forEach((datasetKey) => {
      datasetObj[datasetKey].data.push(datapoint);
    });
  } else {
    datasetObj[keyForData].data.push(datapoint);
  }
}

export const onBreakdownChange = (...args) => {
  const [
    value,
    breakdown,
    startTime,
    endTime,
    fetchWidgets,
    setBreakdown,
    widgetName,
    additionalInfo = {},
  ] = args;

  if (value === breakdown) return;
  setBreakdown(value);
  const endDate = moment(endTime).unix();
  const startDate = moment(startTime).add(5, 'hours').add(30, 'minutes').unix();
  fetchWidgets(widgetName, value, startDate, endDate, additionalInfo);
};

export const getWidgetData = (
  widget,
  aggregationType,
  startTime,
  endTime,
  fetchWidgets,
  setRequestCount,
  additionalInfo = {},
) => {
  const endDate = moment(endTime).unix();
  const startDate = moment(startTime).add(5, 'hours').add(30, 'minutes').unix();

  fetchWidgets(widget, aggregationType, startDate, endDate, additionalInfo)
    .then(() => {
      setRequestCount(0);
    })
    .catch(() => {
      setRequestCount((preVal) => preVal + 1);
    });
};

export const onRequestCountChange = (requestCount, fetchData, setRequestCount) => {
  if (requestCount > 0 && requestCount <= REQUEST_LIMIT) {
    fetchData();
  } else if (requestCount > REQUEST_LIMIT) {
    setRequestCount(0);
  }
};
