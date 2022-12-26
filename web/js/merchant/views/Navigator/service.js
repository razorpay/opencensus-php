import { merchantFetch } from 'merchant/utils/ajax';

export function editProvider({ payload }) {
  return merchantFetch({
    url: 'terminals/proxy/optimizer/mid/provider',
    method: 'put',
    data: payload,
  });
}

export function addProvider({ payload }) {
  return merchantFetch({
    url: 'terminals/proxy/optimizer/mid/provider',
    method: 'post',
    data: payload,
  });
}
