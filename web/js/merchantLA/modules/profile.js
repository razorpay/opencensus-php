import ajax, { merchantFetch } from 'merchantLA/utils/ajax';
import { set } from 'rzp/utils/immutable';

export const updatePassword = data => {
  return ajax({
    url: '/password',
    method: 'post',
    data: data,
    appendModeInQueryParam: true,
  });
};

export const fetchBankAccount = () => {
  return merchantFetch({
    url: 'account/bank_account',
    mode: 'live',
  });
};
