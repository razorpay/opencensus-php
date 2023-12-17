/* Convert old data -> new data */
export function transformCreatePLPayload_OldToNew(data) {
  const reqPayload = JSON.parse(JSON.stringify(data));

  // 1.
  if (reqPayload.hasOwnProperty('type')) {
    delete reqPayload.type; // type not required in new api
  }

  // 2.
  if (reqPayload.hasOwnProperty('partial_payment')) {
    reqPayload.accept_partial =
      reqPayload.partial_payment === true || reqPayload.partial_payment == '1';
    delete reqPayload.partial_payment;
  }

  // 3.
  if (reqPayload.hasOwnProperty('first_payment_min_amount')) {
    reqPayload.first_min_partial_amount = reqPayload.first_payment_min_amount;

    delete reqPayload.first_payment_min_amount;
  }

  // 4.
  if (reqPayload.hasOwnProperty('receipt')) {
    reqPayload.reference_id = reqPayload.receipt;
    delete reqPayload.receipt;
  }

  // 5.
  if (reqPayload.email_notify === '1' || reqPayload.sms_notify === '1') {
    reqPayload.notify = {};

    if (reqPayload.hasOwnProperty('email_notify')) {
      reqPayload.notify.email = reqPayload.email_notify === '1';
    }

    if (reqPayload.hasOwnProperty('sms_notify')) {
      reqPayload.notify.sms = reqPayload.sms_notify === '1';
    }
  }

  // 6.
  if (reqPayload.hasOwnProperty('sms_notify')) {
    delete reqPayload.sms_notify;
  }

  if (reqPayload.hasOwnProperty('email_notify')) {
    delete reqPayload.email_notify;
  }

  return reqPayload;
}

/* Convert new data -> old data */
export function transformPLDetails_NewToOld(data) {
  const resPayload = JSON.parse(JSON.stringify(data));

  // 1.
  resPayload.type = 'link';

  // 2.
  if (resPayload.customer && Object.keys(resPayload.customer).length) {
    resPayload.customer_details = {
      id: resPayload.customer_id,
      customer_contact: resPayload.customer.contact,
      customer_email: resPayload.customer.email,
      customer_name: resPayload.customer.name,
      ...resPayload.customer,
    };
  } else {
    resPayload.customer_details = {};
  }

  delete resPayload.customer;

  // 3.
  const { notify: { email, sms, whatsapp } = {} } = data;

  resPayload.email_notify = email ? '1' : '0';
  resPayload.sms_notify = sms ? '1' : '0';
  resPayload.whatsapp_notify = whatsapp ? '1' : '0';

  delete resPayload.notify;

  // 4.
  resPayload.notes = resPayload.notes || [];

  // 5.
  resPayload.receipt = resPayload.reference_id;

  // 6.
  resPayload.partial_payment = resPayload.accept_partial;

  // 7.
  resPayload.first_payment_min_amount = resPayload.first_min_partial_amount;

  // 8.
  resPayload.reminder_status = resPayload.reminders ? resPayload.reminders.status : null;

  // Other keys are not needed to be changed and extra keys need not to be deleted, as they won't beused further

  return resPayload;
}

export function transformPLListFilters_NewToOld(_params) {
  const params = JSON.parse(JSON.stringify(_params));

  // 1.
  if (params.hasOwnProperty('types')) {
    delete params.types;
  }

  // 2.
  if (params.hasOwnProperty('receipt')) {
    params.reference_id = params.receipt;
    delete params.receipt;
  }

  // 3.
  if (params.hasOwnProperty('customer_email')) {
    params.email = params.customer_email;
    delete params.customer_email;
  }

  // 4.
  if (params.hasOwnProperty('customer_contact')) {
    params.contact = params.customer_contact;
    delete params.customer_contact;
  }

  // 5.
  if (params.hasOwnProperty('batch_id')) {
    params.source_id = params.batch_id;
    delete params.batch_id;
  }

  return params;
}
