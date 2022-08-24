import moment from 'moment';
import head from 'lodash/head';
import reduce from 'lodash/reduce';
import cloneDeep from 'lodash/cloneDeep';
import store from 'merchant/store';
import {
  DATE_RANGE_PRESETS,
  DEFAULT_INTERVAL,
  DEFAULT_PRESET,
  tabsOrder,
  chartStyle,
  defaultPieChartStyle,
  pieChartStyle,
  TAG_MAP,
} from './constants';

export const getInterval = (startDate, endDate) => {
  const diff = endDate.diff(startDate, 'days');
  if (diff <= 1) {
    return DEFAULT_INTERVAL;
  } else if (diff > 1 && diff <= 24) {
    return 24 * 60; // 1 day ie., 24 hours * 60 minutes
  } else if (diff > 24 && diff <= 60) {
    return 7 * 24 * 60; // 1 week ie., 7 days * 24 hours * 60 minutes
  }
  return DEFAULT_INTERVAL;
};

export const setBreakdownInterval = (from, to) => {
  const start_date = moment.unix(from);
  const end_date = moment.unix(to);
  const diff = end_date.diff(start_date, 'days');
  if (diff <= 1) {
    return 'hourly';
  } else if (diff >= 2 && diff <= 24) {
    return 'daily';
  } else if (diff > 24) {
    return 'weekly';
  }
  return 'hourly';
};

export const initialFilters = () => {
  /**
   * since, we'll not have latest downtime data,
   * we query data with endDate 5mins lesser than the current time.
   */
  const endDate = moment().endOf('hour');
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
    data: intervals?.map((obj) => ({ x: +moment.unix(obj?.from).format('x'), y: obj.sr })),
    ...chartStyle[tagIndex],
  };

  const timestamps = intervals
    .map((obj) => +moment.unix(obj?.from).format('x'))
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
        groups?.[group_by]?.reduce(
          (accumulator, { name, sr } = {}) => {
            if (!name || (name === 'others' && !sr)) return accumulator;
            accumulator.push(name);
            return accumulator;
          },
          ['Overall'],
        ) ?? [];

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

export const getMetricsData = ({ metrics, data, payload, breakdown, group_by }) => {
  const metricsClone = cloneDeep(metrics);
  const groups = data.groups?.[group_by];

  tabsOrder.forEach((tabName, tabIdx) => {
    const groupIdx = groups.findIndex((group) => group.name === tabName.toLocaleLowerCase());

    const args = {
      intervals: (tabIdx > 0 ? groups[groupIdx]?.intervals : data?.intervals) ?? [],
      startTime: payload.from,
      endTime: payload.to,
      breakdown,
      tagIndex: tabIdx > 0 ? groupIdx : 0,
    };

    const vals = metricValues(tabIdx > 0 ? groups[groupIdx] : data, args);
    metricsClone[tabName] = { ...metricsClone[tabName], ...vals };
  });

  return metricsClone;
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

export const getPieChartData = (groupData = []) => {
  const { backgroundColor, borderColor } = defaultPieChartStyle;
  const totalSum = groupData.reduce((total, value) => total + (value?.total ?? 0), 0);

  const result = groupData.reduce(
    (accumulator, datapoint, idx) => {
      // when 'others' SR is '0' there is no need to show on Pie Chart
      if (!datapoint?.sr) return accumulator;
      const _backgroundColor = pieChartStyle?.[idx]?.backgroundColor ?? backgroundColor;
      const _borderColor = pieChartStyle?.[idx]?.borderColor ?? borderColor;
      const percentage = (datapoint?.successful / totalSum) * 100 || 0;
      const percentageValue = percentage.toFixed(2);
      const label = (TAG_MAP[datapoint?.name] ?? datapoint?.name) || '--';
      accumulator?.labels?.push(label);
      accumulator?.datasets?.[0]?.data?.push(percentageValue);
      accumulator?.datasets?.[0]?.backgroundColor?.push(_backgroundColor);
      accumulator?.datasets?.[0]?.borderColor?.push(_borderColor);
      return accumulator;
    },
    {
      labels: [],
      datasets: [{ data: [], backgroundColor: [], borderColor: [], borderWidth: 1 }],
    },
  );

  return result;
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

//Returns a list of initial set of filters for each method.
export const getInitialGroupings = (methodFilters) => {
  reduce(
    methodFilters,
    (acc, tabFilters, key) => {
      acc[key] = [
        ...(acc?.[key] || []),
        ...tabFilters?.map((filter) => {
          return head(filter);
        }),
      ];
      return acc;
    },
    {},
  );
};
