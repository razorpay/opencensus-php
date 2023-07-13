import {
  momentDurationFuncMap,
  momentDurationMap,
  GRAPH_INTERVALS_MAP,
} from 'merchant/views/PaymentMetrics/constants';
import moment from 'moment';
/**
 * this function add 0 value for the missing timestamp
 * between start and end date on the basis of breakdown
 */
export const getTimelineData = ({
  data,
  startTime,
  endTime,
  valueKey = 'value',
  breakdown = GRAPH_INTERVALS_MAP.hourly,
}) => {
  const timelineGroupMap = {};
  const datasets = [];

  if (data.length === 0) {
    return [];
  }

  data.forEach((item) => {
    const timestamp = item.timestamp * 1000;
    timelineGroupMap[timestamp] = item[valueKey];
  });

  /*
   * Transforms data into the input format for chart.js
   */
  let timestamps = Object.keys(timelineGroupMap).sort((a, b) => {
    return Number(a) - Number(b);
  });
  let startMs = moment(startTime * 1000);
  let endMs = moment(endTime * 1000);
  const firstMs = Number(timestamps[0]);
  const lastMs = Number(timestamps[timestamps.length - 1]);

  if (breakdown === GRAPH_INTERVALS_MAP.hourly) {
    const firstHour = moment(firstMs).startOf('hour').toDate();
    const lastHour = moment(lastMs).startOf('hour').toDate();

    startMs = startMs.toDate();

    endMs = endMs.startOf('hour').toDate();

    if (firstHour > startMs) {
      timestamps.unshift(startMs.getTime());
    }

    if (lastHour < endMs) {
      timestamps.push(endMs.getTime());
    }
  } else if (breakdown === GRAPH_INTERVALS_MAP.daily) {
    const firstDayStart = moment(firstMs).startOf('day').toDate();
    const lastDayStart = moment(lastMs).startOf('day').toDate();

    startMs = moment(startMs).startOf('day').toDate();
    endMs = moment(endMs).startOf('day').toDate();

    if (firstDayStart > startMs) {
      timestamps.unshift(startMs.getTime());
    }

    if (lastDayStart < endMs) {
      timestamps.push(endMs.getTime());
    }
  } else if (breakdown === GRAPH_INTERVALS_MAP.weekly) {
    // momentjs start of week is sunday, whereas
    // data start of week is monday, so adding 1 day
    const firstWeekStart = moment(firstMs).startOf('isoWeek').toDate();
    const lastWeekStart = moment(lastMs).startOf('isoWeek').toDate();

    startMs = moment(startMs).startOf('isoWeek').toDate();
    endMs = moment(endMs).startOf('isoWeek').toDate();

    if (firstWeekStart > startMs) {
      timestamps.unshift(startMs.getTime());
    }

    if (lastWeekStart < endMs) {
      timestamps.push(endMs.getTime());
    }
  }

  timestamps = timestamps.reduce((result, timestamp) => {
    /* fill in the missing data points*/

    timestamp = Number(timestamp);
    let prevTimestamp = result[result.length - 1];

    if (!prevTimestamp) {
      result.push(timestamp);
      return result;
    }

    let numPointsGap = moment
      .duration(timestamp - prevTimestamp)
      [momentDurationFuncMap[breakdown]]();

    while (numPointsGap > 1) {
      prevTimestamp = moment(prevTimestamp).add(1, momentDurationMap[breakdown]).toDate().getTime();

      result.push(prevTimestamp);

      numPointsGap--;
    }

    result.push(timestamp);

    return result;
  }, []);

  timestamps.forEach((timestamp) => {
    // if missing value
    if (!timelineGroupMap[timestamp]) {
      timelineGroupMap[timestamp] = 0;
    }
    const yAxisVal = timelineGroupMap[timestamp];
    datasets.push({
      x: timestamp,
      to: moment(timestamp)?.add(1, momentDurationMap[breakdown])?.toDate()?.getTime(),
      from: timestamp,
      y: Math.round(yAxisVal),
    });
  });

  return datasets;
};
