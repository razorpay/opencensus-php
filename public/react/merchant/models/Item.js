import GenericEntity from './GenericEntity';
import ajax from 'merchant/utils/ajax';
import { getFixedINRAmount } from 'rzp/utils/rzp-utils';

export default class Item extends GenericEntity {
  listRouteName = 'item_fetch_multiple';
  deleteRouteName = 'item_delete';
  resourceFields = ['id', 'name', 'amount', 'currency', 'description'];
  currency = 'INR';

  getRouteName() {
    return this.isNew ? 'item_create' : 'item_update';
  }

  // This will be replaced with the ES autocomplete api
  fetchForAutocomplete(data = {}) {
    return ajax('/test/items/autocomplete', { data }).then(response => {
      response.data.items = response.data.items.map(item =>
        new Item(item).deserialize()
      );
      return response;
    });
  }

  serializeProperty(prop) {
    if (prop === 'amount') {
      return Number(this.amountInINR) * 100;
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
