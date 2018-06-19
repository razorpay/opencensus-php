import { merchantFetch } from 'rzp/utils/ajax';

export function createReusableLink(reqPayload) {
  reqPayload.currency = 'INR'; // TODO: Get is dynamically

  reqPayload.amount *= 100;
  reqPayload.expire_by &&
    (reqPayload.expire_by = Math.floor(reqPayload.expire_by / 1000));

  return merchantFetch({
    url: 'payment_links',
    method: 'post',
    data: reqPayload,
  });
}

export function editReusableLink(id, data) {
  return merchantFetch({
    url: `payment_links/${id}`,
    method: 'patch',
    data: data,
    headers: {
      'content-type': 'application/json',
    },
  });
}

export function fetchReusableLinksEntity(id) {
  return merchantFetch({
    url: `payment_links/${id}`,
    params: {
      expand: ['user'],
    },
  });
}

export function fetchReusableLinksList(data) {
  return merchantFetch({
    url: 'payment_links',
    data,
  });
}

export function fetchReusableLinkPaymentsList(id) {
  return merchantFetch({
    url: 'payments',
    params: {
      payment_link_id: id,
      count: 5,
    },
  });
}

export function deactivateReusableLink(id) {
  return merchantFetch({
    url: `payment_links/${id}/deactivate`,
    method: 'patch',
  });
}

export function activateReusableLink(id, data) {
  return merchantFetch({
    url: `payment_links/${id}/activate`,
    method: 'patch',
    data,
  });
}

export function sendLink(id, data) {
  const reqPayload = {};

  data.email && (reqPayload.emails = [data.email]);
  data.contact && (reqPayload.contacts = [data.contact]);

  return merchantFetch({
    url: `payment_links/${id}/notify`,
    method: 'post',
    data: reqPayload,
  });
}
