import { merchantFetch } from 'rzp/utils/ajax';

/*
*
* Specific Api Actions of Payment Links
*
* */

export function createPaymentLink(reqPayload) {
  reqPayload.type = 'link';
  reqPayload.currency = 'INR'; // TODO: Get is dynamically

  reqPayload.amount *= 100;
  reqPayload.expire_by &&
    (reqPayload.expire_by = Math.floor(reqPayload.expire_by / 1000));

  /* Customer details */
  const customer = {};
  if (reqPayload.contact) {
    customer.contact = reqPayload.contact;
    delete reqPayload.contact;
  }

  if (reqPayload.email) {
    customer.email = reqPayload.email;
    delete reqPayload.email;
  }

  if (Object.keys(customer).length) {
    reqPayload.customer = customer;
  }

  return merchantFetch({
    url: 'invoices',
    // mode: this.props.mode,
    method: 'post',
    data: reqPayload,
  }).then(resp => {
    return resp;
  });
}

export function editPaymentLink(id, data) {
  return merchantFetch({
    url: `invoices/${id}`,
    method: 'patch',
    data: data,
    headers: {
      'content-type': 'application/json',
    },
  });
}
