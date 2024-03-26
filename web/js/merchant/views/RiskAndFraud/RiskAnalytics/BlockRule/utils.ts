import { isEmail } from 'common/utils/validators';

import { FormError, FormValues } from './types';

export const validateForm = (values: FormValues): FormError => {
  const fieldValues = ['parameters', 'email', 'file'];
  const errors: FormError = {};
  fieldValues.forEach((field) => {
    if (!values[field] || values[field] === '') {
      errors[field] = 'This field is required';
    }
  });
  if (values.email) {
    values.email.split(',').forEach((email) => {
      if (!isEmail(email)) {
        errors.email = 'Please enter a valid email Id';
      }
    });
  }
  return errors;
};
