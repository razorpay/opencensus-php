import moment from 'moment';

export const onWheelPreventChange = (e) => {
  // Prevent the input value change
  e.target.blur();

  // Prevent the page/container scrolling
  e.stopPropagation();

  // Refocus immediately, on the next tick (after the current function is done)
  setTimeout(() => {
    e.target.focus();
  }, 0);
};

/**
 * @param {Array} seconds total seconds
 * @returns {object} object of hours and mins to consume directly by caller method
 */

export const toHoursAndMinutes = (seconds) => {
  const duration = moment.duration(seconds, 'seconds');

  const hours = Math.floor(duration.asHours());
  const minutes = duration.minutes();

  return { hours, mins: minutes };
};

export const getHoursString = (h) => {
  return h == 1 ? `${h} hour` : `${h} hours`;
};

export const getMinsString = (m) => {
  return m == 1 ? `${m} minute` : `${m} minutes`;
};

/**
 * @param {Array} durationVal duration object having hours and minutes to convert into seconds
 * @returns {number} converted seconds of the provided duration
 */
export const getTimeInSeconds = (durationVal) => {
  const { hours: h, mins: m } = durationVal;

  const hours = parseInt(h, 10);
  const mins = parseInt(m, 10);

  const timeString = `${hours}:${mins}`;
  const timeFormat = 'H:m';

  const duration = moment.duration(moment(timeString, timeFormat).format('H:m')).asSeconds();

  return duration;
};
