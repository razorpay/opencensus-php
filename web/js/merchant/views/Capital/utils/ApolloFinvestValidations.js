const FRIDAY_CLOSE_TIME = moment('5:00pm', 'h:mma');
const MONDAY_START_TIME = moment('4:00am', 'h:mma');

function isWeekDay(time) {
  const day = time.day();
  return day !== 6 && day !== 0;
}

function validateFridayWorkingHours(time) {
  const day = time.day();
  const isFriday = day === 5;
  const isMonday = day === 1;

  if (isFriday) {
    return time.isSameOrBefore(FRIDAY_CLOSE_TIME);
  } else if (isMonday) {
    return time.isSameOrAfter(MONDAY_START_TIME);
  } else return isWeekDay(time);
}

export function isApolloFinvestServiceable(time) {
  return isWeekDay(time) && validateFridayWorkingHours(time);
}

export function getNextWithdrawableDate(time) {
  if (!isApolloFinvestServiceable(time)) {
    return `${moment()
      .startOf('isoWeek')
      .add(1, 'week')
      .startOf('day')
      .format('ll')} ${MONDAY_START_TIME.format('hA')}`;
  }
  // else case should never happen, as we don't show next
  // working day if the current time comes under working hours
}
