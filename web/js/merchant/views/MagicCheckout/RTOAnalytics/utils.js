import moment from 'moment';
import {
  BREAKDOWN_MAP,
  defaultOptions,
  CHART_COLORS,
  DATASET_LABEL_MAP,
} from 'merchant/views/MagicCheckout/RTOAnalytics/constants';

export function getLabels(startTime, endTime, breakdown) {
  const timestamps = [];
  const breakdownValue = BREAKDOWN_MAP[breakdown]?.value;
  const startMoment = moment(startTime).startOf(
    breakdown === 'weekly' ? 'isoWeek' : breakdownValue,
  );
  const endMoment = moment(endTime).startOf(breakdown === 'weekly' ? 'isoWeek' : breakdownValue);

  timestamps.push(startMoment.toDate().getTime());
  for (let i = 0; i < endMoment.diff(startMoment, breakdownValue); i++) {
    const prevTimestamp = timestamps[timestamps.length - 1];
    timestamps.push(moment(prevTimestamp).add(1, breakdownValue).toDate().getTime());
  }
  return timestamps;
}

export function getChartOptions(breakdown) {
  const options = { ...defaultOptions };
  const {
    tooltips,
    scales: { xAxes },
  } = options;

  if (breakdown === 'daily') {
    xAxes[0].offset = false;
  } else {
    xAxes[0].offset = true;
  }

  xAxes[0].ticks.callback = (_value, index, values) => {
    const currVal = moment(values[index].value);
    let format = 'MMM D';

    if (breakdown === 'monthly') {
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
        .endOf(breakdown === 'weekly' ? 'isoWeek' : breakdownValue);
      let format = 'ddd DD MMM YYYY';

      if (breakdown === 'weekly' || breakdown === 'monthly') {
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

export const chartsDataFormatter = (
  rawData,
  breakdown,
  startTime,
  endTime,
  datasetsNameList = [],
) => {
  const breakdownValue = BREAKDOWN_MAP[breakdown]?.value;
  const startMoment = moment(startTime).startOf(
    breakdown === 'weekly' ? 'isoWeek' : breakdownValue,
  );
  let labels = getLabels(startTime, endTime, breakdown);
  const datasetsObj = {};

  // initialise objects for all datasets in datasetsNameList
  datasetsNameList.forEach((datasetKey) => {
    datasetsObj[datasetKey] = {
      backgroundColor: CHART_COLORS[datasetKey],
      data: [],
      label: DATASET_LABEL_MAP[datasetKey].label,
    };

    // for line chart in case of daily breakdown
    if (breakdown === 'daily') {
      datasetsObj[datasetKey].backgroundColor = 'transparent';
      datasetsObj[datasetKey].borderColor = CHART_COLORS[datasetKey];
      datasetsObj[datasetKey].fill = false;
    }
  });

  let prevTimestamp;

  rawData?.forEach((dataObj) => {
    const timestamp = moment(Number(dataObj.period) * 1000)
      .startOf(breakdown === 'weekly' ? 'isoWeek' : breakdownValue)
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

    datasetsNameList.forEach((datasetKey) => {
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

export const onBreakdownChange = (
  value,
  breakdown,
  startTime,
  endTime,
  fetch,
  setBreakdown,
  widgetName,
) => {
  if (value === breakdown) return;
  setBreakdown(value);
  const endDate = moment(endTime).unix();
  const current = moment().unix();
  fetch(widgetName, {
    name: widgetName,
    aggregation_type: value,
    date_range: {
      from: moment(startTime).add(5, 'hours').add(30, 'minutes').unix(),
      to: endDate > current ? current : endDate,
    },
  });
};
