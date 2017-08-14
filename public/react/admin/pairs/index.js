import { titleCase } from 'rzp/utils/rzp-utils';

export const name = {
  title: 'Name',
  value: item => item.name,
};

export const email = {
  title: 'Email',
  value: item => item.email,
};

export const role = {
  title: 'Name',
  value: item => titleCase(item.role),
};
