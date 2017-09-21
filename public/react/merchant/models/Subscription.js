import GenericEntity from './GenericEntity';

export default class Subscription extends GenericEntity {
  listRouteName = 'subscription_fetch_multiple';
  detailsRouteName = 'subscription_account_fetch';
  deleteRouteName = 'subscription_delete';

  resourceFields = ['plan_id', 'customer_id'];

  getRouteName() {
    return this.isNew ? 'subscription_create' : 'subscription_update';
  }

  cancel(cancelAtCycleEnd) {
    return this.makeGenericAjaxCall({
      method: 'post',
      data: {
        route_name: 'subscription_cancel',
        url_params: JSON.stringify({
          '{id}': this.id,
          '{cancel_at_cycle_end}': cancelAtCycleEnd,
        }),
      },
    }).then(response => {
      return new Subscription(response.data);
    });
  }

  fetchInvoices(subs_id) {
    return this.makeGenericAjaxCall({
      method: 'get',
      data: {
        route_name: 'invoice_fetch_multiple',
        query_params: JSON.stringify({
          subscription_id: subs_id,
        }),
      },
    });
  }
}
