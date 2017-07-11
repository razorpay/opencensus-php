import GenericEntity from './GenericEntity';

export default class Plan extends GenericEntity {
  listRouteName = 'plan_fetch_multiple';
  detailsRouteName = 'plan_account_fetch';
  deleteRouteName = 'plan_delete';

  resourceFields = ['period', 'interval', 'item', 'notes'];

  getRouteName() {
    return this.isNew ? 'plan_create' : 'plan_update';
  }

  serializeProperty(prop) {
    if (prop === 'notes') {
      let notes = this.notes || [];
      return notes.reduce((prev, curr) => {
        prev[curr.key] = curr.value;
        return prev;
      }, {});
    }

    if (prop === 'item') {
      let item = this.item;
      return {
        ...item,
        amount: Number(item.amount) * 100,
      };
    }
    return super.serializeProperty(prop);
  }
}
