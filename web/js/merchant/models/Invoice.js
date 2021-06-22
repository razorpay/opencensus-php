import GenericEntity from './GenericEntity';
import ajax from 'merchant/utils/ajax';
import { getFixedINRAmount, isBlank, rupeesToPaise } from 'common/utils/rzp-utils';
import Payment from 'merchant/models/Payment';

const createFields = [
  // 'id',
  // 'amount',
  'currency',
  'partial_payment',
  'date',
  'expire_by',
  'draft',
  'customer',
  // 'customer_id',
  'supply_state_code',
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
  'partial_payment',
  'date',
  'expire_by',
  'terms',
  'notes',
  'receipt',
  'comment',
];

export default class Invoice extends GenericEntity {
  resourceUrl = 'invoices';

  getRouteName() {
    return this.isNew ? 'invoice_create' : 'invoice_update';
  }

  resourceFields() {
    return this.status === 'issued' ? editableFieldsInIssuedState : createFields;
  }

  get isEditable() {
    return this.status !== 'paid';
  }

  notify(type) {
    return this.makeGenericAjaxCall({
      url: `/invoices/${this.id}/notify_by/${type}`,
      method: 'post',
    });
  }

  markAsIssued() {
    return this.makeGenericAjaxCall({
      url: `/${this.resourceUrl}/${this.id}/issue`,
      method: 'post',
    }).then((response) => {
      return new Invoice(response.data).deserialize();
    });
  }

  cancel() {
    return this.makeGenericAjaxCall({
      url: `${this.resourceUrl}/${this.id}/cancel`,
      method: 'post',
    }).then((response) => {
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
    // console.log('serializeProperty......', prop);

    if (prop === 'sms_notify' || prop === 'email_notify' || prop === 'partial_payment') {
      return this[prop] ? 1 : 0;
    }

    if (prop === 'amount' && !isBlank(this.amountInINR)) {
      return rupeesToPaise(this.amountInINR);
    }

    // Serialize the `this.customer` property.
    if (this.type === 'invoice' && prop === 'customer' && typeof this['customer'] === 'object') {
      let keys = Object.keys(this[prop]);
      let obj = {};
      for (let i = 0; i < keys.length; i++) {
        obj[keys[i]] = super.serializeProperty.call(this[prop], keys[i]);
      }
      return obj;
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
        prev[curr.key] = curr.value || '';
        return prev;
      }, {});
    }

    if (prop === 'line_items' && !isBlank(this.line_items)) {
      if (this.type === 'link') {
        return this.line_items.map((item) => {
          return {
            name: item.name,
            amount: rupeesToPaise(item.amount),
          };
        });
      } else if (this.type === 'invoice') {
        return this.line_items
          .filter((item) => !!(item.item_id || item.id || item.name))
          .map((item, index) => {
            let lineItem = {
              quantity: item.quantity,
              description: item.description,
              amount: rupeesToPaise(item.amountInINR),
              tax_rate: item.tax_rate,
              tax_inclusive: item.tax_inclusive,
            };

            // Set tax IDs if they exist.
            if (item.tax_ids && item.tax_ids.length > 0) {
              lineItem.tax_ids = item.tax_ids;
            }

            if (item.item_id) {
              lineItem.item_id = item.item_id;
            } else {
              lineItem.id = item.id;
            }

            if (item.currency) {
              lineItem.currency = item.currency;
            }

            if (item.addName || !item.item_id) {
              lineItem.name = item.name;
            }

            /**
             * `tax_id` being `null` specifies that no taxes are to be applied
             * on this line item. Need to explicitly send this to the API.
             */
            if (!this.supply_state_code) {
              lineItem.tax_id = null;
            }

            if (item.deleteTaxId) {
              delete lineItem.tax_id;
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
        // Get address IDs.
        let billingAddressID = value.billing_address;
        let shippingAddressID = value.shipping_address;
        if (billingAddressID && typeof billingAddressID === 'object' && billingAddressID.id) {
          billingAddressID = billingAddressID.id;
        }
        if (shippingAddressID && typeof shippingAddressID === 'object' && shippingAddressID.id) {
          shippingAddressID = shippingAddressID.id;
        }

        this.customer = {
          id: value.id,
          name: value.name,
          email: value.email,
          contact: value.contact,
          billing_address_id: billingAddressID,
          shipping_address_id: shippingAddressID,
          gstin: value.gstin,
        };
        break;

      case 'amount':
        this.amountInINR = getFixedINRAmount(value);
        break;

      case 'line_items':
        value = value.map((item) => {
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

  clearStash() {
    // this.
  }
}
