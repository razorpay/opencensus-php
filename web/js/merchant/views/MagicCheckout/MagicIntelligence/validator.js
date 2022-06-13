import { VALIDATE, ERRORS } from 'merchant/views/MagicCheckout/MagicIntelligence/constants';

export const validate = (type, value) => {
  let errMsg = '';
  let rejectedItems = [];

  if (type === '') {
    errMsg = ERRORS.TYPE;
  } else if (value === '') {
    errMsg = ERRORS.VALUE;
  } else if (value.charAt(value.length - 1) === ',') {
    errMsg = ERRORS.COMMA;
  } else {
    const items = value.split(',');

    if (items.length > 20) {
      errMsg = ERRORS.LENGTH[type.toUpperCase()];
    } else {
      rejectedItems = items.filter(
        (item) => !VALIDATE[`${type.toUpperCase()}_REGEX`].test(item.trim()),
      );

      if (rejectedItems.length !== 0) {
        rejectedItems.map((item) => (errMsg += `${item}${rejectedItems.length !== 1 ? ',' : ''} `));
        errMsg += `${
          ERRORS[`${type.toUpperCase()}${rejectedItems.length !== 1 ? '_PLURAL' : ''}`]
        }`;
      }
    }
  }

  return errMsg;
};
