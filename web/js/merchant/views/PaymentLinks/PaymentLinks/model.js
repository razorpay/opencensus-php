import { merchantFetch } from 'merchant/utils/ajax';
import {
  getKeysSeparatedByPipe,
  i18CurrencyConversionFromCommonUnitToMinorUnit,
} from 'common/utils/rzp-utils';

import { transformCreatePLPayload_OldToNew, transformPLDetails_NewToOld } from './js/transformer';
import { trackFormSubmit } from './ga';

import store from 'merchant/store';
import { showPayerNamePL } from 'merchant/views/PaymentLinks/utils';

/*
 *
 * Specific Api Actions of Payment Links
 *
 * */

export function createPaymentLink(payload) {
  let reqPayload = { ...payload };
  reqPayload.type = 'link';

  reqPayload.amount = Math.round(
    i18CurrencyConversionFromCommonUnitToMinorUnit(reqPayload.amount, reqPayload.currency),
  );

  reqPayload.expire_by && (reqPayload.expire_by = Math.floor(reqPayload.expire_by / 1000));

  if (reqPayload.description) {
    // It is required field. Safe check.
    reqPayload.description = reqPayload.description.trim();
  }

  if (reqPayload.first_payment_min_amount && Number(reqPayload.first_payment_min_amount) !== 0) {
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

  const user = store.getState().session.user;

  let url;

  if (user.isPaymentlinksV2Enabled) {
    url = 'payment_links';
    if (!reqPayload.currency) {
      reqPayload.currency = 'INR';
    }
  } else {
    url = 'invoices';
  }

  // Transform payload to new format

  reqPayload = user.isPaymentlinksV2Enabled
    ? transformCreatePLPayload_OldToNew(reqPayload)
    : reqPayload;

  const reqPayloadToTrack = {
    ...reqPayload,
    notes: reqPayload.notes && Object.keys(reqPayload.notes).length,
    version: 'Payment Links V2',
  };

  trackFormSubmit(getKeysSeparatedByPipe(reqPayloadToTrack));

  return merchantFetch({
    url,
    // mode: this.props.mode,
    method: 'post',
    data: reqPayload,
    headers: {
      'content-type': 'application/json',
    },
  }).then((resp) => {
    // Transform payload to new format as per

    const _resp = user.isPaymentlinksV2Enabled
      ? { ...resp, data: transformPLDetails_NewToOld(resp.data) }
      : resp;

    return _resp;
  });
}

export function editPaymentLink(id, payload) {
  let reqPayload = { ...payload };

  delete reqPayload.currency;

  reqPayload.expire_by && (reqPayload.expire_by = Math.floor(reqPayload.expire_by / 1000));

  const user = store.getState().session.user;

  // Transform payload to new format
  if (user.isPaymentlinksV2Enabled) {
    reqPayload = transformCreatePLPayload_OldToNew(reqPayload);
  }

  const url = user.isPaymentlinksV2Enabled ? `payment_links/${id}` : `invoices/${id}`;

  return merchantFetch({
    url,
    method: 'patch',
    data: reqPayload,
    headers: {
      'content-type': 'application/json',
    },
  }).then((resp) => {
    // Transform payload to new format as per

    if (resp.data && user.isPaymentlinksV2Enabled) {
      const _resp = { ...resp, data: transformPLDetails_NewToOld(resp.data) };

      return _resp;
    }

    return resp;
  });
}

export function createPaymentLinkV2(payload) {
  const reqPayload = { ...payload };

  // Payment For
  if (reqPayload.description) {
    reqPayload.description = reqPayload.description.trim();
  }

  // Customer details
  const customer = {};
  if (reqPayload.contact) {
    customer.contact = reqPayload.contact;
  }

  delete reqPayload.contact;

  if (reqPayload.email) {
    customer.email = reqPayload.email;
  }

  delete reqPayload.email;
  if (showPayerNamePL()) {
    customer.name = reqPayload?.name ?? '';
    delete reqPayload.name;
  }

  if (Object.keys(customer).length) {
    reqPayload.customer = customer;
  } else {
    delete reqPayload.reminder_enable;
  }

  // Notify
  const notify = {};
  if (reqPayload.email_notify) {
    notify.email = reqPayload.email_notify === '1';
  }

  delete reqPayload.email_notify;

  if (reqPayload.sms_notify) {
    notify.sms = reqPayload.sms_notify === '1';
  }

  delete reqPayload.sms_notify;

  if (reqPayload.whatsapp_notify) {
    notify.whatsapp = reqPayload.whatsapp_notify === '1';
  }

  delete reqPayload.whatsapp_notify;

  if (Object.keys(notify).length) {
    reqPayload.notify = notify;
  }

  // Link Expire by
  if (reqPayload.expire_by) {
    reqPayload.expire_by = Math.floor(reqPayload.expire_by / 1000);
  } else {
    delete reqPayload.expire_by;
  }

  // Reminders
  if (reqPayload.hasOwnProperty('reminder_enable')) {
    reqPayload.reminder_enable = reqPayload.reminder_enable === '1';
  }

  // Partial Payments
  if (reqPayload.hasOwnProperty('accept_partial')) {
    reqPayload.accept_partial = reqPayload.accept_partial === '1';
  }

  if (reqPayload.hasOwnProperty('first_min_partial_amount')) {
    reqPayload.first_min_partial_amount = Math.round(
      i18CurrencyConversionFromCommonUnitToMinorUnit(
        reqPayload.first_min_partial_amount,
        reqPayload.currency,
      ),
    );
  }

  return merchantFetch({
    url: 'payment_links',
    method: 'post',
    data: reqPayload,
    headers: {
      'content-type': 'application/json',
    },
  }).then((resp) => {
    return {
      ...resp,
      data: transformPLDetails_NewToOld(resp.data),
    };
  });
}
