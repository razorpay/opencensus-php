import GenericEntity from './GenericEntity';
import ajax from 'merchant/utils/ajax';
import { isBlank, getCustomerDisplayName } from 'rzp/utils/rzp-utils';

export default class Customer extends GenericEntity {
  resourceUrl = 'customers';

  resourceFields = ['id', 'name', 'email', 'contact'];

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
