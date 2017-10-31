import GenericEntity from './GenericEntity';
import Subscription from './Subscription';
import { rupeesToPaise } from 'rzp/utils/rzp-utils';

export default class Plan extends GenericEntity {
  listRouteName = 'plan_fetch_multiple';
  detailsRouteName = 'plan_account_fetch';
  deleteRouteName = 'plan_delete';

  resourceFields = ['period', 'interval', 'item', 'notes'];

  getRouteName() {
    return this.isNew ? 'plan_create' : 'plan_update';
  }

  fetchSubscriptions() {
    let data = {};

    data.query_params = JSON.stringify({
      plan_id: this.id,
    });

    data.route_name = 'subscription_fetch_multiple';
    return this.makeGenericAjaxCall({ data }).then(response => {
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
