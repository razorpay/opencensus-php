import { merchantFetch } from 'rzp/utils/ajax';

export function createPaymentPage(reqPayload) {
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

export function editPaymentPage(id, data) {
  return merchantFetch({
    url: `payment_links/${id}`,
    method: 'patch',
    data: data,
    headers: {
      'content-type': 'application/json',
    },
  });
}

export function fetchPaymentPageEntity(id) {
  return merchantFetch({
    url: `payment_links/${id}`,
    params: {
      expand: ['user'],
    },
  });
}

export function fetchPaymentPagesList(data) {
  return merchantFetch({
    url: 'payment_links',
    data,
  });
}

export function fetchPaymentsListForPaymentPage(id) {
  return merchantFetch({
    url: 'payments',
    params: {
      payment_link_id: id,
      captured: 1,
      count: 5,
    },
  });
}

export function deactivatePaymentPage(id) {
  return merchantFetch({
    url: `payment_links/${id}/deactivate`,
    method: 'patch',
  });
}

export function activatePaymentPage(id, data) {
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
