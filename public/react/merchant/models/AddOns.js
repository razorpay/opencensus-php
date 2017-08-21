import GenericEntity from './GenericEntity';
import Subscription from './Subscription';

export default class AddOns extends GenericEntity {
  listRouteName = 'addons_fetch_multiple';
  deleteRouteName = 'addon_delete';

  resourceFields = ['name', 'description', 'amount', 'units'];

  getRouteName() {
    return this.isNew ? 'addon_create' : 'addon_update';
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
