import GenericEntity from './GenericEntity';

export default class Subscription extends GenericEntity {
  // listRouteName = 'subscription_fetch_multiple';
  // detailsRouteName = 'subscription_account_fetch';
  // deleteRouteName = 'subscription_delete';
  resourceUrl = 'subscriptions';

  resourceFields = ['plan_id', 'customer_id'];

  getRouteName() {
    return this.isNew ? 'subscription_create' : 'subscription_update';
  }

  cancel(cancelAtCycleEnd) {
    return this.makeGenericAjaxCall({
      method: 'post',
      url: `${this.resourceUrl}/${this.id}/cancel`,
      data: { cancel_at_cycle_end: cancelAtCycleEnd },
    }).then(response => {
      return new Subscription(response.data);
    });
  }

  fetchInvoices(subs_id) {
    return this.makeGenericAjaxCall({
      url: 'invoices',
      data: {
        subscription_id: subs_id,
        count: 100, // Fetch limited will cause bugs in FE calculations like recurring count, etc.
      },
    });
  }
}
