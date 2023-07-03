import { MinutesModifierFn } from 'merchant_common/views/Reports/components/TimePicker/types';

export const handleMinutesChange: MinutesModifierFn = (
  action,
  minutes,
  callback,
  minutesInterval?,
) => {
  const guardAt = minutesInterval ? 60 - minutesInterval : 59;
  const diffOffset = minutesInterval ? minutesInterval : 1;

  if (action === 'increase') callback(minutes === guardAt ? 0 : minutes + diffOffset);
  else if (action === 'decrease') callback(minutes === 0 ? guardAt : minutes - diffOffset);
};
