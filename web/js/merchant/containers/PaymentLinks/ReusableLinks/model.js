import { merchantFetch } from 'rzp/utils/ajax';

export function createReusableLink(data) {
  const reqPayload = {
    ...data,
    currency: 'INR', // TODO: Get is dynamically
  };

  reqPayload.amount *= 100;
  reqPayload.expire_by &&
    (reqPayload.expire_by = Math.floor(reqPayload.expire_by / 1000));

  return merchantFetch({
    url: 'payment_links',
    method: 'post',
    data: reqPayload,
  });
}

export const fetchReusableLinksEntity = id => {
  return merchantFetch({
    url: `payment_links/${id}`,
    params: {
      expand: ['user'],
    },
  });
};

export const fetchReusableLinksList = data =>
  merchantFetch({
    url: 'payment_links',
    data,
  });

export const fetchReusableLinkPaymentsList = id => {
  return merchantFetch({
    url: 'payments',
    params: {
      pl_id: id,
      count: 5,
    },
  });
};
