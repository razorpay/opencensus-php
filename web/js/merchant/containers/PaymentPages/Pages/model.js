import { merchantFetch } from 'merchant/utils/ajax';

function pruneReqPayload(reqPayload) {
  if (reqPayload.amount) {
    reqPayload.amount *= 100;
  }

  reqPayload.expire_by &&
    (reqPayload.expire_by = Math.floor(reqPayload.expire_by / 1000));

  if (reqPayload.title) {
    // It is required field. Safe check.
    reqPayload.title = reqPayload.title.trim();
  }
}

export function createPaymentPage(data) {
  const reqPayload = { ...data };

  pruneReqPayload(reqPayload);

  return merchantFetch({
    url: 'payment_links',
    method: 'post',
    data: reqPayload,
    headers: {
      'content-Type': 'application/json',
    },
  });
}

export function editPaymentPageItem(id, data) {
  const reqPayload = { ...data };

  return merchantFetch({
    url: `payment_links/payment_page_item/${id}`,
    method: 'patch',
    data: reqPayload,
    headers: {
      'content-type': 'application/json',
    },
  });
}

export function editPaymentPage(id, data) {
  const reqPayload = { ...data };

  // In paymentpages v2, following 4 fields can also be edited via this API.
  pruneReqPayload(reqPayload);

  delete reqPayload.currency;

  return merchantFetch({
    url: `payment_links/${id}`,
    method: 'patch',
    data: reqPayload,
    headers: {
      'content-type': 'application/json',
    },
  });
}

export function uploadImageInDescription(file) {
  const fd = new FormData();
  fd.append('images[0]', file);

  return merchantFetch({
    url: `payment_links/images`,
    method: 'post',
    data: fd,
  });
}

export function fetchPaymentPageEntity(id) {
  return merchantFetch({
    url: `payment_links/${id}/details`,
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
    headers: {
      'content-type': 'application/json',
    },
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
