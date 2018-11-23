import { merchantFetch } from 'merchant/utils/ajax';
import { getKeysSeparatedByPipe } from 'rzp/utils/rzp-utils';

import { trackFormSubmit } from './ga';

/*
*
* Specific Api Actions of Payment Links
*
* */

export function createPaymentLink(payload) {
  const reqPayload = { ...payload };
  reqPayload.type = 'link';
  reqPayload.currency = 'INR'; // TODO: Get is dynamically

  reqPayload.amount *= 100;
  reqPayload.expire_by &&
    (reqPayload.expire_by = Math.floor(reqPayload.expire_by / 1000));

  if (reqPayload.description) {
    // It is required field. Safe check.
    reqPayload.description = reqPayload.description.trim();
  }

  if (!reqPayload.min_amount) {
    delete reqPayload.min_amount;
  }

  /* Customer details */
  const customer = {};
  if (reqPayload.contact) {
    customer.contact = reqPayload.contact;
  }

  delete reqPayload.contact;

  if (reqPayload.email) {
    customer.email = reqPayload.email;
  }

  delete reqPayload.email;

  if (Object.keys(customer).length) {
    reqPayload.customer = customer;
  }

  const reqPayloadToTrack = {
    ...reqPayload,
    notes: reqPayload.notes && Object.keys(reqPayload.notes).length,
    version: 'Payment Links V2',
  };
  trackFormSubmit(getKeysSeparatedByPipe(reqPayloadToTrack));

  return merchantFetch({
    url: 'invoices',
    // mode: this.props.mode,
    method: 'post',
    data: reqPayload,
  }).then(resp => {
    return resp;
  });
}

export function editPaymentLink(id, payload) {
  const reqPayload = { ...payload };

  reqPayload.expire_by &&
    (reqPayload.expire_by = Math.floor(reqPayload.expire_by / 1000));

  return merchantFetch({
    url: `invoices/${id}`,
    method: 'patch',
    data: reqPayload,
    headers: {
      'content-type': 'application/json',
    },
  });
}
