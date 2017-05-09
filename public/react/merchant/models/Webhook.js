import GenericEntity from './GenericEntity';
import { normalizeBoolean } from 'rzp/utils/rzp-utils';

export default class Webhook extends GenericEntity {
  listRouteName = 'webhook_fetch_multiple';
  resourceFields = ['url', 'secret', 'events', 'active'];

  getRouteName() {
    return this.isNew ? 'webhook_create' : 'webhook_edit';
  }

  getResourceMethod() {
    return this.isNew ? 'post' : 'put';
  }

  serializeProperty(prop) {
    if (prop === 'events') {
      let events = this[prop] || {};
      return Object.keys(events).reduce((prev, key) => {
        prev[key] = normalizeBoolean(events[key]);
        return prev;
      }, {});
    }

    if (prop === 'active') {
      return normalizeBoolean(this[prop]);
    }

    return super.serializeProperty(prop);
  }

  deserializeProperty(prop, value) {
    if (prop === 'secret') {
      value = undefined;
    }

    return super.deserializeProperty(prop, value);
  }
}
