import GenericEntity from './GenericEntity';
import Subscription from './Subscription';
import { rupeesToPaise } from 'rzp/utils/rzp-utils';

export default class Plan extends GenericEntity {
  resourceUrl = 'plans';

  resourceFields = ['period', 'interval', 'item', 'notes'];

  getRouteName() {
    return this.isNew ? 'plan_create' : 'plan_update';
  }

  fetchSubscriptions() {
    let data = {
      plan_id: this.id,
    };

    return this.makeGenericAjaxCall({
      data: { plan_id: this.id },
      url: 'subscriptions',
    }).then(response => {
      response.data.items = response.data.items.map(item =>
        new Subscription(item).deserialize()
      );

      return response;
    });
  }

  serializeProperty(prop) {
    if (prop === 'notes') {
      let notes = this.notes || [];
      return notes.reduce((prev, curr) => {
        prev[curr.key] = curr.value || '';
        return prev;
      }, {});
    }

    if (prop === 'item') {
      let item = this.item;
      return {
        ...item,
        amount: rupeesToPaise(item.amount),
      };
    }
    return super.serializeProperty(prop);
  }
}
