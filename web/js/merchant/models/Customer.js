import GenericEntity from './GenericEntity';
import ajax from 'merchant/utils/ajax';
import { isBlank, getCustomerDisplayName } from 'rzp/utils/rzp-utils';

export default class Customer extends GenericEntity {
  resourceUrl = 'customers';

  resourceFields = [
    'id',
    'name',
    'email',
    'contact',
    'gstin',
    'billing_address',
    'shipping_address',
  ];

  getRouteName() {
    return this.isNew ? 'customer_create' : 'customer_update';
  }

  getResourceMethod() {
    return this.isNew ? 'post' : 'put';
  }

  // This will be replaced with the ES autocomplete api
  fetchForAutocomplete(data = {}) {
    return ajax('/customers/autocomplete', { data }).then(response => {
      response.data.items = response.data.items.map(item =>
        new Customer(item).deserialize()
      );
      return response;
    });
  }

  /**
   * Fetches Customer's addresses.
   * @param {Object} data
   */
  fetchAddresses(data = {}) {
    if (!this.id) {
      return;
    }
    return this.makeGenericAjaxCall({
      data,
      url: `customers/${this.id}/addresses`,
    }).then(response => {
      return response;
    });
  }

  /**
   * Adds an address.
   * @param {Object} data
   */
  addAddress(data = {}) {
    if (!this.id) {
      return;
    }

    return this.makeGenericAjaxCall({
      data,
      url: `customers/${this.id}/addresses`,
      method: 'POST',
    }).then(response => {
      return response;
    });
  }

  /**
   * Override the serializeProperty method to perform some operations.
   * @param {String} prop
   * @return {Any}
   */
  serializeProperty(prop) {
    // We don't want to send addresses if this is not a new customer.
    const propsToDeleteIfOld = ['billing_address', 'shipping_address'];
    if (!this.isNew) {
      if (propsToDeleteIfOld.indexOf(prop) >= 0) {
        return undefined;
      }
    }

    return super.serializeProperty(prop);
  }

  didDeserialize() {
    // This is used as option display value in the autocomplete
    this.displayName = getCustomerDisplayName({
      name: this.name,
      contact: this.contact,
      email: this.email,
    });

    // This is used as selected display value in the autocomplete
    this.selectedDisplayName = this.name || this.contact || this.email;
  }
}
