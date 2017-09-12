import GenericEntity from './GenericEntity';
import ajax from 'merchant/utils/ajax';
import { getFixedINRAmount, isBlank } from 'rzp/utils/rzp-utils';
import Payment from 'merchant/models/Payment';

const createFields = [
  'id',
  'amount',
  'currency',
  'partial_payment',
  'date',
  'expire_by',
  'draft',
  'customer_id',
  'customer',
  'sms_notify',
  'email_notify',
  'line_items',
  'type',
  'terms',
  'description',
  'receipt',
  'notes',
  'comment',
];

const editableFieldsInIssuedState = [
  'date',
  'expire_by',
  'terms',
  'notes',
  'receipt',
  'comment',
  'partial_payment',
];

export default class Invoice extends GenericEntity {
  listRouteName = 'invoice_fetch_multiple';
  detailsRouteName = 'invoice_fetch';
  deleteRouteName = 'invoice_delete';
  currency = 'INR';

  getRouteName() {
    return this.isNew ? 'invoice_create' : 'invoice_update';
  }

  resourceFields() {
    return this.status === 'issued'
      ? editableFieldsInIssuedState
      : createFields;
  }

  get isEditable() {
    return this.status !== 'paid';
  }

  notify(type) {
    return ajax({
      url: `/invoices/${this.id}/notify/${type}`,
      method: 'post',
    });
  }

  markAsIssued() {
    return this.makeGenericAjaxCall({
      method: 'post',
      data: {
        route_name: 'invoice_issue',
        url_params: JSON.stringify({
          '{id}': this.id,
        }),
      },
    }).then(response => {
      return new Invoice(response.data).deserialize();
    });
  }

  cancel() {
    return this.makeGenericAjaxCall({
      method: 'post',
      data: {
        route_name: 'invoice_cancel',
        url_params: JSON.stringify({
          '{id}': this.id,
        }),
      },
    }).then(response => {
      return new Invoice(response.data).deserialize();
    });
  }

  fetchPayments() {
    let payment = new Payment();
    return payment.fetchAll({
      invoice_id: this.id,
    });
  }

  serializeProperty(prop) {
    if (
      prop === 'sms_notify' ||
      prop === 'email_notify' ||
      prop === 'partial_payment'
    ) {
      return this[prop] ? 1 : 0;
    }

    if (prop === 'amount' && !isBlank(this.amountInINR)) {
      return Number(this.amountInINR) * 100;
    }

    if (prop === 'customer' && this.type === 'invoice' && !this.isNew) {
      return undefined;
    }

    if (prop === 'notes') {
      // Invoice type 'invoice' will have this.notes in object form
      if (this.notes && !(this.notes instanceof Array)) {
        return this.notes;
      }

      // Invoice type 'link' will have this.notes in object form
      let notes = this.notes || [];

      // Convert [{key: key1, value: value1}, {key: key2, value: value2}] into single Object like {key1: value}
      return notes.reduce((prev, curr) => {
        prev[curr.key] = curr.value;
        return prev;
      }, {});
    }

    if (prop === 'line_items' && !isBlank(this.line_items)) {
      if (this.type === 'link') {
        return this.line_items.map(item => {
          return {
            name: item.name,
            amount: Number(item.amount) * 100,
          };
        });
      } else if (this.type === 'invoice') {
        return this.line_items
          .filter(item => !!(item.item_id || item.id || item.name))
          .map((item, index) => {
            let lineItem = {
              quantity: item.quantity,
              description: item.description,
              amount: item.amountInINR * 100,
            };

            if (item.item_id) {
              lineItem.item_id = item.item_id;
            } else {
              lineItem.id = item.id;
            }

            return lineItem;
          });
      }
    }

    return super.serializeProperty(prop);
  }

  deserializeProperty(prop, value) {
    switch (prop) {
      case 'customer_details':
        this.customer = {
          name: value.customer_name,
          email: value.customer_email,
          contact: value.customer_contact,
          address: value.customer_address,
        };
        break;

      case 'amount':
        this.amountInINR = getFixedINRAmount(value);
        break;

      case 'line_items':
        value = value.map(item => {
          item.amountInINR = getFixedINRAmount(item.amount);
          return item;
        });
        break;

      case 'sms_status':
        this.sms_notify = !isBlank(value);
        break;

      case 'email_status':
        this.email_notify = !isBlank(value);
        break;
    }

    return super.deserializeProperty(prop, value);
  }
}
