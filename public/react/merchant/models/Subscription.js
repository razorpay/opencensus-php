import GenericEntity from './GenericEntity';

export default class Subscription extends GenericEntity {
  listRouteName = 'subscription_fetch_multiple';
  detailsRouteName = 'subscription_fetch';
  deleteRouteName = 'subscription_delete';

  resourceFields = ['plan_id', 'customer_id'];

  getRouteName() {
    return this.isNew ? 'subscription_create' : 'subscription_update';
  }
}
