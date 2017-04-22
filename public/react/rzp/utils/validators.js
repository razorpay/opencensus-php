import { isPresent } from './rzp-utils';

export const isEmail = email => {
  email = email || '';
  let emailRegExp = new RegExp(
    /^$|[a-zA-Z0-9.!#$%&’*+/=?^_`{|}~-]+@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)+$/
  );
  return emailRegExp.test(email);
};

export const isPhone = phone => {
  phone = phone || '';
  let phoneRegExp = new RegExp(/^$|\+?[0-9]{8,15}$/);
  return phoneRegExp.test(phone);
};

const makeValidator = (truthyFn, defaultMessage) => (
  message = defaultMessage
) => value => (truthyFn(value) ? undefined : message);

export const required = makeValidator(isPresent, 'Required');
export const email = makeValidator(isEmail, 'Invalid Email');
export const phone = makeValidator(isPhone, 'Invalid Contact');
