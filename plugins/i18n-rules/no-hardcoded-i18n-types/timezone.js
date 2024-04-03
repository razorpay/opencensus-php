const commonTimeZoneList = ['UST', 'GMT', 'IST'];
const isTimeZoneHardCoded = (_value) => {
  const value = _value.split(' ');
  // Currently, checking commonly used the timezones only.
  if (commonTimeZoneList.some((timeZone) => value.includes(timeZone))) {
    return `Avoid hardcoded 'time zones' as this feature may not be supported in all countries. If necessary, use a feature flag to enable it selectively. If this warning seems incorrect, feel free to ignore it.`;
  }

  return false;
};

module.exports = isTimeZoneHardCoded;
