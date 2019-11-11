import ajax from 'merchantLA/utils/ajax';
import { merge, set, unshift } from 'rzp/utils/immutable';

export const saveAccount = data => {
  return {
    type: ACCOUNT_CREATE,
    payload: ajax({
      url: '/submerchants',
      method: 'post',
      appendModeInQueryParam: true,
      data,
    }).then(response => response.data),
  };
};

export const exportAccountsCSV = () => {
  let data = { year: '2017', month: '1' };

  return () => {
    return ajax({
      url: '/reports/account',
      data,
    });
  };
};
