import moment from 'moment';

export const parseTimeoutValues = (capture_options, key) => {
  const days = Math.floor(moment.duration(parseInt(capture_options[key], 10), 'minutes').asDays());
  const daysInMinutes = moment.duration(parseInt(days, 10), 'days').asMinutes();
  const remaining = capture_options[key] - daysInMinutes;
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

export const renderTimeoutAsString = (timeObject) => {
  const _strings = Object.keys(timeObject);
  const _obj = timeObject;

  if (_strings.length === 0) return '';
  else {
    let str = '';

    if (_obj.days) {
      str = `${_obj.days}`;
      if (parseInt(_obj.days, 10) === 1) str = `${str} day`;
      else str = `${str} days`;
    }

    if (_obj.hrs) {
      const suffix = _obj.hrs == 1 ? 'Hr' : 'Hrs';
      str = `${str} ${_obj.hrs} ${suffix}`;
    }

    if (_obj.mins) {
      const suffix = _obj.mins == 1 ? 'Min' : 'Mins';
      str = `${str} ${_obj.mins} ${suffix}`;
    }

    return str;
  }
};

export const capitalize = (s) => {
  if (typeof s !== 'string') return '';
  return s.charAt(0).toUpperCase() + s.slice(1);
};
