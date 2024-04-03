/* eslint-disable no-undef */
import { renderTimeoutAsString } from 'merchant/views/Settings/Configuration/PaymentCaptureComponents/util';

export const maxTimeoutValue = 4320;
export const defaultTimeoutValue = 12;

export const TIMEOUT_VALUES = [
  { label: '12 Mins', name: defaultTimeoutValue },
  { label: '30 Mins', name: 30 },
  { label: '1 Hr', name: 60 },
  { label: '2 Hrs', name: 120 },
  { label: '6 Hrs', name: 360 },
  { label: '12 Hrs', name: 720 },
  { label: '1 Day', name: 1440 },
  { label: '2 Days', name: 2880 },
  { label: '3 Days', name: maxTimeoutValue },
];

export const parseTimeoutValues = (timeoutValue) => {
  const days = Math.floor(moment.duration(parseInt(timeoutValue, 10), 'minutes').asDays());
  const daysInMinutes = moment.duration(parseInt(days, 10), 'days').asMinutes();
  const remaining = timeoutValue - daysInMinutes;
  let hrs = moment.duration(parseInt(remaining, 10), 'minutes').asHours();
  const hrsInMinutes = moment.duration(parseInt(hrs, 10), 'hours').asMinutes();
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

export const doesContainTimeout = (inputArray, value) => {
  const output = inputArray.filter((item) => item.name === value);

  return output.length > 0;
};

export const getTimeoutOptions = (inputArray, timeoutValue) => {
  const containsTimeout = doesContainTimeout(inputArray, parseInt(timeoutValue, 10));

  if (containsTimeout) return inputArray;
  else {
    const label = renderTimeoutAsString(parseTimeoutValues(timeoutValue));
    return [{ label, name: `${timeoutValue}` }, ...inputArray];
  }
};

export const filterTimeoutBasedOnLimit = (inputArray, timeoutLimit) =>
  inputArray.filter((item) => item.name > parseInt(timeoutLimit, 10));
