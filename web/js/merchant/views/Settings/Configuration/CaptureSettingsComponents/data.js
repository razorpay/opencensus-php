import { renderTimeoutAsString } from '../PaymentCaptureComponents/util';

export const TIMEOUT_VALUES = [
  { label: '12 Mins', name: 12 },
  { label: '30 Mins', name: 30 },
  { label: '60 Mins', name: 60 },
  { label: '2 Hrs', name: 120 },
  { label: '6 Hrs', name: 360 },
  { label: '12 Hrs', name: 720 },
  { label: '1 Day', name: 1440 },
  { label: '2 Days', name: 2880 },
  { label: '3 Days', name: 4320 },
  { label: '4 Days', name: 5760 },
  { label: '5 Days', name: 7200 },
];

export const parseTimeoutValues = (timeoutValue) => {
  const days = Math.floor(moment.duration(parseInt(timeoutValue), 'minutes').asDays());
  const daysInMinutes = moment.duration(parseInt(days), 'days').asMinutes();
  const remaining = timeoutValue - daysInMinutes;
  let hrs = moment.duration(parseInt(remaining), 'minutes').asHours();
  const hrsInMinutes = moment.duration(parseInt(hrs), 'hours').asMinutes();
  const mins = remaining - hrsInMinutes;
  hrs = Math.floor(hrs);

  if (hrs <= 9) {
    hrs = `0${hrs}`;
  }

  const timeObject = {
    days,
    hrs,
    mins,
  };

  if (hrs === '00') {
    delete timeObject.hrs;
  }

  return timeObject;
};

export function handleTimeoutValues(timeoutValue, captureManually = false) {
  let found = false;
  const timeout = parseInt(timeoutValue);
  let timeoutValues = [...TIMEOUT_VALUES];

  if (captureManually) {
    timeoutValues = TIMEOUT_VALUES.filter((item) => item.name > timeout);
  }

  timeoutValues.forEach((item) => {
    if (item.name === timeout) found = true;
  });

  if (found) return timeoutValues;
  else {
    const label = renderTimeoutAsString(parseTimeoutValues(timeout));
    const values = [{ label, name: `${timeout}` }, ...timeoutValues];
    return values;
  }
}
