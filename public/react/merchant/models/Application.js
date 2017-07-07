import GenericEntity from './GenericEntity';
import ajax from 'merchant/utils/ajax';

const editFields = ['id', 'delay_roll'];
const newFields = ['name', 'website'];

export default class Key extends GenericEntity {
  listRouteName = 'oauth_application_fetch_multiple';
  connectedListRouteName = 'oauth_token_fetch_multiple';
  deleteRouteName = 'oauth_application_delete';
  revokeRouteName = 'oauth_token_revoke';
  detailsRouteName = 'oauth_application_fetch';

  fetchAll(params = {}) {
    const Klass = this.constructor;
    let id = params.id;

    let data = {}

    data.route_name = this.listRouteName;
    return this.makeGenericAjaxCall({ data }).then(response => {
      response.data.items = response.data.items.map(item =>
        new Klass().deserialize(item)
      );
      return response; 
    });
  }

  fetchConnected(params = {}) {
    console.log('fetching connected apps')
    const Klass = this.constructor;
    let id = params.id;

    let data = {}

    data.route_name = this.connectedListRouteName;
    return this.makeGenericAjaxCall({ data }).then(response => {
      response.data.items = response.data.items.map(item =>
        new Klass().deserialize(item)
      );
      return response; 
    });
  }

  create(params = {}) {
    const Klass = this.constructor;
    let url = this.resourceUrl;
    let method = 'post'

    let data = {
      route_name: 'oauth_application_create'
    };
    data.body = params;
    return this.makeGenericAjaxCall({
      method,
      data,
    }).then(response => {
      return new Klass().deserialize(response.data);
    });
  }

  update(params={}) {
    let url = this.resourceUrl;
    let method = 'patch'
    let data = {
      route_name: 'oauth_application_update',
      body: params
    };
    return this.makeGenericAjaxCall({
      method,
      data,
    }).then(response => {
      return new Klass().deserialize(response.data);
    });
  }

  delete() {
    var id = this.id;
    return this.makeGenericAjaxCall({
      method: 'delete',
      data: {
        route_name: this.deleteRouteName,
        url_params: JSON.stringify({
          '{id}': this.id,
        }),
      },
    }).then((data) => {
      return {id, ...data}
    });
  }

  revokeToken (params) {
    var id = this.id;
    return this.makeGenericAjaxCall({
      method: 'put',
      data: {
        route_name: this.revokeRouteName,
        url_params: JSON.stringify({
          '{id}': this.id,
        }),
      },
    }).then((data) => {
      return {id, ...data}
    });
  }
}
