import moment, { isMoment } from 'moment';
import { WEEK_DAYS } from 'merchant_common/views/Reports/components/DateTimeRangePicker/constants';

export const renderNumberArray = (length, offset = 0) => {
  return Array(length)
    .fill(1)
    .map((_, i) => i + offset);
};

export const renderNumberArrayFromRange = (start: number, end: number, reverse?: boolean) => {
  const arr = Array(end - start + 1)
    .fill('1')
    .map((_, i) => start + i);
  return reverse ? arr.sort((a, b) => b - a) : arr;
};

export const getDayGridData = (
  month = moment().add(4, 'month'),
  enableOutsideDays = false,
  firstDayOfWeek = moment.localeData().firstDayOfWeek(),
) => {
  if (!moment.isMoment(month) || !month.isValid()) {
    throw new TypeError('`month` must be a valid moment object');
  }
  if (WEEK_DAYS.indexOf(firstDayOfWeek) === -1) {
    throw new TypeError('`firstDayOfWeek` must be an integer between 0 and 6');
  }

  // set utc offset to get correct dates in future (when timezone changes)
  const firstOfMonth = month.clone().startOf('month').hour(12);
  const lastOfMonth = month.clone().endOf('month').hour(12);

  // calculate the exact first and last days to fill the entire matrix
  // (considering days outside month)
  const prevDays = (firstOfMonth.day() + 7 - firstDayOfWeek) % 7;
  const nextDays = (firstDayOfWeek + 6 - lastOfMonth.day()) % 7;
  const firstDay = firstOfMonth.clone().subtract(prevDays, 'day');
  const lastDay = lastOfMonth.clone().add(nextDays, 'day');

  const totalDays = lastDay.diff(firstDay, 'days') + 1;

  const currentDay = firstDay.clone();
  const weeksInMonth: any = [];

  for (let i = 0; i < totalDays; i += 1) {
    if (i % 7 === 0) {
      weeksInMonth.push([]);
    }

    let day: any = null;
    if ((i >= prevDays && i < totalDays - nextDays) || enableOutsideDays) {
      day = currentDay.clone();
    }

    weeksInMonth[weeksInMonth.length - 1].push(day);

    currentDay.add(1, 'day');
  }

  return weeksInMonth;
};

export const getYearsArray = (visibleWindowIndex = 0, numOfVisibleYear) => {
  const thisYear = moment().get('year');
  const offset = (numOfVisibleYear - 1) * visibleWindowIndex;
  return renderNumberArrayFromRange(thisYear - (numOfVisibleYear - 1) + offset, thisYear + offset);
};

export const didDatesUpdate = (prev, curr) => {
  if (prev?.startDate && prev?.endDate) {
    if (
      isMoment(prev.startDate) &&
      isMoment(prev.endDate) &&
      isMoment(curr.endDate) &&
      isMoment(curr.startDate)
    ) {
      return !(
        prev.startDate.clone().isSame(curr.startDate.clone(), 'minute') &&
        prev.endDate.clone().isSame(curr.endDate.clone(), 'minute')
      );
    }
  }
  return false;
};
