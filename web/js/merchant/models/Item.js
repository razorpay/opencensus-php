import GenericEntity from './GenericEntity';
import ajax from 'merchant/utils/ajax';
import { getFixedINRAmount, rupeesToPaise } from 'rzp/utils/rzp-utils';

export default class Item extends GenericEntity {
  resourceFields = [
    'id',
    'name',
    'amount',
    'currency',
    'description',
    'hsn_code',
    'sac_code',
    'tax_rate',
    'tax_inclusive',
    'tax_id',
  ];
  currency = 'INR';
  resourceUrl = 'items';

  getRouteName() {
    return this.isNew ? 'item_create' : 'item_update';
  }

  // This will be replaced with the ES autocomplete api
  fetchForAutocomplete(data = {}) {
    return ajax('/items/autocomplete', { data }).then(response => {
      response.data.items = response.data.items.map(item =>
        new Item(item).deserialize()
      );
      return response;
    });
  }

  serializeProperty(prop) {
    if (prop === 'amount') {
      return rupeesToPaise(this.amountInINR);
    }
    return super.serializeProperty(prop);
  }

  deserializeProperty(prop, value) {
    if (prop === 'amount') {
      this.amountInINR = getFixedINRAmount(value);
    }
    return super.deserializeProperty(prop, value);
  }
}
