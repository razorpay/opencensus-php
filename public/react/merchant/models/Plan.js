import GenericEntity from './GenericEntity';

export default class Plan extends GenericEntity {
  listRouteName = 'plan_fetch_multiple';
  detailsRouteName = 'plan_fetch';
  deleteRouteName = 'plan_delete';

  resourceFields = ['period', 'interval', 'item'];

  getRouteName() {
    return this.isNew ? 'plan_create' : 'plan_update';
  }
}
