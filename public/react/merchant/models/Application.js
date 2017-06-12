import GenericEntity from './GenericEntity';
import ajax from 'merchant/utils/ajax';

const editFields = ['id', 'delay_roll'];
const newFields = ['name', 'website'];

export default class Key extends GenericEntity {
  listRouteName = 'oauth_application_fetch_multiple';

  fetchAll(params = {}) {
    const Klass = this.constructor;
    let id = params.id;

    let data = {}
    if (id) {
      data.url_params = {
        '{id}': id,
      };
    }

    data.route_name = this.listRouteName;
    return this.makeGenericAjaxCall({ data }).then(response => {
      response.data.items = response.data.items.map(item =>
        new Klass().deserialize(item)
      );
      return response; 
    });
  }

  save() {
    const Klass = this.constructor;
    let params = this.serialize();
    let url = this.resourceUrl;
    let method = this.getResourceMethod();

    let data = {
      route_name: this.getRouteName(),
    };
    if (this.isNew) {
      data.url_params = JSON.stringify({
        '{id}': this.merchantId,
      });
    } else {
      data.url_params = JSON.stringify({
        '{keyId}': params.id,
        '{merchantId}': this.merchantId,
      });
      data.body = params;
    }

    return this.makeGenericAjaxCall({
      method,
      data,
    }).then(response => {
      if (this.isNew) {
        return new Klass().deserialize(response.data);
      } else {
        return {
          new: new Klass().deserialize(response.data.new),
          old: new Klass().deserialize(response.data.old),
        };
      }
    });
  }

  getRouteName() {
    return this.isNew ? 'oauth_application_create' : 'oauth_application_update';
  }

  getResourceMethod() {
    return this.isNew ? 'post' : 'put';
  }

  resourceFields() {
    return this.isNew ? newFields : editFields;
  }
}
