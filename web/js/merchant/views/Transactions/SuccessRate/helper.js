import moment from 'moment';
import { capitalize } from 'lodash';
import store from 'merchant/store';
import {
  DATE_RANGE_PRESETS,
  DEFAULT_INTERVAL,
  DEFAULT_PRESET,
  tabsOrder,
  chartStyle,
  namedColors,
} from './constants';

export const getInterval = (startDate, endDate) => {
  let interval = DEFAULT_INTERVAL;
  const diff = endDate.diff(startDate, 'days');
  if (diff > 3 && diff <= 30) {
    interval = 1440; // 24 hours in minutes
  } else if (diff > 30) {
    interval = 1440 * 7; // 1week in minutes
  }
  return interval;
};

export const initialFilters = () => {
  /**
   * since, we'll not have latest downtime data,
   * we query data with endDate 5mins lesser than the current time.
   */
  const endDate = moment().endOf('hour').add(1, 'second');
  const startDate = endDate
    .clone()
    .add(...DATE_RANGE_PRESETS[DEFAULT_PRESET].slice(1))
    .startOf('hour');

  const payload = {
    interval: DEFAULT_INTERVAL, // in minutes
    startDate,
    endDate,
    preset: null,
  };

  return payload;
};

export const queryFilters = () => {
  const { session, successRate } = store?.getState();
  const user = session?.user;
  const mode = user.isOptimizerEnabled ? 'optimizer' : 'razorpay';
  const { filters, activeTab, tabs } = successRate;
  const { startDate, endDate, interval } = filters;
  const { method, group_by } = tabs[activeTab];
  const payload = {
    entity: 'payments',
    from: startDate.unix(),
    to: endDate.unix(),
    interval,
    mode,
    filters: {
      method,
    },
    group_by: {
      keys: [group_by],
      limit: 3,
    },
  };

  return payload;
};

export const getTimelineData = ({ intervals = [], startTime, endTime, breakdown, tagIndex }) => {
  const dataset = {
    label: tabsOrder[tagIndex],
    data: intervals?.map((obj) => ({ x: +moment.unix(obj.to).format('x'), y: obj.sr })),
    ...chartStyle[tagIndex],
  };

  const timestamps = intervals
    .map((obj) => +moment.unix(obj?.to).format('x'))
    .sort((a, b) => {
      return Number(a) - Number(b);
    });

  let startMs = +moment.unix(startTime).format('x');
  let endMs = +moment.unix(endTime).format('x');

  const firstMs = Number(timestamps[0]);
  const lastMs = Number(timestamps[timestamps.length - 1]);

  if (breakdown === 'hourly') {
    const firstHour = moment(firstMs).startOf('hour').toDate();
    const lastHour = moment(lastMs).startOf('hour').toDate();

    startMs = moment(startMs).startOf('day').toDate();
    endMs = moment().isSame(endMs, 'day')
      ? moment().startOf('hour').toDate()
      : moment(endMs).endOf('day').startOf('hour').toDate();

    if (firstHour > startMs) {
      timestamps.unshift(startMs.getTime());
    }

    if (lastHour < endMs) {
      timestamps.push(endMs.getTime());
    }
  } else if (breakdown === 'daily') {
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
  } else if (breakdown === 'weekly') {
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
  } else if (breakdown === 'monthly') {
    const firstMonthStart = moment(firstMs).startOf('month').toDate().getTime();
    const lastMonthStart = moment(lastMs).startOf('month').toDate().getTime();

    startMs = moment(startMs).startOf('month').toDate();
    endMs = moment(endMs).startOf('month').toDate();

    if (firstMonthStart > startMs) {
      timestamps.unshift(startMs.getTime());
    }

    if (lastMonthStart < endMs) {
      timestamps.push(endMs.getTime());
    }
  }

  return { labels: timestamps, datasets: [dataset] };
};

export const onFetchSR = ({ data, startTime, endTime, breakdown, group_by = '' }) => {
  try {
    const { groups, intervals = [] } = data;

    if (intervals?.length > 0) {
      const tags =
        groups?.[group_by]?.reduce((initArray, obj) => [...initArray, obj.name], ['Overall']) ?? [];

      const options = {
        intervals,
        startTime,
        endTime,
        breakdown,
        tagIndex: tags.indexOf('Overall'),
      };

      const { labels, datasets } = getTimelineData(options);

      return {
        tags,
        histogram: { labels, datasets },
      };
    }
    return {};
  } catch (error) {
    return error;
  }
};

export const metricValues = (obj, options) => {
  const { labels, datasets } = getTimelineData(options);
  const value = {
    sr: obj?.sr ?? '',
    successful: obj?.successful ?? '',
    total: obj?.total ?? '',
    overviewHistogram: { labels, datasets: datasets?.[0]?.data ?? [] },
  };
  return value;
};

export const getSuitableY = (y, yArray = [], direction) => {
  let result = y;
  yArray.forEach((existedY) => {
    if (existedY - 14 < result && existedY + 14 > result) {
      if (direction === 'right') {
        result = existedY + 14;
      } else {
        result = existedY - 14;
      }
    }
  });

  return result;
};

export const getPieChartData = (groupData) => {
  const colors = Object.values(namedColors);
  return groupData?.reduce(
    (acc, data, index) => {
      acc?.labels?.push(capitalize(data?.name) ?? '--');
      acc?.datasets?.[0]?.data?.push(data?.sr);
      acc?.datasets?.[0]?.backgroundColor?.push(colors[index]);
      acc?.datasets?.[0]?.borderColor?.push(colors[index]);
      return acc;
    },
    {
      labels: [],
      datasets: [{ data: [], backgroundColor: [], borderColor: [], borderWidth: 1 }],
    },
  );
};

export const getMerchantErrorsPayload = () => {
  const { session, successRate } = store?.getState();
  const user = session?.user;
  const mode = user.isOptimizerEnabled ? 'optimizer' : 'razorpay';
  const { tabs, activeTab, filters } = successRate;
  const { method } = tabs[activeTab];
  const { startDate, endDate } = filters;
  const payload = {
    entity: 'payments',
    from: startDate.unix(),
    to: endDate.unix(),
    mode,
    filters: {
      method,
    },
    group_by: {
      limit: 4,
    },
  };

  return payload;
};
