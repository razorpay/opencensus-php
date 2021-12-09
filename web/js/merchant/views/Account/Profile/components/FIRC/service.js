import { merchantFetch } from 'merchant/utils/ajax';
import { downloadFile } from './utility';

export const fetchFircFiles = (month, year) => {
  return merchantFetch({
    url: 'merchant/firs',
    method: 'get',
    data: {
      month,
      year,
    },
  });
};

export const fetchFircFileUrl = (data) => {
  return merchantFetch({
    url: 'merchant/firs/content',
    method: 'get',
    data,
  });
};

export const downloadFiles = (obj) => {
  fetchFircFileUrl(obj)
    .then((response) => {
      downloadFile(response);
      return true;
    })
    .catch((err) => {
      throw new Error(err);
    });
};
