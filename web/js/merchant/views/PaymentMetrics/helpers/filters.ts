import moment from 'moment';
import {
  PRESETS,
  DEFAULT_PRESET,
  GRAPH_INTERVALS_MAP,
} from 'merchant/views/PaymentMetrics/constants';
import { Filter } from 'merchant/views/PaymentMetrics/types';

export const initialFilters = (): Filter => {
  /**
   * since, we'll not have latest  data,
   * we query data with endDate 5mins lesser than the current time.
   */
  const endDate = moment().endOf('hour') as moment.Moment;
  let startDate = endDate.clone() as moment.Moment;
  startDate = startDate.subtract(6, 'hours').startOf('hour');

  const payload = {
    startDate,
    endDate,
    preset: PRESETS[DEFAULT_PRESET],
  };

  return payload;
};
/**
 * get input as start and end date
 * returns interval
 */
export const getBreakdownInterval = (from: moment.Moment, to: moment.Moment): string => {
  const diff = to.diff(from, 'days');
  if (diff < 2) {
    return GRAPH_INTERVALS_MAP.hourly;
  } else if (diff >= 2 && diff <= 14) {
    return GRAPH_INTERVALS_MAP.daily;
  } else {
    return GRAPH_INTERVALS_MAP.weekly;
  }
};

/**
 * for custom date select verify startDate and endDate difference
 */
export const validateDateRange = (dateRange: Filter) => {
  const { startDate, endDate } = dateRange;
  const errors = {} as { date: string };

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

export const getTimeForSelectedGraphs = (time: string, interval: string) => {
  const returnData = {} as Filter;
  const clickedTime = moment(time);
  const now = moment();
  switch (interval) {
    case 'hourly': {
      const duration = moment.duration(now.diff(clickedTime));
      const hours = duration.asHours();
      if (hours < 24) {
        const endDate = moment().endOf('hour');
        let startDate = endDate.clone();
        startDate = startDate.subtract(24, 'hours').startOf('hour');
        return {
          startDate,
          endDate,
          preset: PRESETS[1],
        };
      } else {
        const startDate = moment(time).startOf('hour');
        const endDate = moment(time).add(1, 'days');
        return {
          startDate,
          endDate,
          preset: PRESETS[PRESETS.length - 1],
        };
      }
    }
    case 'daily': {
      const startDate = moment(time).startOf('day');
      const endDate = moment(time).endOf('day');
      return {
        startDate,
        endDate,
        preset: PRESETS[PRESETS.length - 1],
      };
    }
    case 'weekly': {
      const startDate = moment(time).startOf('day');
      const endDate = moment(time).add(7, 'days').endOf('day');
      return {
        startDate,
        endDate,
        preset: PRESETS[PRESETS.length - 1],
      };
    }
    default:
      return returnData;
  }
};
