import GenericEntity from './GenericEntity';
import { normalizeBoolean } from 'common/utils/rzp-utils';

export default class Webhook extends GenericEntity {
  resourceUrl = 'webhooks';
  resourceFields = ['url', 'secret', 'events', 'active', 'alert_email'];

  getRouteName() {
    return this.isNew ? 'webhook_create' : 'webhook_edit';
  }

  getResourceMethod() {
    return this.isNew ? 'post' : 'put';
  }

  getAnalytics(params) {
    return this.makeGenericAjaxCall({
      url: `${this.resourceUrl}/${this.id}/analytics`,
      data: params,
    });
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
      return normalizeBoolean(this[prop] ?? prop);
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
