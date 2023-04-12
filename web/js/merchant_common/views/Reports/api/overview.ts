import { merchantFetch } from 'merchant/utils/ajax';

export const getConfigs = (headers?) => {
  return merchantFetch({
    url: 'reporting/configs',
    headers,
  });
};

export const getRecentConfigs = (headers?) => {
  return merchantFetch({
    url: 'reporting/configs?limit=7&sort_by=logs.created_at',
    headers,
  });
};
