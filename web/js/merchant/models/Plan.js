import GenericEntity from './GenericEntity';
import Subscription from './Subscription';
import { i18CurrencyConversionFromCommonUnitToMinorUnit } from 'common/utils/rzp-utils';

export default class Plan extends GenericEntity {
  resourceUrl = 'plans';

  resourceFields = ['period', 'interval', 'item', 'notes'];

  getRouteName() {
    return this.isNew ? 'plan_create' : 'plan_update';
  }

  fetchSubscriptions() {
    return this.makeGenericAjaxCall({
      data: { plan_id: this.id },
      url: 'subscriptions',
    }).then((response) => {
      response.data.items = response.data.items.map((item) => new Subscription(item).deserialize());

      return response;
    });
  }

  serializeProperty(prop) {
    if (prop === 'notes') {
      const notes = this.notes || [];
      return notes.reduce((prev, curr) => {
        prev[curr.key] = curr.value || '';
        return prev;
      }, {});
    }

    if (prop === 'item') {
      const item = this.item;
      return {
        ...item,
        amount: i18CurrencyConversionFromCommonUnitToMinorUnit(item.amount, item.currency),
      };
    }
    return super.serializeProperty(prop);
  }
}
