import GenericEntity from './GenericEntity';

export default class Subscription extends GenericEntity {
  resourceUrl = 'subscriptions';

  serializeResponse = (response) => new Subscription(response.data);

  getRouteName() {
    return this.isNew ? 'subscription_create' : 'subscription_update';
  }

  cancel(cancelAtCycleEnd) {
    return this.makeGenericAjaxCall({
      method: 'post',
      url: `${this.resourceUrl}/${this.id}/cancel`,
      data: { cancel_at_cycle_end: cancelAtCycleEnd },
    }).then(this.serializeResponse);
  }

  cancelUpdate = () => {
    return this.makeGenericAjaxCall({
      method: 'post',
      url: `${this.resourceUrl}/${this.id}/cancel_scheduled_changes`,
    }).then(this.serializeResponse);
  };

  fetchScheduledChanges = () => {
    return this.makeGenericAjaxCall({
      method: 'get',
      url: `${this.resourceUrl}/${this.id}/retrieve_scheduled_changes`,
    }).then(this.serializeResponse);
  };

  fetchInvoices(subs_id) {
    return this.makeGenericAjaxCall({
      url: 'invoices',
      data: {
        subscription_id: subs_id,
        count: 100, // Fetch limited will cause bugs in FE calculations like recurring count, etc.
      },
    });
  }

  pauseOrResume = () => {
    return this.makeGenericAjaxCall({
      url: `subscriptions/${this.id}/${this.status === 'paused' ? 'resume' : 'pause'}`,
      method: 'post',
    }).then(this.serializeResponse);
  };

  removeOffer = () => {
    return this.makeGenericAjaxCall({
      url: `subscriptions/${this.id}/${this.offer_id}`,
      method: 'DELETE',
    }).then(this.serializeResponse);
  };
}
