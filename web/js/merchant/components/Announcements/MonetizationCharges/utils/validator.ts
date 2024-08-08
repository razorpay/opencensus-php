import { isEmail, isPhone, isUrlLenient } from 'common/utils/validators';
import { FIELD_NAMES } from '../constants/fields';

type fieldNames = keyof typeof FIELD_NAMES;

const validate = (values: Record<fieldNames, string>): Record<string, string> => {
  const errors: Partial<Record<fieldNames, string>> = {};

  if (!values.name) {
    errors.name = 'Name is required';
  }

  if (!values.phone) {
    errors.phone = 'Phone Number is required';
  } else if (!isPhone(values.phone)) {
    errors.phone = 'Phone Number is invalid';
  }

  if (!values.email) {
    errors.email = 'Email is required';
  } else if (!isEmail(values.email)) {
    errors.email = 'Email is invalid';
  }

  if (values.website && !isUrlLenient(values.website)) {
    errors.website = 'Website link is invalid';
  }

  if (!values.company) {
    errors.company = 'Company Name is required';
  }

  if (!values.revenue) {
    errors.revenue = 'Revenue is required';
  }

  if (!values.employeeCount) {
    errors.employeeCount = 'No. of employees is required';
  }

  return errors;
};

export default validate;
