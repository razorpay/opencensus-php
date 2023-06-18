import GenericEntity from './GenericEntity';
import ajax from 'merchant/utils/ajax';
import {
  i18CurrencyConversionFromCommonUnitToMinorUnit,
  i18CurrencyConversionFromMinorUnitToCommonUnit,
} from 'common/utils/rzp-utils';

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
    'type',
  ];
  resourceUrl = 'items';

  getRouteName() {
    return this.isNew ? 'item_create' : 'item_update';
  }

  // This will be replaced with the ES autocomplete api
  fetchForAutocomplete(data = {}) {
    return ajax('/items/autocomplete', { data }).then((response) => {
      response.data.items = response.data.items.map((item) => new Item(item).deserialize());
      return response;
    });
  }

  serializeProperty(prop) {
    if (prop === 'amount') {
      return i18CurrencyConversionFromCommonUnitToMinorUnit(this.amountInINR, this.currency);
    }
    return super.serializeProperty(prop);
  }

  deserializeProperty(prop, value) {
    if (prop === 'amount') {
      this.amountInINR = i18CurrencyConversionFromMinorUnitToCommonUnit(value, this.currency);
    }
    return super.deserializeProperty(prop, value);
  }
}

export class SubscriptionItem extends Item {
  resourceUrl = 'subscriptions/items';
}
