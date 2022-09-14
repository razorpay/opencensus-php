import moment from 'moment';
import { reduce, head, map, unionBy, filter, cloneDeep, upperFirst } from 'lodash';
import store, { getUser } from 'merchant/store';
import {
  DATE_RANGE_PRESETS,
  DEFAULT_INTERVAL,
  DEFAULT_PRESET,
  tabsOrder,
  chartStyle,
  defaultPieChartStyle,
  pieChartStyle,
  TAG_MAP,
  DEFAULT_OPTIMIZER_FILTERS,
  TABS_WITH_OPTIMIZER_DROPDOWN_FILTERS,
  TABS_VS_OPTIMIZER_GROUP_BY,
  DEFAULT_GROUP_BY,
  FILTERS_VS_DISPLAY_NAMES,
  breakdownInterval,
} from './constants';

export const getInterval = (startDate, endDate) => {
  const diff = endDate.diff(startDate, 'days');
  if (diff <= 1) {
    return DEFAULT_INTERVAL;
  } else if (diff > 1 && diff <= 24) {
    return 24 * 60; // 1 day ie., 24 hours * 60 minutes
  } else if (diff > 24) {
    return 7 * 24 * 60; // 1 week ie., 7 days * 24 hours * 60 minutes
  }
  return DEFAULT_INTERVAL;
};

export const getBreakdownInterval = (from, to) => {
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
    startDate,
    endDate,
    preset: null,
  };

  return payload;
};

export const getDefaultOptimizerFilterValues = () =>
  reduce(
    DEFAULT_OPTIMIZER_FILTERS,
    (acc, defaultFilter) => {
      acc.push(...defaultFilter.map((item) => item.value));
      return acc;
    },
    [],
  );

const getSelectedFilters = (selectedDropdownFilterOptions, user) => {
  const defaultOptimizerFilters = getDefaultOptimizerFilterValues();
  if (!user.isOptimizerEnabled) return {};
  return selectedDropdownFilterOptions?.reduce((acc, option) => {
    const { query, value } = option || {};
    if (!defaultOptimizerFilters?.includes(value)) {
      acc[query] = [value];
    }
    return acc;
  }, {});
};

export const queryFilters = (updateDropdownOptions) => {
  const { session, successRate } = store?.getState();
  const user = session?.user;
  const { filters, activeTab, tabs } = successRate;
  const mode = activeTab === 'Overall' || !user.isOptimizerEnabled ? 'razorpay' : 'optimizer';
  const { startDate, endDate } = filters;
  const { method, group_by, selectedDropdownFilterOptions = {}, selectedInterval } = tabs[
    activeTab
  ];
  let _group_by = [group_by];
  if (updateDropdownOptions) {
    if (user.isOptimizerEnabled) {
      _group_by = TABS_VS_OPTIMIZER_GROUP_BY?.[activeTab];
    } else {
      _group_by = [DEFAULT_GROUP_BY[activeTab]];
    }
  }
  const payload = {
    entity: 'payments',
    from: startDate.unix(),
    to: endDate.unix(),
    interval: breakdownInterval[selectedInterval] || selectedInterval,
    mode,
    filters: {
      method,
      ...(updateDropdownOptions ? {} : getSelectedFilters(selectedDropdownFilterOptions, user)),
    },
    group_by: {
      keys: _group_by,
      limit: user.isOptimizerEnabled ? 3 : 4, // 3 for dropdown filters in case of optimizer merchant and 4 for graph pills in case of rzp merchant.
    },
  };

  return payload;
};

export const getErrorMessage = (error) => {
  let message = error?.errors?.[0] ?? error?.message;
  if (error?.status_code === 500) {
    message = 'Internal server error';
  }
  return message;
};

export const generateDatasets = (intervals) => {
  return intervals?.map((obj) => {
    const from = +moment.unix(obj?.from).format('x');
    const to = +moment.unix(obj?.to).format('x');
    return {
      x: from,
      y: obj.sr,
      from,
      to,
    };
  });
};

export const getTimelineData = ({ intervals = [], startTime, endTime, breakdown }) => {
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

  return timestamps;
};

export const getIntervals = ({ tag, data, group_by, activeTab }) => {
  const _group_by =
    activeTab === 'Overall' || !getUser()?.isOptimizerEnabled ? group_by : 'procurer';
  return (
    (tag === 'Overall'
      ? data?.intervals
      : data?.groups[_group_by]?.find((obj) => obj.name === tag)?.intervals) ?? []
  );
};

export const onFetchSR = ({
  data,
  startTime,
  endTime,
  breakdown,
  group_by = '',
  activeTab,
  updateSelectedTags,
  selectedTags: initialSelectedTags,
}) => {
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
      };

      const selectedTags = updateSelectedTags ? tags : initialSelectedTags;

      const labels = getTimelineData(options);
      const datasets = selectedTags?.map((tag) => {
        const intervals = getIntervals({ tag, data, group_by, activeTab });
        return {
          label: tabsOrder[tags?.indexOf(tag)],
          data: generateDatasets(intervals),
          ...chartStyle[tags?.indexOf(tag)],
        };
      });
      return {
        tags,
        selectedTags,
        histogram: { labels, datasets },
      };
    }
    return {};
  } catch (error) {
    return error;
  }
};

export const metricValues = (obj, options) => {
  const labels = getTimelineData(options);
  const value = {
    sr: obj?.sr ?? '',
    successful: obj?.successful ?? '',
    total: obj?.total ?? '',
    overviewHistogram: { labels, datasets: generateDatasets(options?.intervals) ?? [] },
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
    };

    const vals = metricValues(tabIdx > 0 ? groups[groupIdx] : data, args);
    metricsClone[tabName] = { ...metricsClone[tabName], ...vals };
  });

  return metricsClone;
};

export const getSuitableY = (y, yArray = [], direction) => {
  const offset = 10;
  let result = y;

  yArray.forEach((existedY) => {
    if (existedY - offset < result && existedY + offset > result) {
      if (direction === 'right') {
        result = existedY + offset;
      } else {
        result = existedY - offset;
      }
    }
  });

  return result;
};

export const getTagLabel = (name) => {
  const defaultLabel = getUser()?.isOptimizerEnabled ? upperFirst(name) : name;
  return (TAG_MAP[name] ?? defaultLabel) || '--';
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
      const percentage = (datapoint?.total / totalSum) * 100 || 0;
      const percentageValue = percentage.toFixed(2);
      const label = getTagLabel(datapoint?.name);
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

export const getMerchantErrorsPayload = (updateDropdownOptions) => {
  const { session, successRate } = store?.getState();
  const user = session?.user;
  const { tabs, activeTab, filters } = successRate;
  const { method, selectedDropdownFilterOptions } = tabs[activeTab];
  const { startDate, endDate } = filters;
  const payload = {
    entity: 'payments',
    from: startDate.unix(),
    to: endDate.unix(),
    mode: 'razorpay',
    filters: {
      method,
      ...(updateDropdownOptions ? {} : getSelectedFilters(selectedDropdownFilterOptions, user)),
    },
    group_by: {
      limit: 6,
    },
  };

  return payload;
};

//Returns a list of initial set of filters for each method.
export const getInitialGroupings = (methodFilters) => methodFilters?.map((filter) => head(filter));

export const getFormattedFilters = (filters) =>
  reduce(
    filters,
    (acc, values, key) => {
      acc.push([
        ...(DEFAULT_OPTIMIZER_FILTERS?.[key] || []),
        ...values?.map((filterDetails) => {
          const { code, name } = filterDetails || {};
          return {
            value: code || name,
            text: FILTERS_VS_DISPLAY_NAMES?.[name] || upperFirst(name),
            query: key,
          };
        }),
      ]);
      return acc;
    },
    [],
  );

export const getOptimizerFilters = (data, activeTab) => {
  const filters = data?.groups?.procurer?.reduce((acc, procurerDetails) => {
    const { groups } = procurerDetails || {};
    map(groups, (groupDetails, groupKey) => {
      acc[groupKey] = filter(
        unionBy(acc?.[groupKey] || [], groupDetails, (item) => item?.code),
        (item) => !['', 'others'].includes(item?.code),
      );
    });
    return acc;
  }, {});
  return !TABS_WITH_OPTIMIZER_DROPDOWN_FILTERS?.includes(activeTab)
    ? []
    : getFormattedFilters(filters);
};

export const getFormattedNumber = (number) => {
  return new Intl.NumberFormat('en-IN').format(number || 0);
};
