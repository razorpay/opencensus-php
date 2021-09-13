import { merchantFetch } from 'merchant/utils/ajax';

// fetch and update the modal condig details in BE.

export const fetchModalConfigDetails = (namespace) => {
  return merchantFetch({
    url: `merchants/config/store?namespace=${namespace}`,
    method: 'GET',
  })
    .then((data) => {
      return data;
    })
    .catch((err) => {
      return err;
    });
};

export const updateModalConfigDetails = (payload, namespace) => {
  return merchantFetch({
    url: 'merchants/config/store',
    method: 'POST',
    data: {
      namespace,
      ...payload,
    },
  }).catch((_err) => {});
};
