import moment from 'moment';
import isObject from 'is-object';
import { reduce, head, map, unionBy, filter, cloneDeep, upperFirst, uniqBy } from 'lodash';
import store, { getUser } from 'merchant/store';
import {
  DEFAULT_PRESET,
  tabsOrder,
  defaultTagStyle,
  tagStyles,
  chartStyle,
  fetchDefaultReturn,
  breakdownInterval,
  TAG_MAP,
  DEFAULT_OPTIMIZER_FILTERS,
  STATIC_OPTIMIZER_FILTERS,
  TABS_WITH_OPTIMIZER_DROPDOWN_FILTERS,
  TABS_VS_OPTIMIZER_GROUP_BY,
  DEFAULT_GROUP_BY,
  FILTERS_VS_DISPLAY_NAMES,
  TAG_OVERALL_MAP,
  PRESETS,
  DEFAULT_GROUP_BY_LIMIT,
  GROUP_BY_KEY_VS_LIMIT,
  NETBANKING,
  EMANDATE,
  SR_Y,
  SR_X,
  DOWNTIME_X,
  DOWNTIME_Y,
  CARD,
  CARD_NETWORKS,
  PAYMENT_METHOD_VS_CALLOUT_DISPLAY_TEXT,
  DEFAULT_METHOD,
  CUSTOM_ERROR_TYPES,
} from './constants';

export const getBreakdownInterval = (from, to) => {
  const diff = to.diff(from, 'days');
  if (diff <= 1) {
    return 'hourly';
  } else if (diff >= 2 && diff <= 14) {
    return 'daily';
  } else if (diff >= 14) {
    return 'weekly';
  }
  return 'hourly';
};

export const initialFilters = () => {
  /**
   * since, we'll not have latest downtime data,
   * we query data with endDate 5mins lesser than the current time.
   */
  const { value, unit } = PRESETS[DEFAULT_PRESET];
  const endDate = moment().endOf('hour');
  const startDate = endDate.clone().subtract(value, unit).startOf('hour');

  const payload = {
    startDate,
    endDate,
    preset: PRESETS[DEFAULT_PRESET],
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

const getSelectedFilters = ({ selectedDropdownFilterOptions, isOptimizerEnabled }) => {
  if (!isOptimizerEnabled) return {};

  const defaultOptimizerFilters = getDefaultOptimizerFilterValues();

  return selectedDropdownFilterOptions?.reduce((acc, option) => {
    const { query, value } = option || {};
    if (!defaultOptimizerFilters?.includes(value)) {
      acc[query] = [value];
    }
    return acc;
  }, {});
};

/**
 * @param updateDropdownOptions is true, when tab is not equal to 'Overall'
 */

export const queryFilters = (updateDropdownOptions = false, refreshMetricTabs = false) => {
  const { session, successRate } = store?.getState();
  const { isOptimizerEnabled = false } = session?.user ?? {};
  const { filters, activeTab: stateActiveTab, tabs } = successRate;
  const activeTab = refreshMetricTabs ? 'Overall' : stateActiveTab;
  const mode = activeTab === 'Overall' || !isOptimizerEnabled ? 'razorpay' : 'optimizer';
  const { startDate, endDate } = filters;
  const {
    selectedDropdownFilterOptions = {},
    selectedInterval,
    selectedCardType,
  } = tabs[activeTab] || {};

  let _group_by = [DEFAULT_GROUP_BY[activeTab]];
  let filterMethods = DEFAULT_METHOD[activeTab]?.map(({ method }) => method);

  if (updateDropdownOptions) {
    if (isOptimizerEnabled) {
      _group_by = TABS_VS_OPTIMIZER_GROUP_BY?.[activeTab];
    } else {
      _group_by = [tabs[activeTab]?.group_by];
    }
  }

  if (isOptimizerEnabled && activeTab === 'Overall') {
    filterMethods = DEFAULT_METHOD[activeTab]?.reduce((methods, { optimizerEnabled, method }) => {
      if (optimizerEnabled) {
        methods.push(method);
      }
      return methods;
    }, []);
  }

  const payload = {
    entity: 'payments',
    from: startDate.unix(),
    to: endDate.unix(),
    interval: breakdownInterval[selectedInterval] || selectedInterval,
    mode,
    filters: {
      method: filterMethods,
      ...(updateDropdownOptions
        ? getSelectedFilters({
            selectedDropdownFilterOptions,
            isOptimizerEnabled,
          })
        : {}),
      ...(!isOptimizerEnabled && activeTab === 'Card' ? { type: [selectedCardType] } : {}),
    },
    group_by: {
      keys: _group_by,
      limit: isOptimizerEnabled ? 3 : GROUP_BY_KEY_VS_LIMIT[_group_by] || DEFAULT_GROUP_BY_LIMIT, // 3 for dropdown filters in case of optimizer merchant and other limits as per groupBy for graph pills in case of rzp merchant.
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
    const { from, to, sr, total } = obj;

    const formattedFrom = +moment.unix(from).format('x');
    const formattedTo = +moment.unix(to).format('x');
    return {
      x: formattedFrom,
      y: sr,
      from: formattedFrom,
      to: formattedTo,
      total,
    };
  });
};

export const generateDowntimeDataSets = ({
  intervals = [],
  tag,
  activeTab,
  startTime,
  endTime,
  groupBy,
}) => {
  // Substracting 1hr from endtime as to not overflow the graph with downtimes leaving behind the sr graph
  const newEndTime = +moment(endTime * 1000)
    .clone()
    .subtract(1, 'hour')
    .format('X');

  return intervals
    ?.filter((interval) => {
      // Default end date to present date when downtime is still going on
      const { instrument, begin, end } = interval;
      const isInbetweenTime = begin >= startTime && (end || Date.now() / 1000) <= newEndTime;

      if (activeTab === CARD && instrument[groupBy]) {
        if (groupBy === 'network') {
          return tag.code === CARD_NETWORKS[instrument[groupBy]] && isInbetweenTime;
        } else if (groupBy === 'issuer') {
          return tag.code === instrument[groupBy] && isInbetweenTime;
        }
      }

      if ([NETBANKING, EMANDATE].includes(activeTab) && instrument[groupBy]) {
        return tag.code === instrument[groupBy] && isInbetweenTime;
      }

      return false;
    })
    .map((interval) => {
      const { begin, end, severity, status } = interval;

      const from = +moment.unix(begin).format('x');
      const to = +moment.unix(end).format('x');

      return { x: from, y: 0, from, to, severity, status };
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
      : data?.groups[_group_by]?.find(({ name }) => name === tag)?.intervals) ?? []
  );
};

export const getTagLabel = (name) => {
  const defaultLabel = getUser()?.isOptimizerEnabled ? upperFirst(name) : name;
  return (TAG_MAP[name] ?? defaultLabel) || '--';
};

export const getTagLabelWithOverallTag = ({ tag, activeTab, groupBy }) => {
  let name = getTagLabel(tag);
  if ((!getUser()?.isOptimizerEnabled || activeTab === 'Overall') && tag === 'Overall') {
    name = TAG_OVERALL_MAP[groupBy];
  }
  return name;
};

export const onFetchSR = ({
  data,
  startTime,
  endTime,
  breakdown,
  group_by = '',
  activeTab,
  selectedTags: initialSelectedTags,
  resolvedDowntimes = [],
  ongoingDowntimes = [],
}) => {
  try {
    const { groups, intervals = [], total } = data;
    if (!total) return fetchDefaultReturn;

    const tags =
      groups?.[group_by]?.reduce(
        (acc, { name, code, sr } = {}, idx) => {
          if (!name || (name === 'others' && !sr)) return acc;
          acc.push({ name, code, ...(tagStyles[idx + 1] ?? defaultTagStyle) });
          return acc;
        },
        [{ name: 'Overall', ...tagStyles[0] }],
      ) ?? [];

    const options = {
      intervals,
      startTime,
      endTime,
      breakdown,
    };

    const selectedTags = initialSelectedTags || tags;
    const labels = getTimelineData(options);
    const filterOngoingDowntimes = ongoingDowntimes.filter(
      ({ method }) => method === activeTab.toLowerCase(),
    );

    const datasets = selectedTags?.reduce((acc, tag) => {
      const intervals = getIntervals({ tag: tag.name, data, group_by, activeTab });
      const selectedTagIndex = tags?.findIndex(({ name }) => name === tag.name);

      acc.push(
        {
          label: getTagLabelWithOverallTag({ tag: tag.name, activeTab, groupBy: group_by }),
          data: generateDatasets(intervals),
          ...chartStyle[selectedTagIndex],
          xAxisID: SR_X,
          yAxisID: SR_Y,
          tagName: tag.name,
        },
        {
          label: getTagLabelWithOverallTag({ tag: tag.name, activeTab, groupBy: group_by }),
          data: generateDowntimeDataSets({
            intervals: [...resolvedDowntimes, ...filterOngoingDowntimes],
            tag,
            activeTab,
            startTime,
            endTime,
            groupBy: group_by,
          }),
          ...chartStyle[selectedTagIndex],
          xAxisID: DOWNTIME_X,
          yAxisID: DOWNTIME_Y,
          tagName: tag.name,
          type: 'scatter',
        },
      );

      return acc;
    }, []);

    return {
      tags,
      selectedTags,
      histogram: { labels, datasets },
    };
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
  const groups = data.groups?.[group_by] || [];

  tabsOrder.forEach(({ tab: tabName }, tabIdx) => {
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

export const getPieChartData = (groupData = [], tags) => {
  const { backgroundColor, borderColor } = defaultTagStyle;
  const totalSum = groupData.reduce((total, value) => total + (value?.total ?? 0), 0);
  const rearrangedData = reArrange({ arr: groupData, sortKey: 'total' });

  const result = rearrangedData.reduce(
    (accumulator, datapoint) => {
      // when total attempts is '0' there is no need to show on Pie Chart
      if (!datapoint?.total) return accumulator;
      const tagStyle = tags.find((tag) => tag.name === datapoint?.name);
      const _backgroundColor = tagStyle?.backgroundColor ?? backgroundColor;
      const _borderColor = tagStyle?.color ?? borderColor;
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
  const { isOptimizerEnabled } = session?.user;
  const { tabs, activeTab, filters, merchantErrors } = successRate;
  const { method, selectedDropdownFilterOptions, selectedCardType } = tabs[activeTab];
  const { startDate, endDate } = filters;
  const errorType = merchantErrors[activeTab].failureReasonType ?? 'default';

  let filterMethods = method.map(({ method }) => method);

  if (isOptimizerEnabled && activeTab === 'Overall') {
    filterMethods = method.reduce((methods, { optimizerEnabled, method }) => {
      if (optimizerEnabled) {
        methods.push(method);
      }
      return methods;
    }, []);
  }

  const payload = {
    entity: 'payments',
    from: startDate.unix(),
    to: endDate.unix(),
    mode: 'razorpay',
    filters: {
      method: filterMethods,
      ...(updateDropdownOptions
        ? getSelectedFilters({
            selectedDropdownFilterOptions,
            isOptimizerEnabled,
          })
        : {}),
      ...(!isOptimizerEnabled && activeTab === 'Card' ? { type: [selectedCardType] } : {}),
      ...(errorType !== 'default' ? CUSTOM_ERROR_TYPES[activeTab]?.fetchOptions?.filters : {}),
    },
    group_by: {
      ...(errorType !== 'default' ? CUSTOM_ERROR_TYPES[activeTab]?.fetchOptions?.groupBy : {}),
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
      acc.push(
        uniqBy(
          [
            ...(DEFAULT_OPTIMIZER_FILTERS?.[key] || []),
            ...(STATIC_OPTIMIZER_FILTERS?.[key] || []),
            ...values?.map((filterDetails) => {
              const { code, name } = filterDetails || {};
              return {
                value: code || name,
                text: FILTERS_VS_DISPLAY_NAMES?.[name] || upperFirst(name),
                query: key,
              };
            }),
          ],
          'value',
        ),
      );
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

// function to rearrange an array in min - max form

export function reArrange({ arr = [], sortKey = '' }) {
  if (!Array.isArray(arr)) return arr;
  const len = arr.length;
  const clone = [...arr];
  // temp array to hold modified array
  const res = new Array(len);

  // sort source array
  clone.sort((a, b) => {
    if (isObject(a) && isObject(b) && sortKey !== '') return a[sortKey] - b[sortKey];
    return a - b;
  });

  // two pointers: indexes of smallest and largest elements from given array.
  let small = 0;
  let large = len - 1;

  // if the index's are same
  if (small === large) return clone;

  // Store result in res[]
  for (let i = 0; i < len; i++) {
    if (i % 2 === 0) res[i] = clone[large--];
    else res[i] = clone[small++];
  }

  return res;
}

export const getTabsPane = (metrics = {}) => {
  let tabs = Object.values(metrics);
  if (!tabs.length) return [];
  const user = getUser();
  const isOptimizerEnabled = user?.isOptimizerEnabled;
  tabs = Object.values(metrics).filter(({ optimizerEnabled }) => {
    if (isOptimizerEnabled) {
      return optimizerEnabled;
    }
    return true;
  });
  return tabs;
};

export const validateDateRange = (dateRange) => {
  const { startDate, endDate } = dateRange;
  const errors = {};

  if (!startDate) {
    errors.date = 'Start date is required';
  } else if (!endDate) {
    errors.date = 'End date is required';
  } else if (startDate.valueOf() > endDate.valueOf()) {
    errors.date = 'Start date cannot be greater than end date';
  } else if (endDate.valueOf() > moment().endOf('hour').valueOf()) {
    errors.date = 'End time cannot be greater than current time';
  } else {
    const duration = moment.duration(endDate.diff(startDate));
    const hours = duration.asHours();

    if (hours < 6) {
      errors.date = 'Please select a minimum range of 6 hours';
    }
  }

  return errors;
};

/**
 * @param timestamp 1667823125000 in milliseconds
 * @returns '1s/m/h/d'
 */

export function timestampHumanize(timestamp) {
  if (!timestamp) return null;

  moment.updateLocale('en', {
    relativeTime: {
      future: 'in %s',
      past: '%s',
      s: (number) => `${number}s`,
      ss: '%ds',
      m: '1m',
      mm: '%dm',
      h: '1h',
      hh: '%dh',
      d: '1d',
      dd: '%dd',
      M: 'a month',
      MM: '%d',
      y: 'a year',
      yy: '%d',
    },
  });

  const diff = moment().diff(timestamp, 'seconds'); // 'diff' in seconds
  const dayStart = moment().startOf('day').seconds(diff);

  if (diff > 300) {
    return moment(timestamp).fromNow();
  } else if (diff < 60) {
    return `${dayStart.format('s')}s`;
  } else {
    return `${dayStart.format('m')}m`;
  }
}

/**
 * @param from 1667304882000 in milliseconds
 * @param to 1667823282000 in milliseconds
 * @returns '01 Nov 2022' if it is same day
 * @returns '01 - 07 Nov 2022' if it is same month
 * @returns '01 Oct 2022 - 30 Nov 2022' if it is lies on different months
 */

export const formatIntervals = ({ from, to }) => {
  const isSameDay = moment(from).isSame(to, 'day');
  const isSameMonth = moment(from).isSame(to, 'month');
  if (isSameDay) {
    return `${moment(from).format('DD MMM YYYY')}`;
  }
  if (isSameMonth) {
    return `${moment(from).format('DD')} - ${moment(to).format('DD MMM YYYY')}`;
  }
  return `${moment(from).format('DD MMM YYYY')} - ${moment(to).format('DD MMM YYYY')}`;
};

/**
 * @param time 1667304882000 in milliseconds
 * @returns '10:32 pm'
 */

export const formatTime = (time) => {
  return moment(time).format('hh:mm a');
};

const areFiltersSelected = (selectedDropdownFilterOptions) =>
  selectedDropdownFilterOptions?.some(
    (option) =>
      !DEFAULT_OPTIMIZER_FILTERS?.[option?.query]
        ?.map((item) => item?.value)
        ?.includes(option?.value),
  );

export const getNoDataTitle = (tab) => {
  const { name = '', selectedDropdownFilterOptions } = tab;
  return `No payments were made via${
    areFiltersSelected(selectedDropdownFilterOptions) ? ` selected filters for` : ''
  } ${PAYMENT_METHOD_VS_CALLOUT_DISPLAY_TEXT[name] || name} in the selected date range.`;
};

export const getNoDataSubTitle = (tab) => {
  const { name = '', selectedDropdownFilterOptions } = tab;
  return `Tip: You could try again by selecting a different${
    areFiltersSelected(selectedDropdownFilterOptions) ? ' filter,' : ''
  }${name !== 'Overall' ? ' payment method or' : ''} date range`;
};

export const reportSR = (datasets = []) => {
  if (Boolean(!datasets.length)) return datasets;

  const hashMap = {};

  datasets?.forEach((dataset) => {
    dataset?.data?.forEach((interval) => {
      if (hashMap.hasOwnProperty(interval?.x)) {
        hashMap[interval?.x][dataset?.label] = interval?.y;
      } else {
        hashMap[interval?.x] = {
          '':
            `${formatIntervals(interval?.from, interval?.to)} | ${moment(interval?.from).format(
              'hh:mm a',
            )} - ${moment(interval?.to).format('hh:mm a')}` ?? '',
          [dataset?.label]: interval?.y,
        };
      }
    });
  });

  return Object.values(hashMap);
};

export const checkIfFilterValid = ({ activeTab, filter, flags = {} }) => {
  if (activeTab === 'Card' && filter === 'international') {
    return flags.isInternationalEnabled;
  }
  return true;
};
