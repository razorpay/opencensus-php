import moment from 'moment';

export const parseTimeoutValues = (capture_options, key) => {
  const days = Math.floor(moment.duration(parseInt(capture_options[key]), 'minutes').asDays());
  let daysInMinutes = moment.duration(parseInt(days), 'days').asMinutes();
  const remaining = capture_options[key] - daysInMinutes;
  let hrs = moment.duration(parseInt(remaining), 'minutes').asHours();
  let hrsInMinutes = moment.duration(parseInt(hrs), 'hours').asMinutes();
  const mins = remaining - hrsInMinutes;
  hrs = Math.floor(hrs);

  if (hrs <= 9) {
    hrs = `0${hrs}`;
  }

  const timeObject = {
    days: days,
    hrs: hrs,
    mins: mins,
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

    if (_obj['days']) {
      str = `${_obj['days']}`;
      parseInt(_obj['days']) === 1 ? (str = `${str} day`) : (str = `${str} days`);
    }

    if (_obj['hrs']) {
      let suffix = _obj['hrs'] == 1 ? 'Hr' : 'Hrs';
      str = `${str} ${_obj['hrs']} ${suffix}`;
    }

    if (_obj['mins']) {
      let suffix = _obj['mins'] == 1 ? 'Min' : 'Mins';
      str = `${str} ${_obj['mins']} ${suffix}`;
    }

    return str;
  }
};

export const capitalize = (s) => {
  if (typeof s !== 'string') return '';
  return s.charAt(0).toUpperCase() + s.slice(1);
};
