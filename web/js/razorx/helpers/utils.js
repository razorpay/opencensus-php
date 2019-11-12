import { snakeToTitleCase } from '../../common/util';

export const deepClone = o => {
  try {
    return JSON.parse(JSON.stringify(o));
  } catch (err) {
    console.log('Deepclone error: ', err);
  }
};

/**
 * gets date in format 21st Dec, 2017 05:00
 * @param  {String/Number} value in date string or seconds
 * @return {String}               date in 21st Dec, 2017 05:00 format
 */
export const formatDate = value => {
  if (!value) {
    return null;
  }

  let date;

  /*
   * This is anomaly for shield service, which stored time in format = 2018-06-15T11:04:45Z.
   * It has to get fixed sometime. For now, it's exception.
   * */
  if (typeof value === 'string') {
    date = new moment(value);
  } else {
    date = moment.unix(value);
  }

  return date.format('Do MMM, YYYY hh:mm A');
};

/**
 *
 * @param {Object}, keys: Objet keys to be converted into sentence.
 * Returns comma separated sentence ending in is/are.
 */
export function keysToSentence(keys) {
  if (typeof keys !== 'object' || !Object.keys(keys).length) {
    return;
  }

  let joiner;

  keys = Object.keys(keys).map(key => {
    if (key[key.length - 1] === 's') {
      // plural term
      joiner = 'are';
    }

    return snakeToTitleCase(key);
  });

  joiner = joiner || (keys.length > 1 ? 'are' : 'is');

  let sentence = keys[0];

  for (let i = 1; i < keys.length; i++) {
    if (i === keys.length - 1) {
      sentence = sentence + ' and ' + keys[i];
    } else {
      sentence = sentence + ', ' + keys[i];
    }
  }

  return sentence + ' ' + joiner;
}
