import { merchantFetch } from 'merchant/utils/ajax';
import { getKeysSeparatedByPipe } from 'common/utils/rzp-utils';

import { trackFormSubmit } from './ga';

/*
*
* Specific Api Actions of Payment Links
*
* */

export function createPaymentLink(payload) {
  const reqPayload = { ...payload };
  reqPayload.type = 'link';

  reqPayload.amount *= 100;
  reqPayload.expire_by &&
    (reqPayload.expire_by = Math.floor(reqPayload.expire_by / 1000));

  if (reqPayload.description) {
    // It is required field. Safe check.
    reqPayload.description = reqPayload.description.trim();
  }

  if (
    reqPayload.first_payment_min_amount &&
    Number(reqPayload.first_payment_min_amount) !== 0
  ) {
    reqPayload.first_payment_min_amount *= 100;
  } else {
    delete reqPayload.first_payment_min_amount;
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

  if (reqPayload.customer_name) {
    customer.name = reqPayload.customer_name;
  }

  delete reqPayload.customer_name;

  if (Object.keys(customer).length) {
    reqPayload.customer = customer;
  } else {
    delete reqPayload.reminder_enable;
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

  delete reqPayload.currency;

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
