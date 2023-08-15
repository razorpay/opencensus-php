import {
  MeridiemType,
  MinutesModifierFn,
} from 'merchant_common/views/Reports/components/TimePicker/types';
import { MinutesInterval } from 'merchant_common/views/Reports/components/types';
import moment from 'moment';

export const handleMinutesChange: MinutesModifierFn = (
  action,
  minutes,
  callback,
  minutesInterval?,
) => {
  const guardAt = minutesInterval ? 60 - minutesInterval : 59;
  const diffOffset = minutesInterval ? minutesInterval : 1;
  const maxMin = 59;

  if (action === 'increase')
    callback(minutes === guardAt ? maxMin : minutes === maxMin ? 0 : minutes + diffOffset);
  else if (action === 'decrease')
    callback(minutes === 0 ? maxMin : minutes === maxMin ? guardAt : minutes - diffOffset);
};

export const getInitialTimeStates = (
  date: moment.Moment,
  minInterval?: MinutesInterval,
): [number, number, MeridiemType] => {
  // if minInterval is passed, will check if its passed date has minutes as multiple of minInterval.
  // if yes, returns it. Or resets it to 0.
  const getMin = () => {
    const tM = +date.clone().format('m');
    if (!minInterval || tM % minInterval === 0 || tM === 59) {
      return tM;
    } else {
      return 0;
    }
  };

  const [hour, minute, meridiem] = date
    .clone()
    .set({
      minute: getMin(),
    })
    .format('h:m:A')
    .split(':');

  return [+hour, +minute, meridiem as MeridiemType];
};
